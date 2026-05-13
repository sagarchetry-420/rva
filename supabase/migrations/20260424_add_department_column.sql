-- Add department column to teachers table if it doesn't exist
ALTER TABLE public.teachers
ADD COLUMN IF NOT EXISTS department TEXT DEFAULT 'Unassigned';
