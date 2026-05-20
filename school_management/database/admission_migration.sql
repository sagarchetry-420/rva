-- ============================================================
-- School Management System - Student Admission Application System
-- Author: AI Assistant
-- Date: 2026-05-21
-- ============================================================

USE school_management;

-- ============================================================
-- 1. ADMISSION APPLICATIONS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS admission_applications (
    admission_application_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    father_name VARCHAR(100),
    mother_name VARCHAR(100),
    parent_email VARCHAR(120),
    parent_phone VARCHAR(15),
    address TEXT,
    city VARCHAR(50),
    class_id INT NOT NULL,
    session_id INT NOT NULL,
    previous_school VARCHAR(200),
    documents_submitted LONGTEXT COMMENT 'JSON array of uploaded file paths',
    application_status ENUM('Pending', 'Approved', 'Rejected', 'Admitted') DEFAULT 'Pending',
    admin_remarks TEXT,
    rejection_reason TEXT,
    applied_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_on DATETIME NULL,
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (class_id) REFERENCES classes(class_id),
    FOREIGN KEY (session_id) REFERENCES academic_sessions(session_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id),

    INDEX idx_email (email),
    INDEX idx_status (application_status),
    INDEX idx_class (class_id),
    INDEX idx_session (session_id),
    INDEX idx_applied_date (applied_on),
    INDEX idx_status_class (application_status, class_id)
);

-- ============================================================
-- 2. ADMISSION SETTINGS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS admission_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_name VARCHAR(100) NOT NULL UNIQUE,
    setting_value VARCHAR(500),
    description TEXT,
    setting_type ENUM('boolean', 'text', 'number', 'date', 'email') DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_setting_name (setting_name)
);

-- ============================================================
-- 3. SAMPLE ADMISSION SETTINGS DATA
-- ============================================================
INSERT INTO admission_settings (setting_name, setting_value, description, setting_type)
VALUES
('admission_form_open', 'yes', 'Is the admission form open for applications? (yes/no)', 'boolean'),
('application_fee_amount', '500', 'Admission application fee amount in rupees', 'number'),
('application_deadline', '2024-12-31', 'Last date for accepting new applications (YYYY-MM-DD)', 'date'),
('required_documents', 'Birth Certificate,Transfer Certificate,Address Proof', 'Comma-separated list of required documents', 'text'),
('instructions_for_applicants', 'Please fill all fields accurately. Documents will be verified during admission. Contact office for any queries.', 'Instructions shown on application form', 'text'),
('reapplication_allowed', 'yes', 'Can rejected applicants reapply in same season? (yes/no)', 'boolean'),
('school_email_for_contact', 'admissions@school.com', 'Email address for applicant inquiries', 'email')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

-- ============================================================
-- 4. ALTER TABLE TO ADD APPLICATION REFERENCE IN FEES TABLE
-- ============================================================
-- This allows linking a fee record to an application before student is created
ALTER TABLE fees ADD COLUMN admission_application_id INT;
ALTER TABLE fees ADD CONSTRAINT fk_fees_admission_app
    FOREIGN KEY (admission_application_id) REFERENCES admission_applications(admission_application_id) ON DELETE SET NULL;

-- ============================================================
-- 5. ALTER STUDENTS TABLE TO ADD APPLICATION REFERENCE
-- ============================================================
-- This links a student record to their original application
ALTER TABLE students ADD COLUMN admission_application_id INT;
ALTER TABLE students ADD CONSTRAINT fk_students_admission_app
    FOREIGN KEY (admission_application_id) REFERENCES admission_applications(admission_application_id) ON DELETE SET NULL;

-- ============================================================
-- 6. CREATE UPLOADS DIRECTORY REFERENCE (Info only)
-- ============================================================
-- Physical upload directory should be: school_management/uploads/admission_documents/
-- Structure: /uploads/admission_documents/{application_id}/{filename}
-- Example: /uploads/admission_documents/145/birth_certificate.pdf

-- ============================================================
-- SUCCESS
-- ============================================================
-- Run this file:
-- mysql -u root -p school_management < database/admission_migration.sql
--
-- Or in phpMyAdmin: Copy-paste this entire content into SQL tab and click Go
