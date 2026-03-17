import { Router } from 'express';
import { requireAuth } from '../middleware/auth.js';
import { createAdminClient, isUserAdmin } from '../lib/supabase.js';

export const classesRouter = Router();

// GET /api/classes (all authenticated users can read)
classesRouter.get('/', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const { data, error } = await supabase
      .from('classes')
      .select('*, school_levels(name)')
      .order('name');

    if (error) return res.status(400).json({ error: error.message });
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch classes' });
  }
});

// GET /api/classes/simple — id and name only (for dropdowns)
classesRouter.get('/simple', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const { data, error } = await supabase
      .from('classes')
      .select('id, name')
      .order('name');

    if (error) return res.status(400).json({ error: error.message });
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch classes' });
  }
});

// POST /api/classes (admin only)
classesRouter.post('/', requireAuth, async (req, res) => {
  try {
    // Verify user is admin
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const supabase = createAdminClient();
    const { name, schoolLevelId } = req.body;
    const { error } = await supabase
      .from('classes')
      .insert([{ name, school_level_id: schoolLevelId }]);

    if (error) return res.status(400).json({ error: error.message });
    res.json({ success: true });
  } catch (err) {
    res.status(500).json({ error: 'Failed to create class' });
  }
});

// DELETE /api/classes/:id (admin only)
classesRouter.delete('/:id', requireAuth, async (req, res) => {
  try {
    // Verify user is admin
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const supabase = createAdminClient();
    const { error } = await supabase.from('classes').delete().eq('id', req.params.id);
    if (error) return res.status(400).json({ error: error.message });
    res.json({ success: true });
  } catch (err) {
    res.status(500).json({ error: 'Failed to delete class' });
  }
});

// GET /api/classes/school-levels (all authenticated users can read)
classesRouter.get('/school-levels', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const { data, error } = await supabase
      .from('school_levels')
      .select('id, name')
      .order('name');

    if (error) return res.status(400).json({ error: error.message });
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch school levels' });
  }
});

// POST /api/classes/school-levels (admin only)
classesRouter.post('/school-levels', requireAuth, async (req, res) => {
  try {
    // Verify user is admin
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const supabase = createAdminClient();
    const { name, description } = req.body;
    const { error } = await supabase
      .from('school_levels')
      .insert([{ name, description }]);

    if (error) return res.status(400).json({ error: error.message });
    res.json({ success: true });
  } catch (err) {
    res.status(500).json({ error: 'Failed to create school level' });
  }
});

// DELETE /api/classes/school-levels/:id (admin only)
classesRouter.delete('/school-levels/:id', requireAuth, async (req, res) => {
  try {
    // Verify user is admin
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const supabase = createAdminClient();
    const { error } = await supabase.from('school_levels').delete().eq('id', req.params.id);
    if (error) return res.status(400).json({ error: error.message });
    res.json({ success: true });
  } catch (err) {
    res.status(500).json({ error: 'Failed to delete school level' });
  }
});

// GET /api/classes/subjects (all authenticated users can read)
classesRouter.get('/subjects', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const { data, error } = await supabase
      .from('subjects')
      .select('*')
      .order('name');

    if (error) return res.status(400).json({ error: error.message });
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch subjects' });
  }
});

// POST /api/classes/subjects (admin only)
classesRouter.post('/subjects', requireAuth, async (req, res) => {
  try {
    // Verify user is admin
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const supabase = createAdminClient();
    const { name, code } = req.body;
    const { error } = await supabase
      .from('subjects')
      .insert([{ name, code }]);

    if (error) return res.status(400).json({ error: error.message });
    res.json({ success: true });
  } catch (err) {
    res.status(500).json({ error: 'Failed to create subject' });
  }
});

// DELETE /api/classes/subjects/:id (admin only)
classesRouter.delete('/subjects/:id', requireAuth, async (req, res) => {
  try {
    // Verify user is admin
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const supabase = createAdminClient();
    const { error } = await supabase.from('subjects').delete().eq('id', req.params.id);
    if (error) return res.status(400).json({ error: error.message });
    res.json({ success: true });
  } catch (err) {
    res.status(500).json({ error: 'Failed to delete subject' });
  }
});
