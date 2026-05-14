import { Router } from 'express';
import { requireAuth } from '../middleware/auth.js';
import { createAdminClient, isUserAdmin } from '../lib/supabase.js';
import { sendStudentEnrollmentEmail } from '../lib/resend.js';

export const studentsRouter = Router();

type StudentPromotionCandidate = {
  id: string;
  class_id: string | null;
  roll_number: string | null;
  enrollment_date: string;
};

function parseNumericRoll(rollNumber: string | null | undefined): number | null {
  if (!rollNumber) return null;
  const normalized = rollNumber.trim();
  if (!/^\d+$/.test(normalized)) return null;
  const parsed = Number.parseInt(normalized, 10);
  return Number.isFinite(parsed) ? parsed : null;
}

function getNextRollNumber(rolls: Array<{ roll_number: string | null }>): number {
  let maxRoll = 0;
  for (const row of rolls) {
    const parsed = parseNumericRoll(row.roll_number);
    if (parsed !== null && parsed > maxRoll) {
      maxRoll = parsed;
    }
  }
  return maxRoll + 1;
}

function sortByRollAndEnrollment(a: StudentPromotionCandidate, b: StudentPromotionCandidate): number {
  const rollA = parseNumericRoll(a.roll_number);
  const rollB = parseNumericRoll(b.roll_number);

  if (rollA !== null && rollB !== null && rollA !== rollB) {
    return rollA - rollB;
  }
  if (rollA !== null && rollB === null) {
    return -1;
  }
  if (rollA === null && rollB !== null) {
    return 1;
  }

  const enrollmentDiff = new Date(a.enrollment_date).getTime() - new Date(b.enrollment_date).getTime();
  if (enrollmentDiff !== 0) {
    return enrollmentDiff;
  }

  return a.id.localeCompare(b.id);
}

// GET /api/students/me — get the logged-in student's profile
// IMPORTANT: This must come BEFORE the /:id route to match properly
studentsRouter.get('/me', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();

    // Get student record (filtered by authenticated user's ID)
    const { data: studentData, error: studentError } = await supabase
      .from('students')
      .select(`
        id,
        user_id,
        class_id,
        enrollment_date,
        classes (
          id,
          name
        )
      `)
      .eq('user_id', req.userId)
      .maybeSingle();

    if (studentError) return res.status(400).json({ error: studentError.message });
    if (!studentData) return res.status(404).json({ error: 'Student record not found' });

    // Get profile data
    const { data: profileData, error: profileError } = await supabase
      .from('profiles')
      .select('first_name, last_name, avatar_url')
      .eq('user_id', req.userId)
      .maybeSingle();

    if (profileError) return res.status(400).json({ error: profileError.message });

    // Get user email from auth.users table using admin client
    const { data: authUser, error: authError } = await supabase.auth.admin.getUserById(req.userId!);
    if (authError) return res.status(400).json({ error: authError.message });

    const classInfo = studentData.classes as unknown as { id: string; name: string } | null;

    res.json({
      firstName: profileData?.first_name ?? '',
      lastName: profileData?.last_name ?? '',
      email: authUser?.user?.email ?? '',
      className: classInfo?.name ?? 'Not assigned',
      classId: classInfo?.id ?? null,
      admissionDate: studentData.enrollment_date,
    });
  } catch (err) {
    console.error('Student profile error:', err);
    res.status(500).json({ error: 'Failed to fetch student profile' });
  }
});

// GET /api/students/me/attendance/stats — get attendance statistics for logged-in student
studentsRouter.get('/me/attendance/stats', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();

    // Get student id (filtered by authenticated user's ID)
    const { data: studentData, error: studentError } = await supabase
      .from('students')
      .select('id')
      .eq('user_id', req.userId)
      .maybeSingle();

    if (studentError) return res.status(400).json({ error: studentError.message });
    if (!studentData) return res.status(404).json({ error: 'Student record not found' });

    // Get attendance records
    const { data: attendanceData, error: attendanceError } = await supabase
      .from('student_attendance')
      .select('status')
      .eq('student_id', studentData.id);

    if (attendanceError) return res.status(400).json({ error: attendanceError.message });

    const records = attendanceData || [];
    const totalDays = records.length;
    const presentDays = records.filter((r: any) => r.status === 'Present').length;
    const absentDays = records.filter((r: any) => r.status === 'Absent').length;
    const lateDays = records.filter((r: any) => r.status === 'Late').length;
    const attendancePercentage = totalDays > 0
      ? Math.round(((presentDays + lateDays) / totalDays) * 100)
      : 0;

    res.json({
      totalDays,
      presentDays,
      absentDays,
      lateDays,
      attendancePercentage,
    });
  } catch (err) {
    console.error('Student attendance stats error:', err);
    res.status(500).json({ error: 'Failed to fetch attendance stats' });
  }
});

// GET /api/students/me/attendance — get attendance records for logged-in student
studentsRouter.get('/me/attendance', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();

    // Get student id (filtered by authenticated user's ID)
    const { data: studentData, error: studentError } = await supabase
      .from('students')
      .select('id')
      .eq('user_id', req.userId)
      .maybeSingle();

    if (studentError) return res.status(400).json({ error: studentError.message });
    if (!studentData) return res.status(404).json({ error: 'Student record not found' });

    // Get attendance records ordered by date descending
    const { data: attendanceData, error: attendanceError } = await supabase
      .from('student_attendance')
      .select('id, date, status, marked_by')
      .eq('student_id', studentData.id)
      .order('date', { ascending: false });

    if (attendanceError) return res.status(400).json({ error: attendanceError.message });

    res.json(attendanceData || []);
  } catch (err) {
    console.error('Student attendance records error:', err);
    res.status(500).json({ error: 'Failed to fetch attendance records' });
  }
});

// POST /api/students/promotions/bulk — promote or retain students in bulk (admin only)
studentsRouter.post('/promotions/bulk', requireAuth, async (req, res) => {
  try {
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const {
      fromClassId,
      toClassId,
      academicYear,
      promotedStudentIds,
      reassignRetainedRollNumbers,
    } = req.body ?? {};

    const normalizedFromClassId = typeof fromClassId === 'string' ? fromClassId.trim() : '';
    const normalizedToClassId = typeof toClassId === 'string' ? toClassId.trim() : '';
    const normalizedAcademicYear = typeof academicYear === 'string' && academicYear.trim()
      ? academicYear.trim()
      : `${new Date().getFullYear()}-${new Date().getFullYear() + 1}`;
    const shouldReassignRetainedRolls = Boolean(reassignRetainedRollNumbers);

    if (!normalizedFromClassId || !normalizedToClassId) {
      return res.status(400).json({ error: 'fromClassId and toClassId are required' });
    }
    if (normalizedFromClassId === normalizedToClassId) {
      return res.status(400).json({ error: 'fromClassId and toClassId must be different' });
    }
    if (!Array.isArray(promotedStudentIds)) {
      return res.status(400).json({ error: 'promotedStudentIds must be an array' });
    }

    const normalizedPromotedStudentIds = Array.from(
      new Set(
        promotedStudentIds
          .filter((studentId: unknown): studentId is string => typeof studentId === 'string')
          .map((studentId: string) => studentId.trim())
          .filter(Boolean)
      )
    );

    const adminClient = createAdminClient();

    const [{ data: fromClass, error: fromClassError }, { data: toClass, error: toClassError }] = await Promise.all([
      adminClient.from('classes').select('id, name').eq('id', normalizedFromClassId).maybeSingle(),
      adminClient.from('classes').select('id, name').eq('id', normalizedToClassId).maybeSingle(),
    ]);

    if (fromClassError) return res.status(400).json({ error: fromClassError.message });
    if (toClassError) return res.status(400).json({ error: toClassError.message });
    if (!fromClass) return res.status(404).json({ error: 'Source class not found' });
    if (!toClass) return res.status(404).json({ error: 'Target class not found' });

    const { data: sourceClassStudents, error: sourceClassStudentsError } = await adminClient
      .from('students')
      .select('id, class_id, roll_number, enrollment_date')
      .eq('class_id', normalizedFromClassId);

    if (sourceClassStudentsError) {
      return res.status(400).json({ error: sourceClassStudentsError.message });
    }

    const studentsInSourceClass = (sourceClassStudents || []) as StudentPromotionCandidate[];
    if (studentsInSourceClass.length === 0) {
      return res.status(400).json({ error: 'No students found in source class' });
    }

    const sourceStudentIds = new Set(studentsInSourceClass.map((student) => student.id));
    const invalidPromotedIds = normalizedPromotedStudentIds.filter((studentId) => !sourceStudentIds.has(studentId));
    if (invalidPromotedIds.length > 0) {
      return res.status(400).json({
        error: 'Some selected students are not in the source class',
        invalidStudentIds: invalidPromotedIds,
      });
    }

    const { data: existingPromotions, error: existingPromotionsError } = await adminClient
      .from('student_promotions')
      .select('student_id')
      .in('student_id', studentsInSourceClass.map((student) => student.id))
      .eq('academic_year', normalizedAcademicYear);

    if (existingPromotionsError) {
      return res.status(400).json({
        error: `Failed to validate promotion history: ${existingPromotionsError.message}`,
      });
    }

    if ((existingPromotions || []).length > 0) {
      return res.status(409).json({
        error: `Promotion already processed for academic year ${normalizedAcademicYear} for one or more students.`,
      });
    }

    const promotedStudentIdSet = new Set(normalizedPromotedStudentIds);
    const promotedStudents = studentsInSourceClass
      .filter((student) => promotedStudentIdSet.has(student.id))
      .sort(sortByRollAndEnrollment);
    const retainedStudents = studentsInSourceClass
      .filter((student) => !promotedStudentIdSet.has(student.id))
      .sort(sortByRollAndEnrollment);

    const { data: targetClassRolls, error: targetClassRollsError } = await adminClient
      .from('students')
      .select('roll_number')
      .eq('class_id', normalizedToClassId);

    if (targetClassRollsError) {
      return res.status(400).json({ error: targetClassRollsError.message });
    }

    let nextTargetClassRollNumber = getNextRollNumber(targetClassRolls || []);
    const promotedRollAssignments = new Map<string, string>();
    for (const student of promotedStudents) {
      promotedRollAssignments.set(student.id, String(nextTargetClassRollNumber));
      nextTargetClassRollNumber += 1;
    }

    const retainedRollAssignments = new Map<string, string>();
    if (shouldReassignRetainedRolls) {
      let nextRetainedRollNumber = 1;
      for (const student of retainedStudents) {
        retainedRollAssignments.set(student.id, String(nextRetainedRollNumber));
        nextRetainedRollNumber += 1;
      }
    }

    for (const student of promotedStudents) {
      const assignedRollNumber = promotedRollAssignments.get(student.id);
      const { error: updateError } = await adminClient
        .from('students')
        .update({
          class_id: normalizedToClassId,
          roll_number: assignedRollNumber ?? null,
        })
        .eq('id', student.id);

      if (updateError) {
        return res.status(400).json({ error: updateError.message });
      }
    }

    if (shouldReassignRetainedRolls) {
      for (const student of retainedStudents) {
        const assignedRollNumber = retainedRollAssignments.get(student.id);
        const { error: updateError } = await adminClient
          .from('students')
          .update({
            roll_number: assignedRollNumber ?? null,
          })
          .eq('id', student.id);

        if (updateError) {
          return res.status(400).json({ error: updateError.message });
        }
      }
    }

    const promotionAuditRows = [
      ...promotedStudents.map((student) => ({
        student_id: student.id,
        from_class_id: normalizedFromClassId,
        to_class_id: normalizedToClassId,
        from_roll_number: student.roll_number ?? null,
        to_roll_number: promotedRollAssignments.get(student.id) ?? null,
        result: 'promoted',
        academic_year: normalizedAcademicYear,
        promoted_by: req.userId,
      })),
      ...retainedStudents.map((student) => ({
        student_id: student.id,
        from_class_id: normalizedFromClassId,
        to_class_id: normalizedFromClassId,
        from_roll_number: student.roll_number ?? null,
        to_roll_number: shouldReassignRetainedRolls
          ? (retainedRollAssignments.get(student.id) ?? null)
          : (student.roll_number ?? null),
        result: 'retained',
        academic_year: normalizedAcademicYear,
        promoted_by: req.userId,
      })),
    ];

    if (promotionAuditRows.length > 0) {
      const { error: auditInsertError } = await adminClient.from('student_promotions').insert(promotionAuditRows);
      if (auditInsertError) {
        return res.status(400).json({ error: auditInsertError.message });
      }
    }

    res.json({
      success: true,
      academicYear: normalizedAcademicYear,
      promotedCount: promotedStudents.length,
      retainedCount: retainedStudents.length,
      sourceClassName: fromClass.name,
      targetClassName: toClass.name,
      reassignRetainedRollNumbers: shouldReassignRetainedRolls,
    });
  } catch (err: any) {
    console.error('[POST /api/students/promotions/bulk] Error:', err);
    res.status(500).json({ error: err.message || 'Failed to process student promotions' });
  }
});

// GET /api/students/:id — get a single student's full details (admin only)
studentsRouter.get('/:id', requireAuth, async (req, res) => {
  try {
    const studentId = req.params.id;
    console.log(`[GET /api/students/:id] Fetching student: ${studentId}`);

    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      console.log(`[GET /api/students/:id] Access denied: user ${req.userId} is not admin`);
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const supabase = createAdminClient();

    // Get student with profile and class info
    const { data: studentData, error: studentError } = await supabase
      .from('students')
      .select(`
        id,
        user_id,
        class_id,
        enrollment_date,
        roll_number,
        profiles (
          first_name,
          last_name,
          avatar_url,
          dob
        ),
        classes (
          id,
          name
        )
      `)
      .eq('id', studentId)
      .maybeSingle();

    if (studentError) {
      console.error(`[GET /api/students/:id] Error fetching student: ${studentError.message}`);
      return res.status(400).json({ error: studentError.message });
    }

    if (!studentData) {
      console.log(`[GET /api/students/:id] Student not found: ${studentId}`);
      return res.status(404).json({ error: 'Student not found' });
    }

    // Get user email from auth
    const { data: authUser, error: authError } = await supabase.auth.admin.getUserById(studentData.user_id);
    if (authError) {
      console.error(`[GET /api/students/:id] Error fetching auth user: ${authError.message}`);
    }

    // Get attendance stats
    const { data: attendanceData, error: attendanceError } = await supabase
      .from('student_attendance')
      .select('status')
      .eq('student_id', studentData.id);

    if (attendanceError) {
      console.error(`[GET /api/students/:id] Error fetching attendance: ${attendanceError.message}`);
    }

    const records = attendanceData || [];
    const totalDays = records.length;
    const presentDays = records.filter((r: any) => r.status === 'Present').length;
    const absentDays = records.filter((r: any) => r.status === 'Absent').length;
    const lateDays = records.filter((r: any) => r.status === 'Late').length;
    const attendancePercentage = totalDays > 0 ? Math.round(((presentDays + lateDays) / totalDays) * 100) : 0;

    const profile = studentData.profiles as any;
    const classInfo = studentData.classes as any;

    const response = {
      id: studentData.id,
      userId: studentData.user_id,
      firstName: profile?.first_name ?? '',
      lastName: profile?.last_name ?? '',
      email: authUser?.user?.email ?? '',
      avatarUrl: profile?.avatar_url,
      dob: profile?.dob,
      rollNumber: studentData.roll_number || 'N/A',
      className: classInfo?.name ?? 'Not assigned',
      classId: classInfo?.id ?? null,
      enrollmentDate: studentData.enrollment_date,
      attendance: {
        totalDays,
        presentDays,
        absentDays,
        lateDays,
        attendancePercentage,
      }
    };

    console.log(`[GET /api/students/:id] Successfully fetched student: ${studentId}`);
    res.json(response);
  } catch (err) {
    console.error('Get student details error:', err);
    res.status(500).json({ error: 'Failed to fetch student details' });
  }
});

// DELETE /api/students/:id (admin only)
studentsRouter.delete('/:id', requireAuth, async (req, res) => {
  try {
    // Verify user is admin
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const supabase = createAdminClient();
    const { error } = await supabase.from('students').delete().eq('id', req.params.id);
    if (error) return res.status(400).json({ error: error.message });
    res.json({ success: true });
  } catch (err) {
    res.status(500).json({ error: 'Failed to delete student' });
  }
});

// POST /api/students — admin creates a student account
studentsRouter.post('/', requireAuth, async (req, res) => {
  try {
    // Verify user is admin
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const admin = createAdminClient();
    const { email, password, firstName, lastName, classId, dob } = req.body;
    const normalizedEmail = typeof email === 'string' ? email.trim() : '';

    console.log('[POST /api/students] Creating student:', { email: normalizedEmail, firstName, lastName, classId });

    const { data: userData, error: authError } = await admin.auth.admin.createUser({
      email: normalizedEmail,
      password,
      email_confirm: true,
      user_metadata: {
        first_name: firstName,
        last_name: lastName,
        role: 'student',
        class_id: classId,
        dob
      }
    });

    if (authError) {
      console.error('[POST /api/students] Auth error:', authError);
      return res.status(400).json({ error: authError.message });
    }

    // Wait for the DB trigger to create profile and user_role (retry-based, not setTimeout)
    const maxRetries = 5;
    for (let attempt = 1; attempt <= maxRetries; attempt++) {
      const { data: profileCheck } = await admin
        .from('profiles')
        .select('user_id')
        .eq('user_id', userData.user!.id)
        .maybeSingle();
      if (profileCheck) break;
      if (attempt === maxRetries) {
        console.warn('[POST /api/students] Profile trigger did not fire after retries — proceeding anyway');
      }
      await new Promise(resolve => setTimeout(resolve, 200));
    }

    // Now manually create the student record in the students table
    const { data: studentData, error: studentError } = await admin
      .from('students')
      .insert([{
        user_id: userData.user!.id,
        class_id: classId || null,
        dob: dob || null,
        enrollment_date: new Date().toISOString()
      }])
      .select()
      .single();

    if (studentError) {
      console.error('[POST /api/students] Student record creation error:', studentError);
      // Clean up auth user if student creation fails
      await admin.auth.admin.deleteUser(userData.user!.id);
      return res.status(400).json({ error: 'Failed to create student record: ' + studentError.message });
    }

    console.log('[POST /api/students] Student record created:', studentData.id);

    // Assign roll number if class is provided
    if (classId) {
      try {
        // Get the highest roll number for this class
        const { data: allRolls } = await admin
          .from('students')
          .select('roll_number')
          .eq('class_id', classId)
          .not('roll_number', 'is', null)
          .order('roll_number', { ascending: false });

        const nextRollNumber = (allRolls && allRolls.length > 0 && allRolls[0].roll_number)
          ? (allRolls[0].roll_number as number) + 1
          : 1;

        // Update the student with the assigned roll number
        const { error: updateError } = await admin
          .from('students')
          .update({ roll_number: nextRollNumber })
          .eq('id', studentData.id);

        if (updateError) {
          console.warn('[POST /api/students] Warning updating roll number:', updateError);
        } else {
          console.log('[POST /api/students] Roll number assigned:', nextRollNumber);
        }
      } catch (rollError: any) {
        console.warn('[POST /api/students] Warning assigning roll number:', rollError.message);
      }
    }

    console.log('[POST /api/students] Student created successfully:', userData.user?.id);

    // Respond immediately — don't make the client wait for SMTP
    res.json({
      user: userData.user,
      emailSent: true,
      emailError: null,
    });

    // Fire-and-forget: send email in the background
    sendStudentEnrollmentEmail({
      to: normalizedEmail,
      firstName,
      lastName,
      loginEmail: normalizedEmail,
      temporaryPassword: password,
    }).then(result => {
      if (!result.sent) {
        console.error('[POST /api/students] Enrollment email failed:', result.error);
      } else {
        console.log('[POST /api/students] Enrollment email sent:', result.messageId);
      }
    }).catch(err => {
      console.error('[POST /api/students] Enrollment email error:', err);
    });
  } catch (err: any) {
    console.error('[POST /api/students] Error:', err);
    res.status(500).json({ error: err.message || 'Failed to create student' });
  }
});

// GET /api/students?class_id=xxx (admin only)
// IMPORTANT: This must be LAST so it doesn't match /me, /me/attendance, etc.
studentsRouter.get('/', requireAuth, async (req, res) => {
  try {
    // Verify user is admin
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    // Use admin client to fetch all students
    const supabase = createAdminClient();
    let query = supabase.from('students').select(`
      id,
      user_id,
      enrollment_date,
      roll_number,
      profiles (
        first_name,
        last_name,
        avatar_url
      ),
      classes (
        id,
        name
      )
    `);

    if (req.query.class_id && req.query.class_id !== 'all') {
      query = query.eq('class_id', req.query.class_id as string);
    }

    const { data, error } = await query;
    if (error) return res.status(400).json({ error: error.message });

    // Fetch emails for all students in parallel
    const studentsWithEmails = await Promise.all(
      (data || []).map(async (student: any) => {
        try {
          const { data: authUser } = await supabase.auth.admin.getUserById(student.user_id);
          return {
            ...student,
            email: authUser?.user?.email ?? ''
          };
        } catch (err) {
          return {
            ...student,
            email: ''
          };
        }
      })
    );

    res.json(studentsWithEmails);
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch students' });
  }
});
