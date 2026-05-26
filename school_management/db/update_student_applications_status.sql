-- Update student_applications table to include 'enrolled' status
ALTER TABLE student_applications 
MODIFY COLUMN status ENUM('pending','approved','rejected','enrolled') DEFAULT 'pending';
