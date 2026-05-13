-- Reassign all roll numbers to be sequential (1, 2, 3, ...) within each class
-- Ordered by enrollment date (earliest students first)

BEGIN;

-- Reassign roll numbers sequentially within each class by enrollment date
UPDATE public.students
SET roll_number = new_rolls.new_roll_num
FROM (
  SELECT
    id,
    ROW_NUMBER() OVER (PARTITION BY class_id ORDER BY enrollment_date ASC, id ASC) as new_roll_num
  FROM public.students
  WHERE class_id IS NOT NULL
) new_rolls
WHERE public.students.id = new_rolls.id;

COMMIT;
