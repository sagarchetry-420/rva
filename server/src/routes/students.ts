import { Router } from 'express';
import { requireAuth } from '../middleware/auth.js';
import { createAdminClient, createUserClient } from '../lib/supabase.js';

export const studentsRouter = Router();

// GET /api/students?class_id=xxx
studentsRouter.get('/', requireAuth, async (req, res) => {
  try {
    const supabase = createUserClient(req.accessToken!);
    let query = supabase.from('students').select(`
      id,
      user_id,
      enrollment_date,
      profiles!inner (
        first_name,
        last_name,
        avatar_url
      ),
      classes (
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

// POST /api/students — admin creates a student account
studentsRouter.post('/', requireAuth, async (req, res) => {
  try {
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

// DELETE /api/students/:id
studentsRouter.delete('/:id', requireAuth, async (req, res) => {
  try {
    const supabase = createUserClient(req.accessToken!);
    const { error } = await supabase.from('students').delete().eq('id', req.params.id);
    if (error) return res.status(400).json({ error: error.message });
    res.json({ success: true });
  } catch (err) {
    res.status(500).json({ error: 'Failed to delete student' });
  }
});
