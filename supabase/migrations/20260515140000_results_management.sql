-- ============================================================
-- Results Management System
-- 1. Add is_final flag to exams
-- 2. Create exam_results table for storing student marks
-- ============================================================

-- 1. Add is_final column to exams table
ALTER TABLE public.exams
ADD COLUMN IF NOT EXISTS is_final BOOLEAN NOT NULL DEFAULT false;

-- 2. Create exam_results table
CREATE TABLE public.exam_results (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  exam_subject_id UUID REFERENCES public.exam_subjects(id) ON DELETE CASCADE NOT NULL,
  student_id UUID REFERENCES public.students(id) ON DELETE CASCADE NOT NULL,
  marks_obtained INTEGER NOT NULL DEFAULT 0,
  is_absent BOOLEAN NOT NULL DEFAULT false,
  remarks TEXT,
  entered_by UUID REFERENCES auth.users(id),
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  UNIQUE (exam_subject_id, student_id)
);
ALTER TABLE public.exam_results ENABLE ROW LEVEL SECURITY;

-- 3. Indexes
CREATE INDEX idx_exam_results_exam_subject_id ON public.exam_results(exam_subject_id);
CREATE INDEX idx_exam_results_student_id ON public.exam_results(student_id);

-- 4. RLS Policies for exam_results
CREATE POLICY "Authenticated can read exam_results"
  ON public.exam_results FOR SELECT TO authenticated USING (true);

CREATE POLICY "Admins can manage exam_results"
  ON public.exam_results FOR ALL TO authenticated
  USING (public.has_role(auth.uid(), 'admin'));

CREATE POLICY "Teachers can insert exam_results"
  ON public.exam_results FOR INSERT TO authenticated
  WITH CHECK (public.has_role(auth.uid(), 'teacher'));

CREATE POLICY "Teachers can update exam_results"
  ON public.exam_results FOR UPDATE TO authenticated
  USING (public.has_role(auth.uid(), 'teacher'));
