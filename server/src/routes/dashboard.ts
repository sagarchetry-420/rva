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

    const [students, teachers, classes, notices] = await Promise.all([
      supabase.from('students').select('*', { count: 'exact', head: true }),
      supabase.from('teachers').select('*', { count: 'exact', head: true }),
      supabase.from('classes').select('*', { count: 'exact', head: true }),
      supabase.from('notices').select('*', { count: 'exact', head: true }),
    ]);

    const tableNames = ['students', 'teachers', 'classes', 'notices'] as const;
    const results = [students, teachers, classes, notices];
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
    });
  } catch (err) {
    console.error('Dashboard stats error:', err);
    res.status(500).json({ error: 'Failed to fetch dashboard stats' });
  }
});
