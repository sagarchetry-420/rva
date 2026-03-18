import { Router } from 'express';
import { requireAuth } from '../middleware/auth.js';
import { createAdminClient } from '../lib/supabase.js';

export const routinesRouter = Router();

const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// GET /api/routines - get all routines with class, subject, and teacher info
routinesRouter.get('/', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const classId = req.query.class_id as string;

    let query = supabase
      .from('class_routines')
      .select(`
        id,
        day_of_week,
        start_time,
        end_time,
        room,
        class_id,
        subject_id,
        teacher_id,
        classes(id, name),
        subjects(id, name, code),
        teachers(id, user_id)
      `)
      .order('day_of_week', { ascending: true })
      .order('start_time', { ascending: true });

    if (classId) {
      query = query.eq('class_id', classId);
    }

    const { data, error } = await query;

    if (error) return res.status(400).json({ error: error.message });

    // Fetch teacher profiles
    const teacherIds = [...new Set((data || []).filter((r: any) => r.teachers?.user_id).map((r: any) => r.teachers.user_id))];

    let teacherProfiles: Record<string, any> = {};
    if (teacherIds.length > 0) {
      const { data: profiles } = await supabase
        .from('profiles')
        .select('user_id, first_name, last_name')
        .in('user_id', teacherIds);

      (profiles || []).forEach((p: any) => {
        teacherProfiles[p.user_id] = p;
      });
    }

    // Enrich data with teacher names
    const enrichedData = (data || []).map((item: any) => ({
      ...item,
      teacher_name: item.teachers?.user_id
        ? `${teacherProfiles[item.teachers.user_id]?.first_name || ''} ${teacherProfiles[item.teachers.user_id]?.last_name || ''}`.trim() || 'Unknown'
        : null
    }));

    res.json(enrichedData);
  } catch (err) {
    console.error('Fetch routines error:', err);
    res.status(500).json({ error: 'Failed to fetch routines' });
  }
});

// GET /api/routines/by-class/:classId - get routines for a specific class grouped by day
routinesRouter.get('/by-class/:classId', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const { classId } = req.params;

    const { data, error } = await supabase
      .from('class_routines')
      .select(`
        id,
        day_of_week,
        start_time,
        end_time,
        room,
        subjects(id, name, code),
        teachers(id, user_id)
      `)
      .eq('class_id', classId)
      .order('day_of_week', { ascending: true })
      .order('start_time', { ascending: true });

    if (error) return res.status(400).json({ error: error.message });

    // Fetch teacher profiles
    const teacherIds = [...new Set((data || []).filter((r: any) => r.teachers?.user_id).map((r: any) => r.teachers.user_id))];

    let teacherProfiles: Record<string, any> = {};
    if (teacherIds.length > 0) {
      const { data: profiles } = await supabase
        .from('profiles')
        .select('user_id, first_name, last_name')
        .in('user_id', teacherIds);

      (profiles || []).forEach((p: any) => {
        teacherProfiles[p.user_id] = p;
      });
    }

    // Group by day
    const grouped: Record<string, any[]> = {};
    DAYS.forEach((day, index) => {
      grouped[day] = [];
    });

    (data || []).forEach((item: any) => {
      const dayName = DAYS[item.day_of_week];
      grouped[dayName].push({
        ...item,
        teacher_name: item.teachers?.user_id
          ? `${teacherProfiles[item.teachers.user_id]?.first_name || ''} ${teacherProfiles[item.teachers.user_id]?.last_name || ''}`.trim() || 'Unknown'
          : null
      });
    });

    res.json(grouped);
  } catch (err) {
    console.error('Fetch routines by class error:', err);
    res.status(500).json({ error: 'Failed to fetch routines' });
  }
});

// POST /api/routines - create a new routine entry
routinesRouter.post('/', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const { classId, subjectId, teacherId, dayOfWeek, startTime, endTime, room } = req.body;

    if (!classId || !subjectId || dayOfWeek === undefined || !startTime || !endTime) {
      return res.status(400).json({ error: 'Missing required fields' });
    }

    const { data, error } = await supabase
      .from('class_routines')
      .insert({
        class_id: classId,
        subject_id: subjectId,
        teacher_id: teacherId || null,
        day_of_week: dayOfWeek,
        start_time: startTime,
        end_time: endTime,
        room: room || null
      })
      .select()
      .single();

    if (error) {
      if (error.code === '23505') {
        return res.status(400).json({ error: 'A class already has a routine at this time slot' });
      }
      return res.status(400).json({ error: error.message });
    }
    res.json(data);
  } catch (err) {
    console.error('Create routine error:', err);
    res.status(500).json({ error: 'Failed to create routine' });
  }
});

// DELETE /api/routines/:id - delete a routine entry
routinesRouter.delete('/:id', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const { id } = req.params;

    const { error } = await supabase
      .from('class_routines')
      .delete()
      .eq('id', id);

    if (error) return res.status(400).json({ error: error.message });
    res.json({ success: true });
  } catch (err) {
    console.error('Delete routine error:', err);
    res.status(500).json({ error: 'Failed to delete routine' });
  }
});
