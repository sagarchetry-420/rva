import { Router } from 'express';
import { requireAuth } from '../middleware/auth.js';
import { createAdminClient } from '../lib/supabase.js';

export const examsRouter = Router();

// GET /api/exams - get all exams with nested exam_subjects
examsRouter.get('/', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const classId = req.query.class_id as string;

    let query = supabase
      .from('exams')
      .select(`
        id,
        name,
        description,
        class_id,
        created_at,
        classes(id, name),
        exam_subjects(
          id,
          subject_id,
          exam_date,
          start_time,
          end_time,
          total_marks,
          passing_marks,
          subjects(id, name, code)
        )
      `)
      .order('created_at', { ascending: false });

    if (classId) {
      query = query.eq('class_id', classId);
    }

    const { data, error } = await query;

    if (error) return res.status(400).json({ error: error.message });

    // Sort exam_subjects by exam_date within each exam
    const sorted = (data || []).map((exam: any) => ({
      ...exam,
      exam_subjects: (exam.exam_subjects || []).sort(
        (a: any, b: any) => new Date(a.exam_date).getTime() - new Date(b.exam_date).getTime()
      )
    }));

    res.json(sorted);
  } catch (err) {
    console.error('Fetch exams error:', err);
    res.status(500).json({ error: 'Failed to fetch exams' });
  }
});

// GET /api/exams/schedule - get all scheduled exams (past and upcoming)
examsRouter.get('/schedule', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const classId = req.query.class_id as string;

    // Get all exam_subjects, with parent exam + subject info
    let query = supabase
      .from('exam_subjects')
      .select(`
        id,
        exam_date,
        start_time,
        end_time,
        total_marks,
        passing_marks,
        subjects(id, name, code),
        exams!inner(id, name, class_id, classes(id, name))
      `)
      .order('exam_date', { ascending: false })
      .limit(50);

    if (classId) {
      query = query.eq('exams.class_id', classId);
    }

    const { data, error } = await query;

    if (error) return res.status(400).json({ error: error.message });
    res.json(data || []);
  } catch (err) {
    console.error('Fetch upcoming exams error:', err);
    res.status(500).json({ error: 'Failed to fetch upcoming exams' });
  }
});

// GET /api/exams/:id - get a single exam with all subjects
examsRouter.get('/:id', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const { id } = req.params;

    const { data, error } = await supabase
      .from('exams')
      .select(`
        id,
        name,
        description,
        class_id,
        created_at,
        classes(id, name),
        exam_subjects(
          id,
          subject_id,
          exam_date,
          start_time,
          end_time,
          total_marks,
          passing_marks,
          subjects(id, name, code)
        )
      `)
      .eq('id', id)
      .single();

    if (error) return res.status(400).json({ error: error.message });
    if (!data) return res.status(404).json({ error: 'Exam not found' });

    res.json(data);
  } catch (err) {
    console.error('Fetch exam error:', err);
    res.status(500).json({ error: 'Failed to fetch exam' });
  }
});

// POST /api/exams - create a new exam with all subjects
examsRouter.post('/', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const { name, classId, description, subjects } = req.body;

    if (!name || !classId || !subjects || !Array.isArray(subjects) || subjects.length === 0) {
      return res.status(400).json({ error: 'Name, class, and at least one subject are required' });
    }

    // Validate each subject entry
    for (const subj of subjects) {
      if (!subj.subjectId || !subj.examDate || !subj.startTime || !subj.endTime) {
        return res.status(400).json({ error: 'Each subject must have subjectId, examDate, startTime, and endTime' });
      }
    }

    // 1. Create the parent exam
    const { data: exam, error: examError } = await supabase
      .from('exams')
      .insert({
        name,
        class_id: classId,
        description: description || null
      })
      .select()
      .single();

    if (examError) return res.status(400).json({ error: examError.message });

    // 2. Create all exam_subjects
    const examSubjects = subjects.map((s: any) => ({
      exam_id: exam.id,
      subject_id: s.subjectId,
      exam_date: s.examDate,
      start_time: s.startTime,
      end_time: s.endTime,
      total_marks: s.totalMarks || 100,
      passing_marks: s.passingMarks || 33
    }));

    const { error: subjectsError } = await supabase
      .from('exam_subjects')
      .insert(examSubjects);

    if (subjectsError) {
      // Rollback: delete the parent exam
      await supabase.from('exams').delete().eq('id', exam.id);
      return res.status(400).json({ error: subjectsError.message });
    }

    // 3. Fetch the complete exam with subjects
    const { data: completeExam, error: fetchError } = await supabase
      .from('exams')
      .select(`
        id,
        name,
        description,
        class_id,
        created_at,
        classes(id, name),
        exam_subjects(
          id,
          subject_id,
          exam_date,
          start_time,
          end_time,
          total_marks,
          passing_marks,
          subjects(id, name, code)
        )
      `)
      .eq('id', exam.id)
      .single();

    if (fetchError) return res.status(400).json({ error: fetchError.message });
    res.json(completeExam);
  } catch (err) {
    console.error('Create exam error:', err);
    res.status(500).json({ error: 'Failed to create exam' });
  }
});

// DELETE /api/exams/:id - delete an exam (cascades to exam_subjects)
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
