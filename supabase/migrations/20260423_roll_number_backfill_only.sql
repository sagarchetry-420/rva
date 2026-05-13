-- Simpler approach: Just add index and support for roll numbers
-- No triggers - we'll handle assignment in the API instead

BEGIN;

-- Drop trigger and function if they exist (cleanup)
DROP TRIGGER IF EXISTS on_student_insert_assign_roll ON public.students;
DROP FUNCTION IF EXISTS public.assign_roll_number();

-- Backfill existing students with roll numbers based on enrollment date
UPDATE public.students s
SET roll_number = ranked.roll_num
FROM (
  SELECT
    id,
    ROW_NUMBER() OVER (PARTITION BY class_id ORDER BY enrollment_date ASC) as roll_num
  FROM public.students
  WHERE class_id IS NOT NULL
) ranked
WHERE s.id = ranked.id AND s.roll_number IS NULL;

-- Create index for better performance
CREATE INDEX IF NOT EXISTS idx_students_class_roll
ON public.students(class_id, roll_number);

COMMIT;
