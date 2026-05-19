-- Database update script for Examination System Overhaul
-- Please run this script in phpMyAdmin to apply the required database changes.

-- Add is_published to examinations to control result visibility
ALTER TABLE examinations ADD COLUMN is_published TINYINT(1) DEFAULT 0;

-- Add is_absent to results to track if a student missed the exam
ALTER TABLE results ADD COLUMN is_absent TINYINT(1) DEFAULT 0;

-- Create exam_schedules table to store class-wise subject routines
CREATE TABLE IF NOT EXISTS exam_schedules (
    schedule_id INT PRIMARY KEY AUTO_INCREMENT,
    exam_id INT,
    class_id INT,
    subject_id INT,
    exam_date DATE,
    start_time TIME,
    end_time TIME,
    full_marks DECIMAL(5,2) DEFAULT 100.00,
    pass_marks DECIMAL(5,2) DEFAULT 30.00,
    FOREIGN KEY (exam_id) REFERENCES examinations(exam_id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    UNIQUE KEY unique_schedule (exam_id, class_id, subject_id)
);
