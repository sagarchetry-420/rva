-- Add end_date to student_services table to support scheduled deactivation
ALTER TABLE student_services 
ADD COLUMN end_date DATE NULL AFTER enrollment_date;
