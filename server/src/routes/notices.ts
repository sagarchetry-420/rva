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

// POST /api/notices
noticesRouter.post('/', requireAuth, async (req, res) => {
  try {
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
