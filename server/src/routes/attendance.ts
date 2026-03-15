import { Router } from 'express';
import { requireAuth } from '../middleware/auth.js';
import { createUserClient } from '../lib/supabase.js';

export const attendanceRouter = Router();

// GET /api/attendance/teacher-classes — get classes assigned to the logged-in teacher
attendanceRouter.get('/teacher-classes', requireAuth, async (req, res) => {
  try {
    const supabase = createUserClient(req.accessToken!);

    const { data: teacherData, error: teacherError } = await supabase
      .from('teachers')
      .select('id')
      .eq('user_id', req.userId)
      .maybeSingle();

    if (teacherError) return res.status(400).json({ error: teacherError.message });
    if (!teacherData) return res.status(404).json({ error: 'Teacher record not found' });

    let { data, error } = await supabase
      .from('teacher_subjects')
      .select('class_id, classes(id, name)')
      .eq('teacher_id', teacherData.id);

    if (error) {
      const fallback = await supabase
        .from('teacher_subjects')
        .select('class_id, classes!class_id(id, name)')
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
    res.status(500).json({ error: 'Failed to fetch teacher classes' });
  }
});

// GET /api/attendance/students?class_id=xxx
attendanceRouter.get('/students', requireAuth, async (req, res) => {
  try {
    const supabase = createUserClient(req.accessToken!);
    const classId = req.query.class_id as string;

    if (!classId) return res.status(400).json({ error: 'class_id is required' });

    const { data, error } = await supabase
      .from('students')
      .select('id, profiles (first_name, last_name)')
      .eq('class_id', classId);

    if (error) return res.status(400).json({ error: error.message });
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch students' });
  }
});

// POST /api/attendance — save attendance records
attendanceRouter.post('/', requireAuth, async (req, res) => {
  try {
    const supabase = createUserClient(req.accessToken!);
    const { records, date } = req.body;

    const attendanceData = records.map((r: { studentId: string; status: string }) => ({
      student_id: r.studentId,
      status: r.status,
      date,
      marked_by: req.userId,
    }));

    const { error } = await supabase
      .from('student_attendance')
      .upsert(attendanceData, { onConflict: 'student_id, date' });

    if (error) return res.status(400).json({ error: error.message });
    res.json({ success: true });
  } catch (err) {
    res.status(500).json({ error: 'Failed to save attendance' });
  }
});
