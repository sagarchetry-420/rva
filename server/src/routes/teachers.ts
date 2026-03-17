import { Router } from 'express';
import { requireAuth } from '../middleware/auth.js';
import { createAdminClient, isUserAdmin } from '../lib/supabase.js';

export const teachersRouter = Router();

// GET /api/teachers (admin only)
teachersRouter.get('/', requireAuth, async (req, res) => {
  try {
    // Verify user is admin
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const supabase = createAdminClient();
    const { data, error } = await supabase
      .from('teachers')
      .select(`
        id,
        department,
        hire_date,
        profiles!inner (
          first_name,
          last_name
        )
      `)
      .order('hire_date', { ascending: false });

    if (error) return res.status(400).json({ error: error.message });
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch teachers' });
  }
});

// POST /api/teachers — admin creates a teacher account
teachersRouter.post('/', requireAuth, async (req, res) => {
  try {
    // Verify user is admin
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const admin = createAdminClient();
    const { email, password, firstName, lastName, department, classId, subjectId, hireDate } = req.body;

    const { data: userData, error: authError } = await admin.auth.admin.createUser({
      email,
      password,
      email_confirm: true,
      user_metadata: {
        first_name: firstName,
        last_name: lastName,
        role: 'teacher',
        department,
        class_id: classId,
        subject_id: subjectId,
        hire_date: hireDate
      }
    });

    if (authError) return res.status(400).json({ error: authError.message });
    res.json({ user: userData.user });
  } catch (err) {
    res.status(500).json({ error: 'Failed to create teacher' });
  }
});

// DELETE /api/teachers/:id (admin only)
teachersRouter.delete('/:id', requireAuth, async (req, res) => {
  try {
    // Verify user is admin
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const supabase = createAdminClient();
    const { error } = await supabase.from('teachers').delete().eq('id', req.params.id);
    if (error) return res.status(400).json({ error: error.message });
    res.json({ success: true });
  } catch (err) {
    res.status(500).json({ error: 'Failed to delete teacher' });
  }
});
