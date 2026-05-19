-- ============================================================
-- Update Schema: Forgot Password & Student Email
-- ============================================================

USE school_management;

-- 1. Add email column to students table
ALTER TABLE students ADD COLUMN email VARCHAR(100) AFTER phone;

-- 2. Add password reset fields to users table
ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL AFTER email;
ALTER TABLE users ADD COLUMN reset_expires DATETIME NULL AFTER reset_token;