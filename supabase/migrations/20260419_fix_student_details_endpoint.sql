-- Fix issues with student details endpoint
-- This migration ensures admin users can access student details properly

-- Make sure the profiles table has a dob column for consistency
ALTER TABLE public.profiles
ADD COLUMN IF NOT EXISTS dob DATE;

-- Add roll_number column to students table for academic tracking
ALTER TABLE public.students
ADD COLUMN IF NOT EXISTS roll_number TEXT;

-- Create an index on students(id) for faster lookups
CREATE INDEX IF NOT EXISTS idx_students_id ON public.students(id);

-- Create an index on profiles(user_id) for faster lookups
CREATE INDEX IF NOT EXISTS idx_profiles_user_id ON public.profiles(user_id);

-- Create an index on student_attendance(student_id) for faster lookups
CREATE INDEX IF NOT EXISTS idx_student_attendance_student_id ON public.student_attendance(student_id);

-- Update RLS policies to be more permissive for admins
-- Drop existing student RLS policies if they're too restrictive
DROP POLICY IF EXISTS "Students can read own record" ON public.students;
DROP POLICY IF EXISTS "Admins can manage students" ON public.students;
DROP POLICY IF EXISTS "Teachers can read students" ON public.students;

-- Create new, more explicit policies for students
CREATE POLICY "Students can read own record" ON public.students
  FOR SELECT TO authenticated
  USING (auth.uid() = user_id);

CREATE POLICY "Admins can read all students" ON public.students
  FOR SELECT TO authenticated
  USING (public.has_role(auth.uid(), 'admin'));

CREATE POLICY "Admins can insert students" ON public.students
  FOR INSERT TO authenticated
  WITH CHECK (public.has_role(auth.uid(), 'admin'));

CREATE POLICY "Admins can update students" ON public.students
  FOR UPDATE TO authenticated
  USING (public.has_role(auth.uid(), 'admin'));

CREATE POLICY "Admins can delete students" ON public.students
  FOR DELETE TO authenticated
  USING (public.has_role(auth.uid(), 'admin'));

CREATE POLICY "Teachers can read students" ON public.students
  FOR SELECT TO authenticated
  USING (public.has_role(auth.uid(), 'teacher'));

-- Update profiles RLS policies - drop all first
DROP POLICY IF EXISTS "Users can read own profile" ON public.profiles;
DROP POLICY IF EXISTS "Admins can read all profiles" ON public.profiles;
DROP POLICY IF EXISTS "Users can update own profile" ON public.profiles;
DROP POLICY IF EXISTS "Admins can manage profiles" ON public.profiles;
DROP POLICY IF EXISTS "Admins can insert profiles" ON public.profiles;
DROP POLICY IF EXISTS "Admins can update profiles" ON public.profiles;
DROP POLICY IF EXISTS "Admins can manage profiles fully" ON public.profiles;

-- Recreate profiles policies
CREATE POLICY "Users can read own profile" ON public.profiles
  FOR SELECT TO authenticated
  USING (auth.uid() = user_id);

CREATE POLICY "Admins can read all profiles" ON public.profiles
  FOR SELECT TO authenticated
  USING (public.has_role(auth.uid(), 'admin'));

CREATE POLICY "Admins can insert profiles" ON public.profiles
  FOR INSERT TO authenticated
  WITH CHECK (public.has_role(auth.uid(), 'admin'));

CREATE POLICY "Admins can update profiles" ON public.profiles
  FOR UPDATE TO authenticated
  USING (public.has_role(auth.uid(), 'admin'));

CREATE POLICY "Users can update own profile" ON public.profiles
  FOR UPDATE TO authenticated
  USING (auth.uid() = user_id);

CREATE POLICY "Admins can delete profiles" ON public.profiles
  FOR DELETE TO authenticated
  USING (public.has_role(auth.uid(), 'admin'));

-- Update classes RLS policies
DROP POLICY IF EXISTS "Authenticated can read classes" ON public.classes;
DROP POLICY IF EXISTS "Admins can manage classes" ON public.classes;
DROP POLICY IF EXISTS "Admins can insert classes" ON public.classes;
DROP POLICY IF EXISTS "Admins can update classes" ON public.classes;
DROP POLICY IF EXISTS "Admins can delete classes" ON public.classes;

CREATE POLICY "Authenticated can read classes" ON public.classes
  FOR SELECT TO authenticated
  USING (true);

CREATE POLICY "Admins can insert classes" ON public.classes
  FOR INSERT TO authenticated
  WITH CHECK (public.has_role(auth.uid(), 'admin'));

CREATE POLICY "Admins can update classes" ON public.classes
  FOR UPDATE TO authenticated
  USING (public.has_role(auth.uid(), 'admin'));

CREATE POLICY "Admins can delete classes" ON public.classes
  FOR DELETE TO authenticated
  USING (public.has_role(auth.uid(), 'admin'));

-- Ensure student_attendance has proper indexes
CREATE INDEX IF NOT EXISTS idx_student_attendance_date ON public.student_attendance(date);
CREATE INDEX IF NOT EXISTS idx_student_attendance_status ON public.student_attendance(status);

