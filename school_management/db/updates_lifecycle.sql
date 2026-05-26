ALTER TABLE students ADD COLUMN leaving_date DATE NULL;
ALTER TABLE students ADD COLUMN leaving_reason VARCHAR(100) NULL;

ALTER TABLE teachers ADD COLUMN status ENUM('Active','Inactive') DEFAULT 'Active';
ALTER TABLE teachers ADD COLUMN leaving_date DATE NULL;
ALTER TABLE teachers ADD COLUMN leaving_reason VARCHAR(100) NULL;
