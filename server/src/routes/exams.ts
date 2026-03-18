import { Router } from 'express';
import { requireAuth } from '../middleware/auth.js';
import { createAdminClient } from '../lib/supabase.js';

export const examsRouter = Router();

// GET /api/exams - get all exams with class and subject info
examsRouter.get('/', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const classId = req.query.class_id as string;

    let query = supabase
      .from('exams')
      .select(`
        id,
        name,
        exam_date,
        start_time,
        end_time,
        total_marks,
        description,
        class_id,
        subject_id,
        classes(id, name),
        subjects(id, name, code)
      `)
      .order('exam_date', { ascending: true });

    if (classId) {
      query = query.eq('class_id', classId);
    }

    const { data, error } = await query;

    if (error) return res.status(400).json({ error: error.message });
    res.json(data || []);
  } catch (err) {
    console.error('Fetch exams error:', err);
    res.status(500).json({ error: 'Failed to fetch exams' });
  }
});

// GET /api/exams/upcoming - get upcoming exams
examsRouter.get('/upcoming', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const today = new Date().toISOString().split('T')[0];

    const { data, error } = await supabase
      .from('exams')
      .select(`
        id,
        name,
        exam_date,
        start_time,
        end_time,
        total_marks,
        classes(id, name),
        subjects(id, name, code)
      `)
      .gte('exam_date', today)
      .order('exam_date', { ascending: true })
      .limit(10);

    if (error) return res.status(400).json({ error: error.message });
    res.json(data || []);
  } catch (err) {
    console.error('Fetch upcoming exams error:', err);
    res.status(500).json({ error: 'Failed to fetch upcoming exams' });
  }
});

// POST /api/exams - create a new exam
examsRouter.post('/', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const { name, classId, subjectId, examDate, startTime, endTime, totalMarks, description } = req.body;

    if (!name || !classId || !subjectId || !examDate || !startTime || !endTime) {
      return res.status(400).json({ error: 'Missing required fields' });
    }

    const { data, error } = await supabase
      .from('exams')
      .insert({
        name,
        class_id: classId,
        subject_id: subjectId,
        exam_date: examDate,
        start_time: startTime,
        end_time: endTime,
        total_marks: totalMarks || 100,
        description: description || null
      })
      .select()
      .single();

    if (error) return res.status(400).json({ error: error.message });
    res.json(data);
  } catch (err) {
    console.error('Create exam error:', err);
    res.status(500).json({ error: 'Failed to create exam' });
  }
});

// DELETE /api/exams/:id - delete an exam
examsRouter.delete('/:id', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const { id } = req.params;

    const { error } = await supabase
      .from('exams')
      .delete()
      .eq('id', id);

    if (error) return res.status(400).json({ error: error.message });
    res.json({ success: true });
  } catch (err) {
    console.error('Delete exam error:', err);
    res.status(500).json({ error: 'Failed to delete exam' });
  }
});
