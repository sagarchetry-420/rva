import { Router } from 'express';
import { requireAuth } from '../middleware/auth.js';
import { createAdminClient } from '../lib/supabase.js';

export const resultsRouter = Router();

// GET /api/results/exam/:examId — full results for an exam (admin/teacher view)
resultsRouter.get('/exam/:examId', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const { examId } = req.params;

    // 1. Get the exam with its subjects
    const { data: exam, error: examError } = await supabase
      .from('exams')
      .select(`
        id, name, description, class_id, is_final,
        classes(id, name),
        exam_subjects(
          id, subject_id, exam_date, start_time, end_time, total_marks, passing_marks,
          subjects(id, name, code)
        )
      `)
      .eq('id', examId)
      .single();

    if (examError || !exam) {
      return res.status(404).json({ error: 'Exam not found' });
    }

    // 2. Get all students in this class
    const { data: students, error: studentsError } = await supabase
      .from('students')
      .select(`
        id, roll_number,
        profiles(first_name, last_name)
      `)
      .eq('class_id', exam.class_id)
      .order('roll_number', { ascending: true });

    if (studentsError) {
      return res.status(400).json({ error: studentsError.message });
    }

    // 3. Get all results for this exam's subjects
    const examSubjectIds = (exam.exam_subjects || []).map((es: any) => es.id);
    let results: any[] = [];
    if (examSubjectIds.length > 0) {
      const { data: resultsData, error: resultsError } = await supabase
        .from('exam_results')
        .select('*')
        .in('exam_subject_id', examSubjectIds);

      if (resultsError) {
        return res.status(400).json({ error: resultsError.message });
      }
      results = resultsData || [];
    }

    // 4. Build student-wise result map
    const studentResults = (students || []).map((student: any) => {
      const subjectResults = (exam.exam_subjects || []).map((es: any) => {
        const result = results.find(
          (r: any) => r.exam_subject_id === es.id && r.student_id === student.id
        );
        const marksObtained = result ? result.marks_obtained : null;
        const isAbsent = result ? result.is_absent : false;
        const passed = result
          ? (!isAbsent && marksObtained !== null && marksObtained >= es.passing_marks)
          : false;
        return {
          examSubjectId: es.id,
          subjectName: es.subjects?.name || 'N/A',
          subjectCode: es.subjects?.code || '',
          totalMarks: es.total_marks,
          passingMarks: es.passing_marks,
          marksObtained,
          isAbsent,
          passed,
          remarks: result?.remarks || null,
          hasResult: !!result,
        };
      });

      const totalMarks = subjectResults.reduce((sum: number, s: any) => sum + s.totalMarks, 0);
      const totalObtained = subjectResults.reduce((sum: number, s: any) => sum + (s.marksObtained || 0), 0);
      const allResultsEntered = subjectResults.every((s: any) => s.hasResult);
      const overallPassed = allResultsEntered && subjectResults.every((s: any) => s.passed);
      const percentage = totalMarks > 0 ? ((totalObtained / totalMarks) * 100).toFixed(1) : '0.0';

      return {
        studentId: student.id,
        rollNumber: student.roll_number,
        firstName: student.profiles?.first_name || '',
        lastName: student.profiles?.last_name || '',
        subjects: subjectResults,
        totalMarks,
        totalObtained,
        percentage: parseFloat(percentage),
        overallPassed,
        allResultsEntered,
      };
    });

    res.json({
      exam: {
        id: exam.id,
        name: exam.name,
        description: exam.description,
        isFinal: exam.is_final,
        className: (exam.classes as any)?.name || 'N/A',
        classId: exam.class_id,
      },
      subjects: (exam.exam_subjects || []).map((es: any) => ({
        id: es.id,
        name: es.subjects?.name || 'N/A',
        code: es.subjects?.code || '',
        totalMarks: es.total_marks,
        passingMarks: es.passing_marks,
      })),
      students: studentResults,
    });
  } catch (err) {
    console.error('Fetch exam results error:', err);
    res.status(500).json({ error: 'Failed to fetch results' });
  }
});

// GET /api/results/exam-subjects/:examSubjectId — marks for a specific subject (teacher entry view)
resultsRouter.get('/exam-subjects/:examSubjectId', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const { examSubjectId } = req.params;

    // Get exam_subject with parent exam info
    const { data: examSubject, error: esError } = await supabase
      .from('exam_subjects')
      .select(`
        id, subject_id, exam_date, total_marks, passing_marks,
        subjects(id, name, code),
        exams(id, name, class_id, classes(id, name))
      `)
      .eq('id', examSubjectId)
      .single();

    if (esError || !examSubject) {
      return res.status(404).json({ error: 'Exam subject not found' });
    }

    // Get students in this class
    const classId = (examSubject as any).exams?.class_id;
    const { data: students } = await supabase
      .from('students')
      .select('id, roll_number, profiles(first_name, last_name)')
      .eq('class_id', classId)
      .order('roll_number', { ascending: true });

    // Get existing results
    const { data: existingResults } = await supabase
      .from('exam_results')
      .select('*')
      .eq('exam_subject_id', examSubjectId);

    const studentMarks = (students || []).map((student: any) => {
      const result = (existingResults || []).find((r: any) => r.student_id === student.id);
      return {
        studentId: student.id,
        rollNumber: student.roll_number,
        firstName: student.profiles?.first_name || '',
        lastName: student.profiles?.last_name || '',
        marksObtained: result?.marks_obtained ?? null,
        isAbsent: result?.is_absent ?? false,
        remarks: result?.remarks ?? '',
        hasResult: !!result,
      };
    });

    res.json({
      examSubject: {
        id: examSubject.id,
        subjectName: (examSubject as any).subjects?.name || 'N/A',
        subjectCode: (examSubject as any).subjects?.code || '',
        examDate: examSubject.exam_date,
        totalMarks: examSubject.total_marks,
        passingMarks: examSubject.passing_marks,
        examName: (examSubject as any).exams?.name || 'N/A',
        className: (examSubject as any).exams?.classes?.name || 'N/A',
      },
      students: studentMarks,
    });
  } catch (err) {
    console.error('Fetch exam subject marks error:', err);
    res.status(500).json({ error: 'Failed to fetch marks' });
  }
});

// POST /api/results/exam-subjects/:examSubjectId — bulk upsert marks
resultsRouter.post('/exam-subjects/:examSubjectId', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const { examSubjectId } = req.params;
    const { marks } = req.body; // Array of { studentId, marksObtained, isAbsent, remarks }

    if (!Array.isArray(marks) || marks.length === 0) {
      return res.status(400).json({ error: 'marks array is required' });
    }

    // Verify the exam_subject exists and get total_marks for validation
    const { data: examSubject, error: esError } = await supabase
      .from('exam_subjects')
      .select('id, total_marks, subject_id, exams(class_id)')
      .eq('id', examSubjectId)
      .single();

    if (esError || !examSubject) {
      return res.status(404).json({ error: 'Exam subject not found' });
    }

    // Verify the teacher is assigned to this subject+class
    const { data: teacherRecord } = await supabase
      .from('teachers')
      .select('id')
      .eq('user_id', req.userId)
      .single();

    if (teacherRecord) {
      const { data: assignment } = await supabase
        .from('teacher_subjects')
        .select('id')
        .eq('teacher_id', teacherRecord.id)
        .eq('subject_id', examSubject.subject_id)
        .eq('class_id', (examSubject as any).exams?.class_id)
        .maybeSingle();

      // Allow if teacher is assigned OR if user is admin
      const { data: roleCheck } = await supabase
        .from('user_roles')
        .select('role')
        .eq('user_id', req.userId)
        .eq('role', 'admin')
        .maybeSingle();

      if (!assignment && !roleCheck) {
        return res.status(403).json({ error: 'You are not assigned to this subject for this class' });
      }
    }

    // Validate marks
    for (const m of marks) {
      if (!m.studentId) {
        return res.status(400).json({ error: 'Each entry must have a studentId' });
      }
      if (!m.isAbsent && (m.marksObtained < 0 || m.marksObtained > examSubject.total_marks)) {
        return res.status(400).json({
          error: `Marks must be between 0 and ${examSubject.total_marks}`
        });
      }
    }

    // Upsert results
    const upsertData = marks.map((m: any) => ({
      exam_subject_id: examSubjectId,
      student_id: m.studentId,
      marks_obtained: m.isAbsent ? 0 : (m.marksObtained || 0),
      is_absent: m.isAbsent || false,
      remarks: m.remarks || null,
      entered_by: req.userId,
      updated_at: new Date().toISOString(),
    }));

    const { error: upsertError } = await supabase
      .from('exam_results')
      .upsert(upsertData, {
        onConflict: 'exam_subject_id,student_id',
      });

    if (upsertError) {
      console.error('Upsert marks error:', upsertError);
      return res.status(400).json({ error: upsertError.message });
    }

    res.json({ success: true, count: marks.length });
  } catch (err) {
    console.error('Save marks error:', err);
    res.status(500).json({ error: 'Failed to save marks' });
  }
});

// GET /api/results/student/:studentId — all results for a student
resultsRouter.get('/student/:studentId', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const { studentId } = req.params;

    // Get all results for this student, joined with exam_subjects and exams
    const { data: results, error } = await supabase
      .from('exam_results')
      .select(`
        id, marks_obtained, is_absent, remarks,
        exam_subjects(
          id, exam_date, total_marks, passing_marks,
          subjects(id, name, code),
          exams(id, name, is_final, class_id, classes(id, name))
        )
      `)
      .eq('student_id', studentId);

    if (error) {
      return res.status(400).json({ error: error.message });
    }

    // Group results by exam
    const examMap = new Map<string, any>();
    for (const result of (results || [])) {
      const es = result.exam_subjects as any;
      const exam = es?.exams;
      if (!exam) continue;

      if (!examMap.has(exam.id)) {
        examMap.set(exam.id, {
          examId: exam.id,
          examName: exam.name,
          isFinal: exam.is_final,
          className: exam.classes?.name || 'N/A',
          subjects: [],
        });
      }

      const entry = examMap.get(exam.id)!;
      const passed = !result.is_absent && result.marks_obtained >= es.passing_marks;
      entry.subjects.push({
        subjectName: es.subjects?.name || 'N/A',
        subjectCode: es.subjects?.code || '',
        totalMarks: es.total_marks,
        passingMarks: es.passing_marks,
        marksObtained: result.marks_obtained,
        isAbsent: result.is_absent,
        passed,
        examDate: es.exam_date,
      });
    }

    // Compute totals per exam
    const examResults = Array.from(examMap.values()).map((exam: any) => {
      const totalMarks = exam.subjects.reduce((s: number, sub: any) => s + sub.totalMarks, 0);
      const totalObtained = exam.subjects.reduce((s: number, sub: any) => s + sub.marksObtained, 0);
      const percentage = totalMarks > 0 ? ((totalObtained / totalMarks) * 100).toFixed(1) : '0.0';
      const overallPassed = exam.subjects.every((sub: any) => sub.passed);

      return {
        ...exam,
        totalMarks,
        totalObtained,
        percentage: parseFloat(percentage),
        overallPassed,
      };
    });

    res.json(examResults);
  } catch (err) {
    console.error('Fetch student results error:', err);
    res.status(500).json({ error: 'Failed to fetch student results' });
  }
});

// GET /api/results/my-assigned-subjects — teacher's assigned exam subjects with pending marks
resultsRouter.get('/my-assigned-subjects', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();

    // Get teacher record
    const { data: teacher } = await supabase
      .from('teachers')
      .select('id')
      .eq('user_id', req.userId)
      .single();

    if (!teacher) {
      return res.status(404).json({ error: 'Teacher record not found' });
    }

    // Get teacher's subject-class assignments
    const { data: assignments } = await supabase
      .from('teacher_subjects')
      .select('subject_id, class_id, subjects(id, name, code), classes(id, name)')
      .eq('teacher_id', teacher.id);

    if (!assignments || assignments.length === 0) {
      return res.json([]);
    }

    // Build unique subject_ids and class_ids
    const subjectIds = [...new Set(assignments.map(a => a.subject_id))];
    const classIds = [...new Set(assignments.map(a => a.class_id))];

    // Single query: get ALL exam_subjects for these subjects in these classes
    const { data: allExamSubjects } = await supabase
      .from('exam_subjects')
      .select(`
        id, exam_date, start_time, end_time, total_marks, passing_marks, subject_id,
        subjects(id, name, code),
        exams!inner(id, name, class_id, is_final)
      `)
      .in('subject_id', subjectIds)
      .in('exams.class_id', classIds);

    if (!allExamSubjects || allExamSubjects.length === 0) {
      return res.json([]);
    }

    // Filter to only exam subjects that match the teacher's actual assignments
    const assignmentSet = new Set(assignments.map(a => `${a.subject_id}:${a.class_id}`));
    const matchedExamSubjects = allExamSubjects.filter(es => {
      const classId = (es as any).exams?.class_id;
      return assignmentSet.has(`${es.subject_id}:${classId}`);
    });

    if (matchedExamSubjects.length === 0) {
      return res.json([]);
    }

    // Batch: count results for all matched exam_subject IDs in one query
    const esIds = matchedExamSubjects.map(es => es.id);
    const { data: resultCounts } = await supabase
      .from('exam_results')
      .select('exam_subject_id')
      .in('exam_subject_id', esIds);

    const resultCountMap = new Map<string, number>();
    for (const r of (resultCounts || [])) {
      resultCountMap.set(r.exam_subject_id, (resultCountMap.get(r.exam_subject_id) || 0) + 1);
    }

    // Batch: count students per class (only unique class IDs)
    const studentCountMap = new Map<string, number>();
    await Promise.all(classIds.map(async (classId) => {
      const { count } = await supabase
        .from('students')
        .select('id', { count: 'exact', head: true })
        .eq('class_id', classId);
      studentCountMap.set(classId, count || 0);
    }));

    // Build assignment class name lookup
    const classNameMap = new Map<string, string>();
    for (const a of assignments) {
      classNameMap.set(a.class_id, (a as any).classes?.name || 'N/A');
    }

    // Assemble response
    const result = matchedExamSubjects.map(es => {
      const classId = (es as any).exams?.class_id;
      return {
        ...es,
        className: classNameMap.get(classId) || 'N/A',
        classId,
        resultsEntered: resultCountMap.get(es.id) || 0,
        totalStudents: studentCountMap.get(classId) || 0,
      };
    });

    // Sort by exam_date descending
    result.sort((a, b) => new Date(b.exam_date).getTime() - new Date(a.exam_date).getTime());

    res.json(result);
  } catch (err) {
    console.error('Fetch assigned subjects error:', err);
    res.status(500).json({ error: 'Failed to fetch assigned subjects' });
  }
});

// POST /api/results/student-lookup — student self-service result lookup
// Verifies identity using: examId, class, student name, and password
resultsRouter.post('/student-lookup', requireAuth, async (req, res) => {
  try {
    const supabase = createAdminClient();
    const { examId, password } = req.body;

    if (!examId || !password) {
      return res.status(400).json({ error: 'Exam ID and password are required' });
    }

    // Verify the password by attempting to sign in with the user's email
    const { data: authUser } = await supabase.auth.admin.getUserById(req.userId!);
    if (!authUser?.user?.email) {
      return res.status(400).json({ error: 'Could not verify your identity' });
    }

    // Verify password by trying to sign in
    const { error: signInError } = await supabase.auth.signInWithPassword({
      email: authUser.user.email,
      password,
    });

    if (signInError) {
      return res.status(401).json({ error: 'Invalid password. Please enter your login password.' });
    }

    // Find the student by user_id and roll_number
    const { data: student, error: studentError } = await supabase
      .from('students')
      .select('id, roll_number, class_id, profiles(first_name, last_name)')
      .eq('user_id', req.userId)
      .single();

    if (studentError || !student) {
      return res.status(404).json({ error: 'Student record not found' });
    }

    // Get the exam
    const { data: exam, error: examError } = await supabase
      .from('exams')
      .select(`
        id, name, is_final, class_id,
        classes(id, name),
        exam_subjects(
          id, subject_id, exam_date, total_marks, passing_marks,
          subjects(id, name, code)
        )
      `)
      .eq('id', examId)
      .single();

    if (examError || !exam) {
      return res.status(404).json({ error: 'Exam not found' });
    }

    // Verify student belongs to this exam's class
    if (exam.class_id !== student.class_id) {
      return res.status(403).json({ error: 'This exam is not for your class' });
    }

    // Get results for this student in this exam
    const examSubjectIds = (exam.exam_subjects || []).map((es: any) => es.id);
    let results: any[] = [];
    if (examSubjectIds.length > 0) {
      const { data: resultsData } = await supabase
        .from('exam_results')
        .select('*')
        .eq('student_id', student.id)
        .in('exam_subject_id', examSubjectIds);
      results = resultsData || [];
    }

    const subjectResults = (exam.exam_subjects || []).map((es: any) => {
      const result = results.find((r: any) => r.exam_subject_id === es.id);
      const marksObtained = result ? result.marks_obtained : null;
      const isAbsent = result ? result.is_absent : false;
      const passed = result ? (!isAbsent && marksObtained !== null && marksObtained >= es.passing_marks) : false;
      return {
        subjectName: es.subjects?.name || 'N/A',
        subjectCode: es.subjects?.code || '',
        totalMarks: es.total_marks,
        passingMarks: es.passing_marks,
        marksObtained,
        isAbsent,
        passed,
        hasResult: !!result,
      };
    });

    const totalMarks = subjectResults.reduce((s: number, sub: any) => s + sub.totalMarks, 0);
    const totalObtained = subjectResults.reduce((s: number, sub: any) => s + (sub.marksObtained || 0), 0);
    const allResultsEntered = subjectResults.every((s: any) => s.hasResult);
    const overallPassed = allResultsEntered && subjectResults.every((s: any) => s.passed);
    const percentage = totalMarks > 0 ? ((totalObtained / totalMarks) * 100).toFixed(1) : '0.0';

    res.json({
      student: {
        name: `${(student.profiles as any)?.first_name || ''} ${(student.profiles as any)?.last_name || ''}`.trim(),
        rollNumber: student.roll_number,
      },
      exam: {
        name: exam.name,
        isFinal: exam.is_final,
        className: (exam.classes as any)?.name || 'N/A',
      },
      subjects: subjectResults,
      totalMarks,
      totalObtained,
      percentage: parseFloat(percentage),
      overallPassed,
      allResultsEntered,
    });
  } catch (err) {
    console.error('Student result lookup error:', err);
    res.status(500).json({ error: 'Failed to look up results' });
  }
});
