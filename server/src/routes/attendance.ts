import { Router } from 'express';
import { requireAuth } from '../middleware/auth.js';
import { createAdminClient } from '../lib/supabase.js';

export const attendanceRouter = Router();

// GET /api/attendance/teacher-classes — get classes assigned to the logged-in teacher
attendanceRouter.get('/teacher-classes', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();

    const { data: teacherData, error: teacherError } = await supabase
      .from('teachers')
      .select('id')
      .eq('user_id', req.userId)
      .maybeSingle();

    if (teacherError) return res.status(400).json({ error: teacherError.message });
    if (!teacherData) return res.status(404).json({ error: 'Teacher record not found' });

    let { data, error } = await supabase
      .from('teacher_subjects')
      .select('class_id, classes!class_id(id, name)')
      .eq('teacher_id', teacherData.id);

    if (error) {
      const fallback = await supabase
        .from('teacher_subjects')
        .select('class_id, classes(id, name)')
        .eq('teacher_id', teacherData.id);
      data = fallback.data;
      error = fallback.error;
    }

    if (error) return res.status(400).json({ error: error.message });

    const classMap = new Map();
    (data || []).forEach((item: any) => {
      const cls = item.classes;
      if (cls && !classMap.has(cls.id)) {
        classMap.set(cls.id, cls);
      }
    });

    res.json(Array.from(classMap.values()));
  } catch (err) {
    console.error('Teacher classes error:', err);
    res.status(500).json({ error: 'Failed to fetch teacher classes' });
  }
});

// GET /api/attendance/students?class_id=xxx
attendanceRouter.get('/students', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const classId = req.query.class_id as string;

    if (!classId) return res.status(400).json({ error: 'class_id is required' });

    // Try join with hint first, fallback to manual join
    let { data, error } = await supabase
      .from('students')
      .select('id, user_id, roll_number, profiles!user_id(first_name, last_name)')
      .eq('class_id', classId)
      .order('id');

    if (error) {
      // Fallback: fetch students then fetch profiles separately
      const { data: studentsData, error: studentsError } = await supabase
        .from('students')
        .select('id, user_id, roll_number')
        .eq('class_id', classId)
        .order('id');

      if (studentsError) return res.status(400).json({ error: studentsError.message });

      const userIds = (studentsData || []).map((s: any) => s.user_id);
      const { data: profilesData } = await supabase
        .from('profiles')
        .select('user_id, first_name, last_name')
        .in('user_id', userIds);

      const profileMap = new Map((profilesData || []).map((p: any) => [p.user_id, p]));
      data = (studentsData || []).map((s: any) => ({
        ...s,
        profiles: profileMap.get(s.user_id) || { first_name: 'Unknown', last_name: '' },
      }));
    }

    if (!data) data = [];
    res.json(data);
  } catch (err) {
    console.error('Fetch students error:', err);
    res.status(500).json({ error: 'Failed to fetch students' });
  }
});

// GET /api/attendance/check-class?class_id=xxx — check if attendance already marked for this class today
attendanceRouter.get('/check-class', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const classId = req.query.class_id as string;

    if (!classId) return res.status(400).json({ error: 'class_id is required' });

    const today = new Date().toISOString().split('T')[0];

    // Get all students in the class
    const { data: studentsInClass, error: studentsError } = await supabase
      .from('students')
      .select('id')
      .eq('class_id', classId);

    if (studentsError) return res.status(400).json({ error: studentsError.message });
    if (!studentsInClass || studentsInClass.length === 0) {
      return res.json({ markedToday: false, markedBy: null });
    }

    const studentIds = studentsInClass.map((s: any) => s.id);

    // Check if ANY student in this class has attendance marked today
    const { data: existingAttendance, error: attendanceError } = await supabase
      .from('student_attendance')
      .select('id, marked_by')
      .eq('date', today)
      .in('student_id', studentIds)
      .limit(1);

    if (attendanceError) return res.status(400).json({ error: attendanceError.message });

    const markedToday = (existingAttendance && existingAttendance.length > 0);
    const markedBy = markedToday ? existingAttendance[0].marked_by : null;
    const isMarkedByCurrentTeacher = markedBy === req.userId;

    res.json({ markedToday, markedBy, isMarkedByCurrentTeacher });
  } catch (err) {
    console.error('Check class attendance error:', err);
    res.status(500).json({ error: 'Failed to check attendance' });
  }
});

// GET /api/attendance/teacher-stats — get stats for the teacher dashboard
attendanceRouter.get('/teacher-stats', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();

    const { data: teacherData, error: teacherError } = await supabase
      .from('teachers')
      .select('id, department, hire_date')
      .eq('user_id', req.userId)
      .maybeSingle();

    if (teacherError) return res.status(400).json({ error: teacherError.message });
    if (!teacherData) return res.status(404).json({ error: 'Teacher record not found' });

    // Get assigned classes count
    const { data: classData } = await supabase
      .from('teacher_subjects')
      .select('class_id')
      .eq('teacher_id', teacherData.id);

    const uniqueClasses = new Set((classData || []).map((c: any) => c.class_id));

    // Get assigned subjects count
    const { data: subjectData } = await supabase
      .from('teacher_subjects')
      .select('subject_id')
      .eq('teacher_id', teacherData.id);

    const uniqueSubjects = new Set((subjectData || []).map((s: any) => s.subject_id));

    // Get total students across assigned classes
    let totalStudents = 0;
    if (uniqueClasses.size > 0) {
      const { count } = await supabase
        .from('students')
        .select('*', { count: 'exact', head: true })
        .in('class_id', Array.from(uniqueClasses));
      totalStudents = count ?? 0;
    }

    // Get today's attendance count
    const today = new Date().toISOString().split('T')[0];
    const { count: attendanceToday } = await supabase
      .from('student_attendance')
      .select('*', { count: 'exact', head: true })
      .eq('marked_by', req.userId)
      .eq('date', today);

    res.json({
      department: teacherData.department,
      hireDate: teacherData.hire_date,
      totalClasses: uniqueClasses.size,
      totalSubjects: uniqueSubjects.size,
      totalStudents,
      attendanceMarkedToday: attendanceToday ?? 0,
    });
  } catch (err) {
    console.error('Teacher stats error:', err);
    res.status(500).json({ error: 'Failed to fetch teacher stats' });
  }
});

// POST /api/attendance — save attendance records
attendanceRouter.post('/', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const { records, date } = req.body;

    // Check if ANY attendance is already marked for these students on this date
    if (records.length > 0) {
      const studentIds = records.map((r: { studentId: string }) => r.studentId);
      const { data: existingRecords } = await supabase
        .from('student_attendance')
        .select('student_id, marked_by')
        .eq('date', date)
        .in('student_id', studentIds);

      // If any attendance exists for any student on this date, reject all
      if (existingRecords && existingRecords.length > 0) {
        return res.status(403).json({
          error: 'Attendance has already been marked for today. Attendance cannot be changed once marked.'
        });
      }
    }

    const attendanceData = records.map((r: { studentId: string; status: string }) => ({
      student_id: r.studentId,
      status: r.status,
      date,
      marked_by: req.userId,
    }));

    // Use insert only (not upsert) to prevent updates
    const { error } = await supabase
      .from('student_attendance')
      .insert(attendanceData);

    if (error) {
      // Check if it's a unique constraint violation
      if (error.message.includes('duplicate') || error.message.includes('unique')) {
        return res.status(403).json({
          error: 'Attendance has already been marked for today. Attendance cannot be changed once marked.'
        });
      }
      return res.status(400).json({ error: error.message });
    }
    res.json({ success: true });
  } catch (err) {
    res.status(500).json({ error: 'Failed to save attendance' });
  }
});
