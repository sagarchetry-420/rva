-- Migration: Add Performance Indexes for Large Datasets
-- Date: 2026-05-22

-- 1. Index on students table
-- Helps with fast lookups by name, class, and roll number which are heavily used in filters.
CREATE INDEX idx_students_current_class ON students(current_class_id);
CREATE INDEX idx_students_roll_number ON students(roll_number);
CREATE INDEX idx_students_first_name ON students(first_name);
CREATE INDEX idx_students_last_name ON students(last_name);

-- 2. Index on student_applications table
-- Helps with filtering applications by status and class, and sorting by created_at
CREATE INDEX idx_applications_status ON student_applications(status);
CREATE INDEX idx_applications_class_id ON student_applications(class_id);
CREATE INDEX idx_applications_created_at ON student_applications(created_at);

-- 3. Index on student_academics table
-- Very heavily used for querying students in a specific session/class
CREATE INDEX idx_student_academics_session_class ON student_academics(session_id, class_id);
CREATE INDEX idx_student_academics_student_session ON student_academics(student_id, session_id);
CREATE INDEX idx_student_academics_admission_status ON student_academics(admission_status);

-- 4. Index on fees table
-- Heavily queried by session, student, and payment status for dues calculation
CREATE INDEX idx_fees_student_session ON fees(student_id, session_id);
CREATE INDEX idx_fees_payment_status ON fees(payment_status);
CREATE INDEX idx_fees_due_date ON fees(due_date);

-- 5. Index on attendance table
-- Required for fast attendance reporting and dashboard stats
CREATE INDEX idx_attendance_date_class ON attendance(attendance_date, class_id);
CREATE INDEX idx_attendance_student_id ON attendance(student_id);

-- 6. Index on teachers table
-- For filtering teachers by status
CREATE INDEX idx_teachers_status ON teachers(status);

-- 7. Index on users table
-- For fast login lookups
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_role ON users(role);
