-- Add status and notice period fields to teachers table
ALTER TABLE public.teachers 
ADD COLUMN IF NOT EXISTS status VARCHAR DEFAULT 'active' CHECK (status IN ('active', 'on_notice', 'left')),
ADD COLUMN IF NOT EXISTS notice_start_date DATE,
ADD COLUMN IF NOT EXISTS last_working_date DATE;
