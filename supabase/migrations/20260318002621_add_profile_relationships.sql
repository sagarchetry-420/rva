-- Add foreign key relationships from students and teachers to profiles
-- This allows Supabase PostgREST to join these tables with profiles

-- Add FK from students.user_id to profiles.user_id
ALTER TABLE public.students
ADD CONSTRAINT students_user_id_profiles_fkey
FOREIGN KEY (user_id) REFERENCES public.profiles(user_id) ON DELETE CASCADE;

-- Add FK from teachers.user_id to profiles.user_id
ALTER TABLE public.teachers
ADD CONSTRAINT teachers_user_id_profiles_fkey
FOREIGN KEY (user_id) REFERENCES public.profiles(user_id) ON DELETE CASCADE;

-- Add FK from staff.user_id to profiles.user_id (for consistency)
ALTER TABLE public.staff
ADD CONSTRAINT staff_user_id_profiles_fkey
FOREIGN KEY (user_id) REFERENCES public.profiles(user_id) ON DELETE CASCADE;
