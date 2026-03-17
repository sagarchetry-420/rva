import { Router } from 'express';
import { requireAuth } from '../middleware/auth.js';
import { createUserClient, createAdminClient } from '../lib/supabase.js';

export const noticesRouter = Router();

// GET /api/notices/public — no auth, returns notices with target_audience='All'
noticesRouter.get('/public', async (_req, res) => {
  try {
    const supabase = createAdminClient();
    const { data, error } = await supabase
      .from('notices')
      .select('id, title, content, publish_date')
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

// GET /api/notices
noticesRouter.get('/', requireAuth, async (req, res) => {
  try {
    const supabase = createUserClient(req.accessToken!);
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
    // Check that the user is an admin
    const adminSupa = createAdminClient();
    const { data: roleData } = await adminSupa
      .from('user_roles')
      .select('role')
      .eq('user_id', req.userId)
      .maybeSingle();

    if (roleData?.role !== 'admin') {
      return res.status(403).json({ error: 'Only admins can post notices' });
    }

    const supabase = createUserClient(req.accessToken!);
    const { title, content, targetAudience } = req.body;
    const { error } = await supabase.from('notices').insert([{
      title,
      content,
      target_audience: targetAudience,
      author_id: req.userId,
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
    const adminSupa = createAdminClient();
    const { data: roleData } = await adminSupa
      .from('user_roles')
      .select('role')
      .eq('user_id', req.userId)
      .maybeSingle();

    if (roleData?.role !== 'admin') {
      return res.status(403).json({ error: 'Only admins can delete notices' });
    }

    const supabase = createUserClient(req.accessToken!);
    const { error } = await supabase.from('notices').delete().eq('id', req.params.id);
    if (error) return res.status(400).json({ error: error.message });
    res.json({ success: true });
  } catch (err) {
    res.status(500).json({ error: 'Failed to delete notice' });
  }
});
