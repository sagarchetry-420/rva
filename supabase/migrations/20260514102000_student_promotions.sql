-- Track annual student promotion decisions (promoted/retained)
-- This supports bulk class promotion workflows without re-enrollment.

BEGIN;

CREATE TABLE IF NOT EXISTS public.student_promotions (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  student_id UUID NOT NULL REFERENCES public.students(id) ON DELETE CASCADE,
  from_class_id UUID REFERENCES public.classes(id) ON DELETE SET NULL,
  to_class_id UUID REFERENCES public.classes(id) ON DELETE SET NULL,
  from_roll_number TEXT,
  to_roll_number TEXT,
  result TEXT NOT NULL CHECK (result IN ('promoted', 'retained')),
  academic_year TEXT NOT NULL,
  promoted_by UUID REFERENCES auth.users(id) ON DELETE SET NULL,
  promoted_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  notes TEXT,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  UNIQUE (student_id, academic_year)
);

CREATE INDEX IF NOT EXISTS idx_student_promotions_student_id
  ON public.student_promotions(student_id);

CREATE INDEX IF NOT EXISTS idx_student_promotions_academic_year
  ON public.student_promotions(academic_year);

ALTER TABLE public.student_promotions ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Admins can manage student promotions" ON public.student_promotions;
CREATE POLICY "Admins can manage student promotions" ON public.student_promotions
  FOR ALL TO authenticated
  USING (public.has_role(auth.uid(), 'admin'))
  WITH CHECK (public.has_role(auth.uid(), 'admin'));

DROP POLICY IF EXISTS "Teachers can read student promotions" ON public.student_promotions;
CREATE POLICY "Teachers can read student promotions" ON public.student_promotions
  FOR SELECT TO authenticated
  USING (public.has_role(auth.uid(), 'teacher'));

DROP POLICY IF EXISTS "Students can read own promotions" ON public.student_promotions;
CREATE POLICY "Students can read own promotions" ON public.student_promotions
  FOR SELECT TO authenticated
  USING (
    EXISTS (
      SELECT 1
      FROM public.students s
      WHERE s.id = student_promotions.student_id
        AND s.user_id = auth.uid()
    )
  );

COMMIT;
