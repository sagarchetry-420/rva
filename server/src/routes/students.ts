import { Router } from 'express';
import { requireAuth } from '../middleware/auth.js';
import { createAdminClient, isUserAdmin } from '../lib/supabase.js';

export const studentsRouter = Router();

// GET /api/students?class_id=xxx (admin only)
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
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch students' });
  }
});

// GET /api/students/me — get the logged-in student's profile
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
        roll_number,
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
      rollNumber: (studentData as any).roll_number,
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

    const { data: userData, error: authError } = await admin.auth.admin.createUser({
      email,
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

    if (authError) return res.status(400).json({ error: authError.message });
    res.json({ user: userData.user });
  } catch (err) {
    res.status(500).json({ error: 'Failed to create student' });
  }
});

// GET /api/students/:id — get a single student's full details (admin only)
studentsRouter.get('/:id', requireAuth, async (req, res) => {
  try {
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
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
      .eq('id', req.params.id)
      .maybeSingle();

    if (studentError) return res.status(400).json({ error: studentError.message });
    if (!studentData) return res.status(404).json({ error: 'Student not found' });

    // Get user email from auth
    const { data: authUser, error: authError } = await supabase.auth.admin.getUserById(studentData.user_id);

    // Get attendance stats
    const { data: attendanceData } = await supabase
      .from('student_attendance')
      .select('status')
      .eq('student_id', studentData.id);

    const records = attendanceData || [];
    const totalDays = records.length;
    const presentDays = records.filter((r: any) => r.status === 'Present').length;
    const absentDays = records.filter((r: any) => r.status === 'Absent').length;
    const lateDays = records.filter((r: any) => r.status === 'Late').length;
    const attendancePercentage = totalDays > 0 ? Math.round(((presentDays + lateDays) / totalDays) * 100) : 0;

    const profile = studentData.profiles as any;
    const classInfo = studentData.classes as any;

    res.json({
      id: studentData.id,
      userId: studentData.user_id,
      firstName: profile?.first_name ?? '',
      lastName: profile?.last_name ?? '',
      email: authUser?.user?.email ?? '',
      avatarUrl: profile?.avatar_url,
      dob: profile?.dob,
      className: classInfo?.name ?? 'Not assigned',
      classId: classInfo?.id ?? null,
      rollNumber: (studentData as any).roll_number,
      enrollmentDate: studentData.enrollment_date,
      attendance: {
        totalDays,
        presentDays,
        absentDays,
        lateDays,
        attendancePercentage,
      }
    });
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
