-- 1. Add new enum values to attendance_status
-- Note: Postgres does not allow adding values to an enum in a transaction block
-- if the enum was created in the same transaction. We can assume it was created earlier.
ALTER TYPE attendance_status ADD VALUE IF NOT EXISTS 'Half Leave';
ALTER TYPE attendance_status ADD VALUE IF NOT EXISTS 'Full Leave';

-- 2. Add leave_application_url to student_attendance
ALTER TABLE student_attendance ADD COLUMN IF NOT EXISTS leave_application_url text;

-- 3. Create a private bucket for leave-applications
INSERT INTO storage.buckets (id, name, public) 
VALUES ('leave-applications', 'leave-applications', false) 
ON CONFLICT (id) DO NOTHING;

-- 4. Storage RLS Policies for leave-applications bucket
-- We want admins to be able to upload, read, update, delete
-- We want teachers to be able to read (optional, but good if they need to see it)

-- Drop existing policies if they exist (for idempotency)
DROP POLICY IF EXISTS "Admins can manage leave applications" ON storage.objects;
DROP POLICY IF EXISTS "Admins can read leave applications" ON storage.objects;
DROP POLICY IF EXISTS "Teachers can read leave applications" ON storage.objects;

CREATE POLICY "Admins can manage leave applications" ON storage.objects
FOR ALL TO authenticated
USING (
  bucket_id = 'leave-applications' AND 
  public.has_role(auth.uid(), 'admin')
)
WITH CHECK (
  bucket_id = 'leave-applications' AND 
  public.has_role(auth.uid(), 'admin')
);

CREATE POLICY "Teachers can read leave applications" ON storage.objects
FOR SELECT TO authenticated
USING (
  bucket_id = 'leave-applications' AND 
  public.has_role(auth.uid(), 'teacher')
);
