-- Ensure all students have profile records
-- This fixes any students that may have been created without profiles

-- Insert missing profiles for students who don't have one
INSERT INTO public.profiles (user_id, first_name, last_name)
SELECT s.user_id, '', ''
FROM public.students s
WHERE NOT EXISTS (
  SELECT 1 FROM public.profiles p WHERE p.user_id = s.user_id
);

-- Insert missing profiles for teachers who don't have one
INSERT INTO public.profiles (user_id, first_name, last_name)
SELECT t.user_id, '', ''
FROM public.teachers t
WHERE NOT EXISTS (
  SELECT 1 FROM public.profiles p WHERE p.user_id = t.user_id
);

-- Insert missing profiles for staff who don't have one
INSERT INTO public.profiles (user_id, first_name, last_name)
SELECT st.user_id, '', ''
FROM public.staff st
WHERE NOT EXISTS (
  SELECT 1 FROM public.profiles p WHERE p.user_id = st.user_id
);
