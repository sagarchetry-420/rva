-- ============================================================
-- School Management System - Database Setup
-- ============================================================
-- Run this in phpMyAdmin SQL tab after creating the database

CREATE DATABASE IF NOT EXISTS school_management;
USE school_management;

-- ============================================================
-- USERS TABLE (Login Authentication)
-- ============================================================
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'teacher', 'student') NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- STUDENTS TABLE
-- ============================================================
CREATE TABLE students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    address TEXT,
    phone VARCHAR(15),
    parent_name VARCHAR(100),
    parent_phone VARCHAR(15),
    class_id INT,
    roll_number VARCHAR(20),
    admission_date DATE,
    photo VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ============================================================
-- TEACHERS TABLE
-- ============================================================
CREATE TABLE teachers (
    teacher_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    phone VARCHAR(15),
    email VARCHAR(100),
    address TEXT,
    qualification VARCHAR(100),
    subject_specialization VARCHAR(100),
    joining_date DATE,
    photo VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ============================================================
-- CLASSES TABLE
-- ============================================================
CREATE TABLE classes (
    class_id INT PRIMARY KEY AUTO_INCREMENT,
    class_name VARCHAR(50) NOT NULL,
    section VARCHAR(10),
    class_teacher_id INT,
    academic_year VARCHAR(20),
    FOREIGN KEY (class_teacher_id) REFERENCES teachers(teacher_id) ON DELETE SET NULL
);

-- ============================================================
-- SUBJECTS TABLE
-- ============================================================
CREATE TABLE subjects (
    subject_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_name VARCHAR(100) NOT NULL,
    subject_code VARCHAR(20),
    description TEXT
);

-- ============================================================
-- CLASS_SUBJECTS TABLE (Many-to-Many)
-- ============================================================
CREATE TABLE class_subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT,
    subject_id INT,
    teacher_id INT,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id) ON DELETE SET NULL
);

-- ============================================================
-- ATTENDANCE TABLE
-- ============================================================
CREATE TABLE attendance (
    attendance_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    class_id INT,
    attendance_date DATE NOT NULL,
    status ENUM('Present', 'Absent', 'Late', 'Excused') NOT NULL,
    remarks TEXT,
    marked_by INT,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(user_id),
    UNIQUE KEY unique_attendance (student_id, attendance_date)
);

-- ============================================================
-- EXAMINATIONS TABLE
-- ============================================================
CREATE TABLE examinations (
    exam_id INT PRIMARY KEY AUTO_INCREMENT,
    exam_name VARCHAR(100) NOT NULL,
    exam_type VARCHAR(50),
    start_date DATE,
    end_date DATE,
    academic_year VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- RESULTS TABLE
-- ============================================================
CREATE TABLE results (
    result_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    exam_id INT,
    subject_id INT,
    marks_obtained DECIMAL(5,2),
    max_marks DECIMAL(5,2),
    grade VARCHAR(5),
    remarks TEXT,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES examinations(exam_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    UNIQUE KEY unique_result (student_id, exam_id, subject_id)
);

-- ============================================================
-- FEES TABLE
-- ============================================================
CREATE TABLE fees (
    fee_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    fee_type VARCHAR(50),
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE,
    payment_status ENUM('Pending', 'Paid', 'Overdue') DEFAULT 'Pending',
    payment_date DATE,
    payment_method VARCHAR(50),
    receipt_number VARCHAR(50),
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- ============================================================
-- NOTICES TABLE
-- ============================================================
CREATE TABLE notices (
    notice_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    notice_date DATE NOT NULL,
    target_audience ENUM('All', 'Students', 'Teachers', 'Parents') DEFAULT 'All',
    posted_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- ============================================================
-- TIMETABLE TABLE
-- ============================================================
CREATE TABLE timetable (
    timetable_id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT,
    subject_id INT,
    teacher_id INT,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
    period_number INT,
    start_time TIME,
    end_time TIME,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id) ON DELETE SET NULL,
    UNIQUE KEY unique_slot (class_id, day_of_week, period_number)
);

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Admin User
INSERT INTO users (username, password, user_type, email) 
VALUES ('admin', MD5('admin123'), 'admin', 'admin@school.com');

-- Sample Teachers
INSERT INTO users (username, password, user_type, email) VALUES
('teacher1', MD5('teacher123'), 'teacher', 'teacher1@school.com'),
('teacher2', MD5('teacher123'), 'teacher', 'teacher2@school.com');

INSERT INTO teachers (user_id, first_name, last_name, gender, phone, email, qualification, subject_specialization, joining_date) VALUES
(2, 'Rajesh', 'Kumar', 'Male', '9876543210', 'teacher1@school.com', 'M.Sc. Mathematics', 'Mathematics', '2024-01-15'),
(3, 'Priya', 'Sharma', 'Female', '9876543211', 'teacher2@school.com', 'M.A. English', 'English', '2024-02-01');

-- Sample Classes
INSERT INTO classes (class_name, section, class_teacher_id, academic_year) VALUES
('Class 1', 'A', 1, '2025-26'),
('Class 2', 'A', 2, '2025-26'),
('Class 3', 'A', NULL, '2025-26'),
('Class 4', 'A', NULL, '2025-26'),
('Class 5', 'A', NULL, '2025-26');

-- Sample Subjects
INSERT INTO subjects (subject_name, subject_code, description) VALUES
('Mathematics', 'MATH101', 'Basic and Advanced Mathematics'),
('English', 'ENG101', 'English Language and Literature'),
('Science', 'SCI101', 'General Science'),
('Social Studies', 'SS101', 'History, Geography, and Civics'),
('Computer Science', 'CS101', 'Computer Fundamentals and Programming');

-- Assign Subjects to Classes
INSERT INTO class_subjects (class_id, subject_id, teacher_id) VALUES
(1, 1, 1), (1, 2, 2), (1, 3, 1),
(2, 1, 1), (2, 2, 2), (2, 4, 2),
(3, 1, 1), (3, 2, 2), (3, 5, 1);

-- Sample Students
INSERT INTO users (username, password, user_type, email) VALUES
('student1', MD5('student123'), 'student', 'student1@school.com'),
('student2', MD5('student123'), 'student', 'student2@school.com'),
('student3', MD5('student123'), 'student', 'student3@school.com');

INSERT INTO students (user_id, first_name, last_name, date_of_birth, gender, phone, parent_name, parent_phone, class_id, roll_number, admission_date) VALUES
(4, 'Amit', 'Das', '2015-05-15', 'Male', '9876543220', 'Mr. Ravi Das', '9876543221', 1, 'STD001', '2024-04-01'),
(5, 'Sneha', 'Borah', '2015-08-22', 'Female', '9876543222', 'Mr. Kiran Borah', '9876543223', 1, 'STD002', '2024-04-01'),
(6, 'Rahul', 'Singh', '2014-03-10', 'Male', '9876543224', 'Mr. Vijay Singh', '9876543225', 2, 'STD003', '2024-04-01');

-- Sample Notice
INSERT INTO notices (title, description, notice_date, target_audience, posted_by) VALUES
('Welcome to New Academic Year 2025-26', 'We are pleased to welcome all students and staff to the new academic year. Classes begin from April 1st, 2025.', '2025-04-01', 'All', 1),
('Annual Sports Day', 'Annual Sports Day will be held on May 25th, 2025. All students are requested to participate.', '2025-05-20', 'Students', 1);

-- Sample Examination
INSERT INTO examinations (exam_name, exam_type, start_date, end_date, academic_year) VALUES
('Mid-Term Examination 2025', 'Mid-Term', '2025-07-15', '2025-07-25', '2025-26');
