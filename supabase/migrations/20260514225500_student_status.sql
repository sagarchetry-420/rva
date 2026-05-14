-- Add student status (active / graduated / left) to track lifecycle
-- Also expand the student_promotions.result constraint to allow 'graduated' and 'left'

BEGIN;

-- 1. Add status column to students table with default 'active'
ALTER TABLE public.students
  ADD COLUMN IF NOT EXISTS status TEXT NOT NULL DEFAULT 'active'
  CHECK (status IN ('active', 'graduated', 'left'));

-- 2. Add an index for filtering by status
CREATE INDEX IF NOT EXISTS idx_students_status ON public.students(status);

-- 3. Expand the student_promotions result constraint to include 'graduated' and 'left'
ALTER TABLE public.student_promotions
  DROP CONSTRAINT IF EXISTS student_promotions_result_check;
ALTER TABLE public.student_promotions
  ADD CONSTRAINT student_promotions_result_check
  CHECK (result IN ('promoted', 'retained', 'graduated', 'left'));

COMMIT;
