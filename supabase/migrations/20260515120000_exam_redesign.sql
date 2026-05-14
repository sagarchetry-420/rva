-- ============================================================
-- Exam Management Redesign: Two-Level Architecture
-- exams (parent) → exam_subjects (children per subject)
-- ============================================================

-- 1. Drop old exams table and its policies
DROP POLICY IF EXISTS "Authenticated can read exams" ON public.exams;
DROP POLICY IF EXISTS "Admins can manage exams" ON public.exams;
DROP POLICY IF EXISTS "Teachers can manage exams" ON public.exams;
DROP TABLE IF EXISTS public.exams CASCADE;

-- 2. Create new exams table (parent — one row per exam per class)
CREATE TABLE public.exams (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name TEXT NOT NULL,
  class_id UUID REFERENCES public.classes(id) ON DELETE CASCADE NOT NULL,
  description TEXT,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
ALTER TABLE public.exams ENABLE ROW LEVEL SECURITY;

-- 3. Create exam_subjects table (child — one row per subject per exam)
CREATE TABLE public.exam_subjects (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  exam_id UUID REFERENCES public.exams(id) ON DELETE CASCADE NOT NULL,
  subject_id UUID REFERENCES public.subjects(id) ON DELETE CASCADE NOT NULL,
  exam_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  total_marks INTEGER NOT NULL DEFAULT 100,
  passing_marks INTEGER NOT NULL DEFAULT 33,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  UNIQUE (exam_id, subject_id)
);
ALTER TABLE public.exam_subjects ENABLE ROW LEVEL SECURITY;

-- 4. Indexes
CREATE INDEX idx_exams_class_id ON public.exams(class_id);
CREATE INDEX idx_exam_subjects_exam_id ON public.exam_subjects(exam_id);
CREATE INDEX idx_exam_subjects_exam_date ON public.exam_subjects(exam_date);

-- 5. RLS Policies for exams
CREATE POLICY "Authenticated can read exams" ON public.exams FOR SELECT TO authenticated USING (true);
CREATE POLICY "Admins can manage exams" ON public.exams FOR ALL TO authenticated USING (public.has_role(auth.uid(), 'admin'));
CREATE POLICY "Teachers can read exams" ON public.exams FOR SELECT TO authenticated USING (public.has_role(auth.uid(), 'teacher'));

-- 6. RLS Policies for exam_subjects
CREATE POLICY "Authenticated can read exam_subjects" ON public.exam_subjects FOR SELECT TO authenticated USING (true);
CREATE POLICY "Admins can manage exam_subjects" ON public.exam_subjects FOR ALL TO authenticated USING (public.has_role(auth.uid(), 'admin'));
CREATE POLICY "Teachers can read exam_subjects" ON public.exam_subjects FOR SELECT TO authenticated USING (public.has_role(auth.uid(), 'teacher'));
