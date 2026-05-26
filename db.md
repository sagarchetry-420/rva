 ============================================================
-- SCHOOL MANAGEMENT SYSTEM — COMPLETE DATABASE SCHEMA
-- Single consolidated file. Run this ONCE on a fresh database.
-- Compatible with MySQL 8.0+
-- ============================================================

CREATE DATABASE IF NOT EXISTS rva
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE rva;

SET FOREIGN_KEY_CHECKS = 0;


-- ============================================================
-- MODULE 1: CORE LOOKUP TABLES
-- These have no foreign keys — create them first
-- ============================================================

-- Academic sessions (e.g. 2024-25, 2025-26)
CREATE TABLE IF NOT EXISTS academic_sessions (
    session_id    INT PRIMARY KEY AUTO_INCREMENT,
    session_name  VARCHAR(20)  NOT NULL UNIQUE,         -- e.g. '2025-26'
    start_date    DATE         NOT NULL,
    end_date      DATE         NOT NULL,
    is_current    TINYINT(1)   NOT NULL DEFAULT 0,
    description   TEXT,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by    INT,                                   -- FK added later
    CONSTRAINT chk_session_dates CHECK (end_date > start_date)
);

-- Fee categories (replaces free-text fee_type)
CREATE TABLE IF NOT EXISTS fee_categories (
    category_id   INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description   TEXT,
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Services (transport, hostel, meals, etc.)
CREATE TABLE IF NOT EXISTS services (
    service_id    INT PRIMARY KEY AUTO_INCREMENT,
    service_name  VARCHAR(100) NOT NULL UNIQUE,
    description   TEXT,
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Admission settings (key-value config store)
CREATE TABLE IF NOT EXISTS admission_settings (
    setting_id    INT PRIMARY KEY AUTO_INCREMENT,
    setting_name  VARCHAR(100) NOT NULL UNIQUE,
    setting_value VARCHAR(500),
    description   TEXT,
    setting_type  ENUM('boolean','text','number','date','email') NOT NULL DEFAULT 'text',
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- ============================================================
-- MODULE 2: USERS & AUTHENTICATION
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
    user_id          INT PRIMARY KEY AUTO_INCREMENT,
    username         VARCHAR(50)  NOT NULL UNIQUE,
    -- Store bcrypt hash (password_hash($pass, PASSWORD_BCRYPT) in PHP).
    -- Plain-text password is generated BEFORE hashing and emailed to the user.
    password_hash    VARCHAR(255) NOT NULL,
    user_type        ENUM('admin','teacher','student','parent') NOT NULL,
    email            VARCHAR(150) UNIQUE,
    is_active        TINYINT(1)   NOT NULL DEFAULT 1,
    last_login       DATETIME,
    failed_attempts  TINYINT      NOT NULL DEFAULT 0,   -- lock after N failures
    locked_until     DATETIME,                          -- NULL = not locked
    -- Password reset: store a hashed token, not the raw token
    reset_token_hash VARCHAR(64),                       -- SHA2(random_token, 256)
    reset_expires    DATETIME,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_user_type (user_type)
);


-- ============================================================
-- MODULE 3: CLASSES & SUBJECTS
-- ============================================================

CREATE TABLE IF NOT EXISTS classes (
    class_id         INT PRIMARY KEY AUTO_INCREMENT,
    class_name       VARCHAR(50)  NOT NULL,              -- e.g. 'Class 6'
    section          VARCHAR(10),                        -- e.g. 'A'
    class_teacher_id INT,                               -- FK added below
    is_active        TINYINT(1)   NOT NULL DEFAULT 1,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_class_section (class_name, section)
);

CREATE TABLE IF NOT EXISTS subjects (
    subject_id    INT PRIMARY KEY AUTO_INCREMENT,
    subject_name  VARCHAR(100) NOT NULL,
    subject_code  VARCHAR(20)  NOT NULL UNIQUE,
    description   TEXT,
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Which subjects are taught in which class (and by which teacher)
CREATE TABLE IF NOT EXISTS class_subjects (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    class_id      INT NOT NULL,
    subject_id    INT NOT NULL,
    teacher_id    INT,                                  -- FK added below
    session_id    INT NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id)   REFERENCES classes(class_id)   ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(session_id),
    UNIQUE KEY unique_class_subject_session (class_id, subject_id, session_id)
);


-- ============================================================
-- MODULE 4: TEACHERS
-- ============================================================

CREATE TABLE IF NOT EXISTS teachers (
    teacher_id             INT PRIMARY KEY AUTO_INCREMENT,
    user_id                INT NOT NULL UNIQUE,
    first_name             VARCHAR(50)  NOT NULL,
    last_name              VARCHAR(50)  NOT NULL,
    date_of_birth          DATE,
    gender                 ENUM('Male','Female','Other'),
    phone                  VARCHAR(15),
    email                  VARCHAR(150) NOT NULL UNIQUE,
    address                TEXT,
    qualification          VARCHAR(150),
    subject_specialization VARCHAR(150),
    joining_date           DATE,
    photo                  VARCHAR(255),
    is_active              TINYINT(1) NOT NULL DEFAULT 1,
    created_at             TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT chk_teacher_phone CHECK (phone REGEXP '^[0-9]{7,15}$')
);

-- Now add class_teacher FK (circular ref, so added after teachers)
ALTER TABLE classes
    ADD CONSTRAINT fk_class_teacher
    FOREIGN KEY (class_teacher_id) REFERENCES teachers(teacher_id) ON DELETE SET NULL;

-- Now add class_subjects teacher FK
ALTER TABLE class_subjects
    ADD CONSTRAINT fk_cs_teacher
    FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id) ON DELETE SET NULL;


-- ============================================================
-- MODULE 5: STUDENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS students (
    student_id               INT PRIMARY KEY AUTO_INCREMENT,
    user_id                  INT NOT NULL UNIQUE,
    first_name               VARCHAR(50)  NOT NULL,
    last_name                VARCHAR(50)  NOT NULL,
    date_of_birth            DATE         NOT NULL,
    gender                   ENUM('Male','Female','Other') NOT NULL,
    address                  TEXT,
    phone                    VARCHAR(15),
    email                    VARCHAR(150),
    parent_name              VARCHAR(100),
    parent_phone             VARCHAR(15),
    parent_email             VARCHAR(150),
    -- current_class_id and session come from student_academics (source of truth)
    -- Kept here only for quick lookups; updated during promotion
    current_class_id         INT,
    current_session_id       INT,
    roll_number              VARCHAR(20),
    admission_date           DATE,
    photo                    VARCHAR(255),
    admission_application_id INT,                      -- FK added with admissions
    is_active                TINYINT(1) NOT NULL DEFAULT 1,
    created_at               TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at               TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)            REFERENCES users(user_id)    ON DELETE CASCADE,
    FOREIGN KEY (current_class_id)   REFERENCES classes(class_id) ON DELETE SET NULL,
    FOREIGN KEY (current_session_id) REFERENCES academic_sessions(session_id),
    CONSTRAINT chk_student_phone    CHECK (phone        REGEXP '^[0-9]{7,15}$'),
    CONSTRAINT chk_parent_phone     CHECK (parent_phone REGEXP '^[0-9]{7,15}$'),
    -- Roll number unique within class+session (enforced via student_academics below)
    INDEX idx_roll  (roll_number),
    INDEX idx_class (current_class_id)
);


-- ============================================================
-- MODULE 6: STUDENT ACADEMICS & PROMOTION
-- ============================================================

-- Per-year enrolment record. This is the source of truth for
-- which class a student is in during a given session.
CREATE TABLE IF NOT EXISTS student_academics (
    academic_id        INT PRIMARY KEY AUTO_INCREMENT,
    student_id         INT NOT NULL,
    session_id         INT NOT NULL,
    class_id           INT NOT NULL,
    roll_number        VARCHAR(20),
    admission_status   ENUM('Active','Graduated','Transferred','Detained','Dropout') NOT NULL DEFAULT 'Active',
    promotion_status   ENUM('Pending','Promoted','Detained','Transferred','Graduated') NOT NULL DEFAULT 'Pending',
    previous_session_id INT,
    is_repeated        TINYINT(1) NOT NULL DEFAULT 0,
    repeat_count       INT        NOT NULL DEFAULT 0,
    cumulative_percentage DECIMAL(5,2),
    created_at         TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id)         REFERENCES students(student_id)          ON DELETE CASCADE,
    FOREIGN KEY (session_id)         REFERENCES academic_sessions(session_id),
    FOREIGN KEY (class_id)           REFERENCES classes(class_id),
    FOREIGN KEY (previous_session_id) REFERENCES academic_sessions(session_id),
    UNIQUE KEY unique_student_session (student_id, session_id),
    -- Roll number unique within class+session
    UNIQUE KEY unique_roll_class_session (class_id, session_id, roll_number),
    INDEX idx_session  (session_id),
    INDEX idx_class    (class_id),
    INDEX idx_status   (promotion_status)
);

-- Rules for promoting from one class to the next
CREATE TABLE IF NOT EXISTS class_promotion_rules (
    rule_id             INT PRIMARY KEY AUTO_INCREMENT,
    from_class_id       INT NOT NULL,
    to_class_id         INT NOT NULL,
    min_percentage      DECIMAL(5,2) NOT NULL DEFAULT 35.00,
    min_subjects_passed INT          NOT NULL DEFAULT 4,
    max_repeat_count    INT          NOT NULL DEFAULT 2,
    allow_transfer      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by          INT,
    FOREIGN KEY (from_class_id) REFERENCES classes(class_id),
    FOREIGN KEY (to_class_id)   REFERENCES classes(class_id),
    FOREIGN KEY (updated_by)    REFERENCES users(user_id),
    UNIQUE KEY unique_promotion_rule (from_class_id),
    CONSTRAINT chk_min_pct CHECK (min_percentage BETWEEN 0 AND 100)
);

-- Full audit trail of every promotion action
CREATE TABLE IF NOT EXISTS promotion_history (
    promotion_id    INT PRIMARY KEY AUTO_INCREMENT,
    student_id      INT NOT NULL,
    from_class_id   INT,
    to_class_id     INT,
    from_session_id INT,
    to_session_id   INT,
    promotion_type  ENUM('Promoted','Detained','Transferred','Graduated') NOT NULL DEFAULT 'Promoted',
    promotion_reason TEXT,
    previous_percentage DECIMAL(5,2),
    promotion_date  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    promoted_by     INT NOT NULL,
    remarks         TEXT,
    FOREIGN KEY (student_id)      REFERENCES students(student_id)           ON DELETE CASCADE,
    FOREIGN KEY (from_class_id)   REFERENCES classes(class_id),
    FOREIGN KEY (to_class_id)     REFERENCES classes(class_id),
    FOREIGN KEY (from_session_id) REFERENCES academic_sessions(session_id),
    FOREIGN KEY (to_session_id)   REFERENCES academic_sessions(session_id),
    FOREIGN KEY (promoted_by)     REFERENCES users(user_id),
    INDEX idx_student       (student_id),
    INDEX idx_promotion_date (promotion_date)
);


-- ============================================================
-- MODULE 7: EXAMINATIONS & RESULTS
-- ============================================================

CREATE TABLE IF NOT EXISTS examinations (
    exam_id        INT PRIMARY KEY AUTO_INCREMENT,
    exam_name      VARCHAR(150) NOT NULL,
    exam_type      ENUM('Unit Test','Mid-Term','Final','Annual','Class Test','Other') NOT NULL DEFAULT 'Other',
    session_id     INT NOT NULL,
    -- start_date/end_date are for the overall exam period
    start_date     DATE NOT NULL,
    end_date       DATE NOT NULL,
    -- Who created it and what role
    created_by     INT NOT NULL,
    created_by_role ENUM('admin','teacher') NOT NULL DEFAULT 'admin',
    is_approved    TINYINT(1) NOT NULL DEFAULT 0,       -- admin must approve
    approved_by    INT,
    approved_at    DATETIME,
    is_published   TINYINT(1) NOT NULL DEFAULT 0,       -- results visible to students
    created_at     TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id)  REFERENCES academic_sessions(session_id),
    FOREIGN KEY (created_by)  REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id),
    CONSTRAINT chk_exam_dates CHECK (end_date >= start_date),
    INDEX idx_session    (session_id),
    INDEX idx_approved   (is_approved),
    INDEX idx_published  (is_published)
);

-- Which classes are assigned to an exam (teacher may select multiple)
CREATE TABLE IF NOT EXISTS exam_classes (
    id       INT PRIMARY KEY AUTO_INCREMENT,
    exam_id  INT NOT NULL,
    class_id INT NOT NULL,
    FOREIGN KEY (exam_id)  REFERENCES examinations(exam_id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(class_id)     ON DELETE CASCADE,
    UNIQUE KEY unique_exam_class (exam_id, class_id)
);

-- Detailed schedule: one row per exam × class × subject
-- This is the single source of truth for marks, date, and time
CREATE TABLE IF NOT EXISTS exam_schedules (
    schedule_id  INT PRIMARY KEY AUTO_INCREMENT,
    exam_id      INT            NOT NULL,
    class_id     INT            NOT NULL,
    subject_id   INT            NOT NULL,
    exam_date    DATE           NOT NULL,
    start_time   TIME           NOT NULL,
    end_time     TIME           NOT NULL,
    full_marks   DECIMAL(5,2)   NOT NULL DEFAULT 100.00,
    pass_marks   DECIMAL(5,2)   NOT NULL DEFAULT 35.00,
    room_number  VARCHAR(20),
    created_at   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id)    REFERENCES examinations(exam_id) ON DELETE CASCADE,
    FOREIGN KEY (class_id)   REFERENCES classes(class_id)     ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id)  ON DELETE CASCADE,
    UNIQUE KEY unique_schedule (exam_id, class_id, subject_id),
    CONSTRAINT chk_marks      CHECK (pass_marks <= full_marks AND full_marks > 0),
    CONSTRAINT chk_exam_times CHECK (end_time > start_time)
);

-- Results: one row per student × schedule
CREATE TABLE IF NOT EXISTS results (
    result_id      INT PRIMARY KEY AUTO_INCREMENT,
    student_id     INT NOT NULL,
    schedule_id    INT NOT NULL,                        -- links to exam_schedules
    session_id     INT NOT NULL,
    marks_obtained DECIMAL(5,2),                        -- NULL if absent
    is_absent      TINYINT(1)   NOT NULL DEFAULT 0,
    -- Grade computed by application logic, stored for reporting
    grade          VARCHAR(5),
    remarks        TEXT,
    entered_by     INT NOT NULL,
    entered_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id)  REFERENCES students(student_id)       ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES exam_schedules(schedule_id) ON DELETE CASCADE,
    FOREIGN KEY (session_id)  REFERENCES academic_sessions(session_id),
    FOREIGN KEY (entered_by)  REFERENCES users(user_id),
    UNIQUE KEY unique_result (student_id, schedule_id),
    -- marks_obtained must be between 0 and full_marks (enforced in app too)
    CONSTRAINT chk_marks_range CHECK (
        marks_obtained IS NULL
        OR marks_obtained >= 0
    )
);


-- ============================================================
-- MODULE 8: ATTENDANCE
-- ============================================================

CREATE TABLE IF NOT EXISTS attendance (
    attendance_id       INT PRIMARY KEY AUTO_INCREMENT,
    student_id          INT  NOT NULL,
    class_id            INT  NOT NULL,
    session_id          INT  NOT NULL,
    attendance_date     DATE NOT NULL,
    status              ENUM('Present','Absent','Late','Excused') NOT NULL,
    remarks             TEXT,
    -- For excused/leave: optional document (store file path)
    leave_document      VARCHAR(255),
    marked_by           INT  NOT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (class_id)   REFERENCES classes(class_id)    ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(session_id),
    FOREIGN KEY (marked_by)  REFERENCES users(user_id),
    -- A student can only have one attendance record per date per class per session
    UNIQUE KEY unique_attendance (student_id, class_id, session_id, attendance_date),
    INDEX idx_date    (attendance_date),
    INDEX idx_student (student_id),
    INDEX idx_session (session_id)
);


-- ============================================================
-- MODULE 9: TIMETABLE
-- ============================================================

CREATE TABLE IF NOT EXISTS timetable (
    timetable_id  INT PRIMARY KEY AUTO_INCREMENT,
    class_id      INT NOT NULL,
    subject_id    INT NOT NULL,
    teacher_id    INT,
    session_id    INT NOT NULL,
    day_of_week   ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
    period_number TINYINT UNSIGNED NOT NULL,
    start_time    TIME NOT NULL,
    end_time      TIME NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id)   REFERENCES classes(class_id)    ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id) ON DELETE SET NULL,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(session_id),
    -- A class cannot have two subjects in the same period on the same day
    UNIQUE KEY unique_slot_class   (class_id, session_id, day_of_week, period_number),
    -- A teacher cannot be in two places at once
    UNIQUE KEY unique_slot_teacher (teacher_id, session_id, day_of_week, period_number),
    CONSTRAINT chk_tt_times CHECK (end_time > start_time)
);


-- ============================================================
-- MODULE 10: FEES
-- ============================================================

CREATE TABLE IF NOT EXISTS fees (
    fee_id                   INT PRIMARY KEY AUTO_INCREMENT,
    student_id               INT            NOT NULL,
    session_id               INT            NOT NULL,
    category_id              INT            NOT NULL,   -- replaces free-text fee_type
    service_id               INT,                       -- NULL for non-service fees
    amount                   DECIMAL(10,2)  NOT NULL,
    due_date                 DATE           NOT NULL,
    payment_status           ENUM('Pending','Paid','Overdue','Waived') NOT NULL DEFAULT 'Pending',
    payment_date             DATE,
    payment_method           ENUM('Cash','Bank Transfer','Online','Cheque','Other'),
    receipt_number           VARCHAR(50)    UNIQUE,
    payment_proof            VARCHAR(255),              -- uploaded file path
    admission_application_id INT,                       -- linked during admission
    remarks                  TEXT,
    created_by               INT            NOT NULL,
    created_at               TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at               TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id)   REFERENCES students(student_id)     ON DELETE CASCADE,
    FOREIGN KEY (session_id)   REFERENCES academic_sessions(session_id),
    FOREIGN KEY (category_id)  REFERENCES fee_categories(category_id),
    FOREIGN KEY (service_id)   REFERENCES services(service_id)     ON DELETE SET NULL,
    FOREIGN KEY (created_by)   REFERENCES users(user_id),
    CONSTRAINT chk_fee_amount  CHECK (amount > 0),
    CONSTRAINT chk_payment_date CHECK (payment_date IS NULL OR payment_date >= due_date - INTERVAL 1 YEAR),
    INDEX idx_student  (student_id),
    INDEX idx_session  (session_id),
    INDEX idx_status   (payment_status),
    INDEX idx_due_date (due_date)
);

-- Which services a student uses (used to auto-assign service fees)
CREATE TABLE IF NOT EXISTS student_services (
    student_service_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id         INT NOT NULL,
    service_id         INT NOT NULL,
    session_id         INT NOT NULL,
    enrollment_date    DATE,
    is_active          TINYINT(1) NOT NULL DEFAULT 1,
    created_at         TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE RESTRICT,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(session_id),
    UNIQUE KEY unique_student_service (student_id, service_id, session_id)
);


-- ============================================================
-- MODULE 11: NOTICES
-- ============================================================

CREATE TABLE IF NOT EXISTS notices (
    notice_id       INT PRIMARY KEY AUTO_INCREMENT,
    title           VARCHAR(200) NOT NULL,
    description     TEXT         NOT NULL,
    notice_date     DATE         NOT NULL,
    expiry_date     DATE,                               -- auto-hide after this date
    target_audience ENUM('All','Students','Teachers','Parents') NOT NULL DEFAULT 'All',
    file_attachment VARCHAR(255),                       -- uploaded file path
    link_url        VARCHAR(500),
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    posted_by       INT        NOT NULL,
    created_at      TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    CONSTRAINT chk_notice_expiry CHECK (expiry_date IS NULL OR expiry_date >= notice_date),
    INDEX idx_audience   (target_audience),
    INDEX idx_active     (is_active),
    INDEX idx_expiry     (expiry_date)
);


-- ============================================================
-- MODULE 12: ADMISSIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS admission_applications (
    admission_application_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name               VARCHAR(100) NOT NULL,
    last_name                VARCHAR(100) NOT NULL,
    email                    VARCHAR(150) NOT NULL,
    phone                    VARCHAR(15)  NOT NULL,
    date_of_birth            DATE         NOT NULL,
    gender                   ENUM('Male','Female','Other') NOT NULL,
    father_name              VARCHAR(100),
    mother_name              VARCHAR(100),
    parent_email             VARCHAR(150),
    parent_phone             VARCHAR(15),
    address                  TEXT,
    city                     VARCHAR(100),
    class_id                 INT NOT NULL,
    session_id               INT NOT NULL,
    previous_school          VARCHAR(200),
    application_status       ENUM('Pending','Under Review','Approved','Rejected','Admitted') NOT NULL DEFAULT 'Pending',
    admin_remarks            TEXT,
    rejection_reason         TEXT,
    applied_on               TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_by              INT,
    reviewed_on              DATETIME,
    approved_by              INT,
    approved_on              DATETIME,
    created_at               TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at               TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id)    REFERENCES classes(class_id)             ON DELETE RESTRICT,
    FOREIGN KEY (session_id)  REFERENCES academic_sessions(session_id) ON DELETE RESTRICT,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id),
    CONSTRAINT chk_app_phone    CHECK (phone        REGEXP '^[0-9]{7,15}$'),
    CONSTRAINT chk_app_par_phone CHECK (parent_phone REGEXP '^[0-9]{7,15}$'),
    INDEX idx_status      (application_status),
    INDEX idx_email       (email),
    INDEX idx_session     (session_id),
    INDEX idx_applied     (applied_on)
);

-- Documents uploaded for each application (separate table, not JSON)
CREATE TABLE IF NOT EXISTS admission_documents (
    document_id              INT PRIMARY KEY AUTO_INCREMENT,
    admission_application_id INT          NOT NULL,
    document_type            VARCHAR(100) NOT NULL,     -- e.g. 'Birth Certificate'
    file_path                VARCHAR(255) NOT NULL,
    uploaded_at              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admission_application_id)
        REFERENCES admission_applications(admission_application_id) ON DELETE CASCADE
);

-- Add back-references now that admission_applications exists
ALTER TABLE students
    ADD CONSTRAINT fk_student_application
    FOREIGN KEY (admission_application_id)
    REFERENCES admission_applications(admission_application_id) ON DELETE SET NULL;

ALTER TABLE fees
    ADD CONSTRAINT fk_fee_application
    FOREIGN KEY (admission_application_id)
    REFERENCES admission_applications(admission_application_id) ON DELETE SET NULL;


-- ============================================================
-- MODULE 13: DEFERRED FKs (created after all tables exist)
-- ============================================================

ALTER TABLE academic_sessions
    ADD CONSTRAINT fk_session_created_by
    FOREIGN KEY (created_by) REFERENCES users(user_id);


-- ============================================================
-- MODULE 14: STUDENT PERFORMANCE SUMMARY (derived/cache table)
-- ============================================================

CREATE TABLE IF NOT EXISTS student_performance_summary (
    summary_id          INT PRIMARY KEY AUTO_INCREMENT,
    student_id          INT NOT NULL UNIQUE,
    current_session_id  INT,
    total_sessions      INT          NOT NULL DEFAULT 0,
    cumulative_pct      DECIMAL(5,2),
    average_pct         DECIMAL(5,2),
    total_a_grades      INT          NOT NULL DEFAULT 0,
    total_b_grades      INT          NOT NULL DEFAULT 0,
    total_f_grades      INT          NOT NULL DEFAULT 0,
    times_promoted      INT          NOT NULL DEFAULT 0,
    times_detained      INT          NOT NULL DEFAULT 0,
    last_updated        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id)        REFERENCES students(student_id)          ON DELETE CASCADE,
    FOREIGN KEY (current_session_id) REFERENCES academic_sessions(session_id)
);


-- ============================================================
-- RE-ENABLE FOREIGN KEY CHECKS
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================
-- SEED DATA
-- ============================================================

-- Academic sessions
INSERT INTO academic_sessions (session_name, start_date, end_date, is_current, description) VALUES
('2024-25', '2024-04-01', '2025-03-31', 0, 'Academic Year 2024-2025'),
('2025-26', '2025-04-01', '2026-03-31', 1, 'Academic Year 2025-2026 (Current)'),
('2026-27', '2026-04-01', '2027-03-31', 0, 'Academic Year 2026-2027')
ON DUPLICATE KEY UPDATE session_name = VALUES(session_name);

-- Fee categories
INSERT INTO fee_categories (category_name, description) VALUES
('Tuition Fee',   'Monthly tuition charges'),
('Admission Fee', 'One-time admission fee'),
('Exam Fee',      'Fee per examination'),
('Library Fee',   'Annual library charges'),
('Laboratory Fee','Science and computer lab charges'),
('Development Fee','Annual school development fee')
ON DUPLICATE KEY UPDATE category_name = VALUES(category_name);

-- Services
INSERT INTO services (service_name, description) VALUES
('Transport/Bus',    'School transportation and bus facility'),
('Hostel/Boarding',  'Residential facility and accommodation'),
('Meals',            'Lunch and meal services'),
('Uniform',          'School uniform and accessories'),
('Sports Activities','Sports coaching and activities'),
('Tuition Coaching', 'Extra coaching and tuition classes')
ON DUPLICATE KEY UPDATE service_name = VALUES(service_name);

-- Admission settings
INSERT INTO admission_settings (setting_name, setting_value, description, setting_type) VALUES
('admission_form_open',     'yes',                              'Is admission form open?',                     'boolean'),
('application_fee_amount',  '500',                              'Application fee in rupees',                   'number'),
('application_deadline',    '2025-12-31',                       'Last date for applications',                  'date'),
('required_documents',      'Birth Certificate,Transfer Certificate,Address Proof', 'Required documents list','text'),
('instructions_for_applicants', 'Fill all fields accurately. Documents will be verified during admission.', 'Form instructions', 'text'),
('reapplication_allowed',   'yes',                              'Can rejected applicants reapply?',            'boolean'),
('school_email_for_contact','admissions@school.com',            'Email for applicant queries',                 'email')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Admin user
-- Password: 'Admin@1234' — generated, then hashed with bcrypt ($2y$12$...)
-- In PHP: password_hash('Admin@1234', PASSWORD_BCRYPT)
-- Replace the hash below with the output of that PHP call
INSERT INTO users (username, password_hash, user_type, email) VALUES
('admin', '$2y$12$REPLACE_THIS_WITH_REAL_BCRYPT_HASH', 'admin', 'admin@school.com')
ON DUPLICATE KEY UPDATE username = VALUES(username);

-- Sample teachers
-- Passwords: 'Teacher@123' each — hash with PHP before use
-- These are placeholder hashes; regenerate in PHP
INSERT INTO users (username, password_hash, user_type, email) VALUES
('teacher_rajesh', '$2y$12$REPLACE_THIS_WITH_REAL_BCRYPT_HASH', 'teacher', 'rajesh.kumar@school.com'),
('teacher_priya',  '$2y$12$REPLACE_THIS_WITH_REAL_BCRYPT_HASH', 'teacher', 'priya.sharma@school.com')
ON DUPLICATE KEY UPDATE username = VALUES(username);

INSERT INTO teachers (user_id, first_name, last_name, gender, phone, email, qualification, subject_specialization, joining_date) VALUES
(2, 'Rajesh', 'Kumar',  'Male',   '9876543210', 'rajesh.kumar@school.com', 'M.Sc. Mathematics', 'Mathematics', '2024-01-15'),
(3, 'Priya',  'Sharma', 'Female', '9876543211', 'priya.sharma@school.com', 'M.A. English',      'English',     '2024-02-01')
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- Classes (no academic_year column — session_id is used instead)
INSERT INTO classes (class_name, section, class_teacher_id) VALUES
('Class 1', 'A', 1),
('Class 2', 'A', 2),
('Class 3', 'A', NULL),
('Class 4', 'A', NULL),
('Class 5', 'A', NULL),
('Class 6', 'A', NULL),
('Class 7', 'A', NULL),
('Class 8', 'A', NULL),
('Class 9', 'A', NULL),
('Class 10','A', NULL)
ON DUPLICATE KEY UPDATE class_name = VALUES(class_name);

-- Subjects
INSERT INTO subjects (subject_name, subject_code, description) VALUES
('Mathematics',     'MATH101', 'Basic and advanced mathematics'),
('English',         'ENG101',  'English language and literature'),
('Science',         'SCI101',  'General science'),
('Social Studies',  'SS101',   'History, geography, and civics'),
('Computer Science','CS101',   'Computer fundamentals and programming'),
('Hindi',           'HIN101',  'Hindi language and literature')
ON DUPLICATE KEY UPDATE subject_code = VALUES(subject_code);

-- Class–subject assignments for session 2025-26 (session_id = 2)
INSERT INTO class_subjects (class_id, subject_id, teacher_id, session_id) VALUES
(1,1,1,2),(1,2,2,2),(1,3,1,2),
(2,1,1,2),(2,2,2,2),(2,4,2,2),
(3,1,1,2),(3,2,2,2),(3,5,1,2)
ON DUPLICATE KEY UPDATE teacher_id = VALUES(teacher_id);

-- Promotion rules (Class 1→2, 2→3, ... 9→10)
INSERT INTO class_promotion_rules (from_class_id, to_class_id, min_percentage, min_subjects_passed, max_repeat_count) VALUES
(1,2,35,3,2),(2,3,35,3,2),(3,4,35,4,2),(4,5,35,4,2),
(5,6,35,4,2),(6,7,40,5,2),(7,8,40,5,2),(8,9,40,5,2),(9,10,40,5,2)
ON DUPLICATE KEY UPDATE min_percentage = VALUES(min_percentage);

-- Sample notice
INSERT INTO notices (title, description, notice_date, target_audience, is_active, posted_by) VALUES
('Welcome to Academic Year 2025-26',
 'Classes begin from April 1st 2025. All students must carry their ID cards.',
 '2025-04-01', 'All', 1, 1),
('Annual Sports Day',
 'Annual Sports Day will be held on May 25th 2025. All students are requested to participate.',
 '2025-05-20', 'Students', 1, 1)
ON DUPLICATE KEY UPDATE title = VALUES(title);


-- ============================================================
-- END OF SCHEMA
-- ============================================================
-- How to use:
--   mysql -u root -p < school_management_schema.sql
--   Or paste into phpMyAdmin SQL tab and click Go
--
-- Password workflow (PHP):
--   1. Generate plain password:  $plain = 'Student@' . rand(1000,9999);
--   2. Hash it:                  $hash  = password_hash($plain, PASSWORD_BCRYPT);
--   3. Email plain to user:      mail($email, 'Your login', "Password: $plain");
--   4. Store hash in DB:         INSERT ... password_hash = '$hash' ...
--   5. Verify on login:          password_verify($input, $stored_hash)
-- ============================================================