import { Router } from 'express';
import multer from 'multer';
import * as XLSX from 'xlsx';
import { requireAuth } from '../middleware/auth.js';
import { createAdminClient, isUserAdmin } from '../lib/supabase.js';
import { sendStudentEnrollmentEmail, isEmailConfigured } from '../lib/resend.js';

export const studentsRouter = Router();

// Multer config — store in memory (files are small spreadsheets)
const upload = multer({
  storage: multer.memoryStorage(),
  limits: { fileSize: 5 * 1024 * 1024 }, // 5 MB
  fileFilter: (_req, file, cb) => {
    const allowed = [
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'application/vnd.ms-excel',
      'text/csv',
      'application/octet-stream',
    ];
    if (allowed.includes(file.mimetype) || file.originalname.match(/\.(xlsx|xls|csv)$/i)) {
      cb(null, true);
    } else {
      cb(new Error('Only .xlsx, .xls, and .csv files are allowed'));
    }
  },
});

/** Generate a secure random password (server-side only, never sent to client) */
function generateBulkPassword(): string {
  const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  const lowercase = 'abcdefghijklmnopqrstuvwxyz';
  const numbers = '0123456789';
  const special = '!@#$%^&*';
  const allChars = uppercase + lowercase + numbers + special;
  let password = '';
  password += uppercase.charAt(Math.floor(Math.random() * uppercase.length));
  password += lowercase.charAt(Math.floor(Math.random() * lowercase.length));
  password += numbers.charAt(Math.floor(Math.random() * numbers.length));
  password += special.charAt(Math.floor(Math.random() * special.length));
  for (let i = password.length; i < 12; i++) {
    password += allChars.charAt(Math.floor(Math.random() * allChars.length));
  }
  return password.split('').sort(() => Math.random() - 0.5).join('');
}

/** Small delay helper for rate-limiting email sends */
function delay(ms: number): Promise<void> {
  return new Promise(resolve => setTimeout(resolve, ms));
}

type StudentPromotionCandidate = {
  id: string;
  class_id: string | null;
  roll_number: string | number | null;
  enrollment_date: string;
};

function parseNumericRoll(rollNumber: string | number | null | undefined): number | null {
  if (rollNumber === null || rollNumber === undefined) return null;
  const normalized = String(rollNumber).trim();
  if (!/^\d+$/.test(normalized)) return null;
  const parsed = Number.parseInt(normalized, 10);
  return Number.isFinite(parsed) ? parsed : null;
}

function getNextRollNumber(rolls: Array<{ roll_number: string | number | null }>): number {
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

// POST /api/students/parse-bulk — parse an uploaded Excel/CSV file (admin only)
studentsRouter.post('/parse-bulk', requireAuth, upload.single('file'), async (req, res) => {
  try {
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    if (!req.file) {
      return res.status(400).json({ error: 'No file uploaded' });
    }

    console.log('[POST /api/students/parse-bulk] Parsing file:', req.file.originalname, 'size:', req.file.size);

    // Parse the Excel/CSV file from buffer
    const workbook = XLSX.read(req.file.buffer, { type: 'buffer' });
    const sheetName = workbook.SheetNames[0];
    if (!sheetName) {
      return res.status(400).json({ error: 'No sheets found in the file' });
    }

    const sheet = workbook.Sheets[sheetName];
    const rawRows: Record<string, any>[] = XLSX.utils.sheet_to_json(sheet, { defval: '' });

    if (rawRows.length === 0) {
      return res.status(400).json({ error: 'The file is empty or has no data rows' });
    }

    if (rawRows.length > 200) {
      return res.status(400).json({ error: 'Maximum 200 students per upload. Please split into smaller files.' });
    }

    // Fetch all classes to match names
    const supabase = createAdminClient();
    const { data: allClasses, error: classError } = await supabase
      .from('classes')
      .select('id, name');

    if (classError) {
      return res.status(400).json({ error: 'Failed to fetch classes: ' + classError.message });
    }

    // Build a case-insensitive class name -> id map
    const classMap = new Map<string, { id: string; name: string }>();
    for (const cls of (allClasses || [])) {
      classMap.set(cls.name.toLowerCase().trim(), { id: cls.id, name: cls.name });
    }

    // Normalize column header names (case-insensitive, trim whitespace)
    function findCol(row: Record<string, any>, ...candidates: string[]): string {
      for (const key of Object.keys(row)) {
        const normalized = key.toLowerCase().trim().replace(/[_\s]+/g, '');
        for (const candidate of candidates) {
          if (normalized === candidate.toLowerCase().replace(/[_\s]+/g, '')) {
            return String(row[key]).trim();
          }
        }
      }
      return '';
    }

    // Parse and validate each row
    const parsedStudents: Array<{
      rowNumber: number;
      firstName: string;
      lastName: string;
      email: string;
      className: string;
      classId: string | null;
      dob: string;
      errors: string[];
    }> = [];

    const seenEmails = new Set<string>();

    for (let i = 0; i < rawRows.length; i++) {
      const row = rawRows[i];
      const rowNum = i + 2; // +2 because row 1 is header, data starts at row 2

      const firstName = findCol(row, 'firstName', 'first_name', 'first name', 'firstname');
      const lastName = findCol(row, 'lastName', 'last_name', 'last name', 'lastname');
      const email = findCol(row, 'email', 'e-mail', 'email address', 'emailaddress').toLowerCase();
      const classNameRaw = findCol(row, 'class', 'className', 'class_name', 'classname', 'class name');
      const dobRaw = findCol(row, 'dob', 'date of birth', 'dateofbirth', 'date_of_birth', 'birthday');

      const errors: string[] = [];

      if (!firstName) errors.push('First Name is required');
      if (!lastName) errors.push('Last Name is required');
      if (!email) {
        errors.push('Email is required');
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errors.push('Invalid email format');
      } else if (seenEmails.has(email)) {
        errors.push('Duplicate email in file');
      }

      if (email) seenEmails.add(email);

      let classId: string | null = null;
      let resolvedClassName = classNameRaw;
      if (classNameRaw) {
        const match = classMap.get(classNameRaw.toLowerCase().trim());
        if (match) {
          classId = match.id;
          resolvedClassName = match.name;
        } else {
          errors.push(`Class "${classNameRaw}" not found`);
        }
      }

      // Parse DOB — handle Excel serial numbers and string dates
      let dob = '';
      if (dobRaw) {
        const numericDob = Number(dobRaw);
        if (!isNaN(numericDob) && numericDob > 10000) {
          // Excel serial date number
          const excelDate = XLSX.SSF.parse_date_code(numericDob);
          if (excelDate) {
            dob = `${excelDate.y}-${String(excelDate.m).padStart(2, '0')}-${String(excelDate.d).padStart(2, '0')}`;
          }
        } else {
          // Try to parse as date string
          const parsed = new Date(dobRaw);
          if (!isNaN(parsed.getTime())) {
            dob = parsed.toISOString().split('T')[0];
          }
        }
      }

      parsedStudents.push({
        rowNumber: rowNum,
        firstName,
        lastName,
        email,
        className: resolvedClassName,
        classId,
        dob,
        errors,
      });
    }

    console.log('[POST /api/students/parse-bulk] Parsed', parsedStudents.length, 'rows,',
      parsedStudents.filter(s => s.errors.length === 0).length, 'valid');

    res.json({
      totalRows: parsedStudents.length,
      validRows: parsedStudents.filter(s => s.errors.length === 0).length,
      students: parsedStudents,
    });
  } catch (err: any) {
    console.error('[POST /api/students/parse-bulk] Error:', err);
    res.status(500).json({ error: err.message || 'Failed to parse file' });
  }
});

// POST /api/students/bulk — create multiple students (admin only)
// Passwords are generated server-side and NEVER returned to the client.
studentsRouter.post('/bulk', requireAuth, async (req, res) => {
  try {
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const { students, sendEmails = false } = req.body ?? {};

    if (!Array.isArray(students) || students.length === 0) {
      return res.status(400).json({ error: 'students array is required and must not be empty' });
    }

    if (students.length > 200) {
      return res.status(400).json({ error: 'Maximum 200 students per batch' });
    }

    console.log('[POST /api/students/bulk] Creating', students.length, 'students, sendEmails:', sendEmails);

    const admin = createAdminClient();
    const emailReady = sendEmails && isEmailConfigured();

    const results: Array<{
      rowNumber: number;
      email: string;
      firstName: string;
      lastName: string;
      success: boolean;
      error?: string;
      emailSent?: boolean;
      emailError?: string | null;
    }> = [];

    for (let i = 0; i < students.length; i++) {
      const student = students[i];
      const { firstName, lastName, email, classId, dob, rowNumber } = student;
      const normalizedEmail = typeof email === 'string' ? email.trim().toLowerCase() : '';
      const password = generateBulkPassword();

      try {
        // 1. Create auth user
        const { data: userData, error: authError } = await admin.auth.admin.createUser({
          email: normalizedEmail,
          password,
          email_confirm: true,
          user_metadata: {
            first_name: firstName,
            last_name: lastName,
            role: 'student',
            class_id: classId || null,
            dob: dob || null,
          },
        });

        if (authError) {
          results.push({
            rowNumber,
            email: normalizedEmail,
            firstName,
            lastName,
            success: false,
            error: authError.message,
          });
          continue;
        }

        // 2. Wait for profile trigger
        for (let attempt = 1; attempt <= 3; attempt++) {
          const { data: profileCheck } = await admin
            .from('profiles')
            .select('user_id')
            .eq('user_id', userData.user!.id)
            .maybeSingle();
          if (profileCheck) break;
          await delay(200);
        }

        // 3. Create student record
        const { data: studentData, error: studentError } = await admin
          .from('students')
          .insert([{
            user_id: userData.user!.id,
            class_id: classId || null,
            dob: dob || null,
            enrollment_date: new Date().toISOString(),
          }])
          .select()
          .single();

        if (studentError) {
          await admin.auth.admin.deleteUser(userData.user!.id);
          results.push({
            rowNumber,
            email: normalizedEmail,
            firstName,
            lastName,
            success: false,
            error: 'Failed to create student record: ' + studentError.message,
          });
          continue;
        }

        // 4. Assign roll number
        if (classId) {
          try {
            const { data: allRolls } = await admin
              .from('students')
              .select('roll_number')
              .eq('class_id', classId)
              .not('roll_number', 'is', null)
              .order('roll_number', { ascending: false });

            const nextRollNumber = (allRolls && allRolls.length > 0 && allRolls[0].roll_number)
              ? (allRolls[0].roll_number as number) + 1
              : 1;

            await admin
              .from('students')
              .update({ roll_number: nextRollNumber })
              .eq('id', studentData.id);
          } catch (rollError: any) {
            console.warn('[POST /api/students/bulk] Roll number warning:', rollError.message);
          }
        }

        // 5. Send email (if enabled)
        let emailSent = false;
        let emailError: string | null = null;

        if (emailReady) {
          try {
            const result = await sendStudentEnrollmentEmail({
              to: normalizedEmail,
              firstName,
              lastName,
              loginEmail: normalizedEmail,
              temporaryPassword: password,
            });
            emailSent = result.sent;
            if (!result.sent) {
              emailError = result.error || 'Failed to send email';
            }
          } catch (err: any) {
            emailError = err.message || 'Unknown email error';
          }

          // Rate-limit: wait 1 second between emails to avoid hitting provider limits
          if (i < students.length - 1) {
            await delay(1000);
          }
        }

        results.push({
          rowNumber,
          email: normalizedEmail,
          firstName,
          lastName,
          success: true,
          emailSent,
          emailError,
        });
      } catch (err: any) {
        results.push({
          rowNumber,
          email: normalizedEmail,
          firstName,
          lastName,
          success: false,
          error: err.message || 'Unknown error',
        });
      }
    }

    const successCount = results.filter(r => r.success).length;
    const failCount = results.filter(r => !r.success).length;
    const emailsSent = results.filter(r => r.emailSent).length;

    console.log('[POST /api/students/bulk] Done:', successCount, 'success,', failCount, 'failed,', emailsSent, 'emails sent');

    res.json({
      totalProcessed: results.length,
      successCount,
      failCount,
      emailsSent,
      results,
    });
  } catch (err: any) {
    console.error('[POST /api/students/bulk] Error:', err);
    res.status(500).json({ error: err.message || 'Failed to process bulk enrollment' });
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

    const isGraduation = normalizedToClassId === 'graduate';

    const [{ data: fromClass, error: fromClassError }, toClassResult] = await Promise.all([
      adminClient.from('classes').select('id, name').eq('id', normalizedFromClassId).maybeSingle(),
      isGraduation 
        ? Promise.resolve({ data: null, error: null })
        : adminClient.from('classes').select('id, name').eq('id', normalizedToClassId).maybeSingle(),
    ]);

    const { data: toClass, error: toClassError } = toClassResult;

    if (fromClassError) return res.status(400).json({ error: fromClassError.message });
    if (toClassError) return res.status(400).json({ error: toClassError.message });
    if (!fromClass) return res.status(404).json({ error: 'Source class not found' });
    if (!isGraduation && !toClass) return res.status(404).json({ error: 'Target class not found' });

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

    let targetClassRolls: any[] | null = null;
    let targetClassRollsError = null;

    if (!isGraduation) {
      const result = await adminClient
        .from('students')
        .select('roll_number')
        .eq('class_id', normalizedToClassId);
      targetClassRolls = result.data;
      targetClassRollsError = result.error;
    }

    if (targetClassRollsError) {
      return res.status(400).json({ error: targetClassRollsError.message });
    }

    let nextTargetClassRollNumber = getNextRollNumber(targetClassRolls || []);
    const promotedRollAssignments = new Map<string, string>();
    if (!isGraduation) {
      for (const student of promotedStudents) {
        promotedRollAssignments.set(student.id, String(nextTargetClassRollNumber));
        nextTargetClassRollNumber += 1;
      }
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
      const assignedRollNumber = isGraduation ? null : promotedRollAssignments.get(student.id);
      
      const updateData: any = isGraduation 
        ? { class_id: null, roll_number: null, status: 'graduated' }
        : { class_id: normalizedToClassId, roll_number: assignedRollNumber ?? null };

      const { error: updateError } = await adminClient
        .from('students')
        .update(updateData)
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
        to_class_id: isGraduation ? null : normalizedToClassId,
        from_roll_number: student.roll_number ?? null,
        to_roll_number: isGraduation ? null : (promotedRollAssignments.get(student.id) ?? null),
        result: isGraduation ? 'graduated' : 'promoted',
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

// POST /api/students/status/update — graduate or mark students as left (admin only)
studentsRouter.post('/status/update', requireAuth, async (req, res) => {
  try {
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const { studentIds, status, academicYear, notes } = req.body ?? {};

    if (!Array.isArray(studentIds) || studentIds.length === 0) {
      return res.status(400).json({ error: 'studentIds array is required and must not be empty' });
    }

    const validStatuses = ['graduated', 'left'];
    if (!validStatuses.includes(status)) {
      return res.status(400).json({ error: `status must be one of: ${validStatuses.join(', ')}` });
    }

    const normalizedAcademicYear = typeof academicYear === 'string' && academicYear.trim()
      ? academicYear.trim()
      : `${new Date().getFullYear()}-${new Date().getFullYear() + 1}`;

    const normalizedStudentIds = Array.from(
      new Set(
        studentIds
          .filter((id: unknown): id is string => typeof id === 'string')
          .map((id: string) => id.trim())
          .filter(Boolean)
      )
    );

    if (normalizedStudentIds.length === 0) {
      return res.status(400).json({ error: 'No valid student IDs provided' });
    }

    const adminClient = createAdminClient();

    // Fetch the students to verify they exist and are active
    const { data: studentsData, error: studentsError } = await adminClient
      .from('students')
      .select('id, class_id, roll_number, status')
      .in('id', normalizedStudentIds);

    if (studentsError) {
      return res.status(400).json({ error: studentsError.message });
    }

    const foundStudents = studentsData || [];
    const alreadyInactive = foundStudents.filter((s) => s.status !== 'active');
    if (alreadyInactive.length > 0) {
      return res.status(400).json({
        error: `${alreadyInactive.length} student(s) are already graduated or have left`,
      });
    }

    const missingIds = normalizedStudentIds.filter(
      (id) => !foundStudents.some((s) => s.id === id)
    );
    if (missingIds.length > 0) {
      return res.status(404).json({
        error: `${missingIds.length} student(s) not found`,
        missingIds,
      });
    }

    // Update status for all selected students
    const { error: updateError } = await adminClient
      .from('students')
      .update({
        status,
        class_id: null,      // Remove class assignment
        roll_number: null,   // Remove roll number
      })
      .in('id', normalizedStudentIds);

    if (updateError) {
      return res.status(400).json({ error: updateError.message });
    }

    // Insert audit records into student_promotions
    const auditRows = foundStudents.map((student) => ({
      student_id: student.id,
      from_class_id: student.class_id,
      to_class_id: null,
      from_roll_number: student.roll_number != null ? String(student.roll_number) : null,
      to_roll_number: null,
      result: status as string,
      academic_year: normalizedAcademicYear,
      promoted_by: req.userId,
      notes: notes || null,
    }));

    if (auditRows.length > 0) {
      const { error: auditError } = await adminClient
        .from('student_promotions')
        .insert(auditRows);
      if (auditError) {
        console.warn('[POST /api/students/status/update] Audit insert warning:', auditError.message);
        // Don't fail the request for audit errors — status is already updated
      }
    }

    console.log(`[POST /api/students/status/update] ${normalizedStudentIds.length} student(s) marked as '${status}'`);

    res.json({
      success: true,
      status,
      updatedCount: normalizedStudentIds.length,
      academicYear: normalizedAcademicYear,
    });
  } catch (err: any) {
    console.error('[POST /api/students/status/update] Error:', err);
    res.status(500).json({ error: err.message || 'Failed to update student status' });
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
        status,
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
      status: (studentData as any).status ?? 'active',
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

    const emailReady = isEmailConfigured();
    let emailSent = false;
    let emailError: string | null = emailReady ? null : 'Email service is not configured on the server.';

    if (emailReady) {
      try {
        const result = await sendStudentEnrollmentEmail({
          to: normalizedEmail,
          firstName,
          lastName,
          loginEmail: normalizedEmail,
          temporaryPassword: password,
        });
        
        emailSent = result.sent;
        if (!result.sent) {
          emailError = result.error || 'Failed to send email';
          console.error('[POST /api/students] Enrollment email failed:', result.error);
        } else {
          console.log('[POST /api/students] Enrollment email sent:', result.messageId);
        }
      } catch (err: any) {
        console.error('[POST /api/students] Enrollment email error:', err);
        emailError = err.message || 'Unknown email error';
      }
    }

    // Respond AFTER email finishes, because Vercel/serverless will kill the process
    // if we respond first.
    res.json({
      user: userData.user,
      emailSent,
      emailError,
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
      status,
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

    // Filter by status (defaults to 'active', use 'all' to see everyone)
    const statusFilter = (req.query.status as string) || 'active';
    if (statusFilter !== 'all') {
      query = query.eq('status', statusFilter);
    }

    if (req.query.class_id && req.query.class_id !== 'all') {
      if (req.query.class_id === 'unassigned') {
        query = query.is('class_id', null);
      } else {
        query = query.eq('class_id', req.query.class_id as string);
      }
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
