import { Router } from 'express';
import { requireAuth } from '../middleware/auth.js';
import { createAdminClient, isUserAdmin } from '../lib/supabase.js';

export const dashboardRouter = Router();

dashboardRouter.get('/stats', requireAuth, async (req, res) => {
  try {
    // Verify user is admin
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    // Use admin client to get accurate counts (bypasses RLS)
    const supabase = createAdminClient();

    const [students, teachers, classes, notices, subjects, attendance] = await Promise.all([
      supabase.from('students').select('*', { count: 'exact', head: true }),
      supabase.from('teachers').select('*', { count: 'exact', head: true }),
      supabase.from('classes').select('*', { count: 'exact', head: true }),
      supabase.from('notices').select('*', { count: 'exact', head: true }),
      supabase.from('subjects').select('*', { count: 'exact', head: true }),
      supabase.from('student_attendance').select('*', { count: 'exact', head: true }),
    ]);

    const tableNames = ['students', 'teachers', 'classes', 'notices', 'subjects', 'attendance'] as const;
    const results = [students, teachers, classes, notices, subjects, attendance];
    const errors = results
      .map((r, i) => r.error ? `${tableNames[i]}: ${r.error.message}` : null)
      .filter(Boolean);

    if (errors.length > 0) {
      console.error('Dashboard query errors:', errors);
      return res.status(500).json({ error: 'Failed to fetch dashboard stats', details: errors });
    }

    res.json({
      totalStudents: students.count ?? 0,
      totalTeachers: teachers.count ?? 0,
      totalClasses: classes.count ?? 0,
      totalNotices: notices.count ?? 0,
      totalSubjects: subjects.count ?? 0,
      totalAttendanceRecords: attendance.count ?? 0,
    });
  } catch (err) {
    console.error('Dashboard stats error:', err);
    res.status(500).json({ error: 'Failed to fetch dashboard stats' });
  }
});

// Get top performing students
dashboardRouter.get('/top-students', requireAuth, async (req, res) => {
  try {
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const supabase = createAdminClient();
    
    // Get students with their attendance stats
    const { data: students, error } = await supabase
      .from('students')
      .select(`
        id,
        enrollment_date,
        profiles!inner(first_name, last_name, avatar_url),
        classes(name)
      `)
      .limit(10);

    if (error) {
      console.error('Top students query error:', error);
      return res.status(500).json({ error: 'Failed to fetch top students' });
    }

    // Get attendance stats for each student
    const studentsWithStats = await Promise.all(
      (students || []).map(async (student) => {
        const { count: totalDays } = await supabase
          .from('student_attendance')
          .select('*', { count: 'exact', head: true })
          .eq('student_id', student.id);

        const { count: presentDays } = await supabase
          .from('student_attendance')
          .select('*', { count: 'exact', head: true })
          .eq('student_id', student.id)
          .eq('status', 'Present');

        const attendancePercent = totalDays && totalDays > 0 
          ? Math.round((presentDays || 0) / totalDays * 100) 
          : 0;

        return {
          id: student.id,
          name: `${(student.profiles as any).first_name} ${(student.profiles as any).last_name}`,
          avatarUrl: (student.profiles as any).avatar_url,
          className: (student.classes as any)?.name || 'N/A',
          enrollmentDate: student.enrollment_date,
          attendancePercent,
          totalDays: totalDays || 0,
          presentDays: presentDays || 0,
        };
      })
    );

    // Sort by attendance percentage descending
    studentsWithStats.sort((a, b) => b.attendancePercent - a.attendancePercent);

    res.json(studentsWithStats.slice(0, 5));
  } catch (err) {
    console.error('Top students error:', err);
    res.status(500).json({ error: 'Failed to fetch top students' });
  }
});

// Get recent notices
dashboardRouter.get('/recent-notices', requireAuth, async (req, res) => {
  try {
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const supabase = createAdminClient();
    
    const { data: notices, error } = await supabase
      .from('notices')
      .select('id, title, content, publish_date, target_audience')
      .order('publish_date', { ascending: false })
      .limit(5);

    if (error) {
      console.error('Recent notices query error:', error);
      return res.status(500).json({ error: 'Failed to fetch recent notices' });
    }

    res.json(notices || []);
  } catch (err) {
    console.error('Recent notices error:', err);
    res.status(500).json({ error: 'Failed to fetch recent notices' });
  }
});

// Get class statistics (students per class)
dashboardRouter.get('/class-stats', requireAuth, async (req, res) => {
  try {
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const supabase = createAdminClient();
    
    const { data: classes, error } = await supabase
      .from('classes')
      .select(`
        id,
        name,
        school_levels(name)
      `);

    if (error) {
      console.error('Class stats query error:', error);
      return res.status(500).json({ error: 'Failed to fetch class stats' });
    }

    // Get student count for each class
    const classStats = await Promise.all(
      (classes || []).map(async (cls) => {
        const { count } = await supabase
          .from('students')
          .select('*', { count: 'exact', head: true })
          .eq('class_id', cls.id);

        return {
          id: cls.id,
          name: cls.name,
          schoolLevel: (cls.school_levels as any)?.name || 'N/A',
          studentCount: count || 0,
        };
      })
    );

    res.json(classStats);
  } catch (err) {
    console.error('Class stats error:', err);
    res.status(500).json({ error: 'Failed to fetch class stats' });
  }
});

// Get subjects list
dashboardRouter.get('/subjects', requireAuth, async (req, res) => {
  try {
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const supabase = createAdminClient();
    
    const { data: subjects, error } = await supabase
      .from('subjects')
      .select('id, name, code')
      .order('name');

    if (error) {
      console.error('Subjects query error:', error);
      return res.status(500).json({ error: 'Failed to fetch subjects' });
    }

    res.json(subjects || []);
  } catch (err) {
    console.error('Subjects error:', err);
    res.status(500).json({ error: 'Failed to fetch subjects' });
  }
});
