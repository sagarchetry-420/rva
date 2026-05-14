import { Router } from 'express';
import { requireAuth } from '../middleware/auth.js';
import { createAdminClient, isUserAdmin } from '../lib/supabase.js';

export const noticesRouter = Router();

// GET /api/notices/public — no auth, returns notices with target_audience='All'
noticesRouter.get('/public', async (_req, res) => {
  try {
    const supabase = createAdminClient();
    const { data, error } = await supabase
      .from('notices')
      .select('id, title, content, publish_date, document_url')
      .eq('target_audience', 'All')
      .lte('publish_date', new Date().toISOString())
      .order('publish_date', { ascending: false })
      .limit(6);

    if (error) return res.status(400).json({ error: error.message });
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch public notices' });
  }
});

// GET /api/notices/teacher — for teachers, returns notices for 'All' or 'Staff'
noticesRouter.get('/teacher', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const { data, error } = await supabase
      .from('notices')
      .select('id, title, content, publish_date, document_url')
      .in('target_audience', ['All', 'Staff'])
      .lte('publish_date', new Date().toISOString())
      .order('publish_date', { ascending: false })
      .limit(6);

    if (error) return res.status(400).json({ error: error.message });
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch notices' });
  }
});

// GET /api/notices/student — for students, returns notices for 'All' or 'Students'
noticesRouter.get('/student', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const { data, error } = await supabase
      .from('notices')
      .select('id, title, content, publish_date, document_url')
      .in('target_audience', ['All', 'Students'])
      .lte('publish_date', new Date().toISOString())
      .order('publish_date', { ascending: false })
      .limit(6);

    if (error) return res.status(400).json({ error: error.message });
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch notices' });
  }
});

// GET /api/notices (admin only - for management)
noticesRouter.get('/', requireAuth, async (req, res) => {
  try {
    // Verify user is admin
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Access denied. Admin only.' });
    }

    const supabase = createAdminClient();
    const { data, error } = await supabase
      .from('notices')
      .select('*')
      .order('created_at', { ascending: false });

    if (error) return res.status(400).json({ error: error.message });
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch notices' });
  }
});

// POST /api/notices (admin only)
noticesRouter.post('/', requireAuth, async (req, res) => {
  try {
    // Verify user is admin
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Only admins can post notices' });
    }

    const supabase = createAdminClient();
    const { title, content, targetAudience, documentUrl } = req.body;
    const { error } = await supabase.from('notices').insert([{
      title,
      content,
      target_audience: targetAudience,
      author_id: req.userId,
      document_url: documentUrl || null,
    }]);

    if (error) return res.status(400).json({ error: error.message });
    res.json({ success: true });
  } catch (err) {
    res.status(500).json({ error: 'Failed to create notice' });
  }
});

// DELETE /api/notices/:id (admin only)
noticesRouter.delete('/:id', requireAuth, async (req, res) => {
  try {
    // Verify user is admin
    const isAdmin = await isUserAdmin(req.userId!);
    if (!isAdmin) {
      return res.status(403).json({ error: 'Only admins can delete notices' });
    }

    const supabase = createAdminClient();
    const { error } = await supabase.from('notices').delete().eq('id', req.params.id);
    if (error) return res.status(400).json({ error: error.message });
    res.json({ success: true });
  } catch (err) {
    res.status(500).json({ error: 'Failed to delete notice' });
  }
});
