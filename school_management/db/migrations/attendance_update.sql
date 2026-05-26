-- Convert any existing 'Late' or 'Excused' to 'Absent' (or 'Present' depending on school policy).
-- We'll default to 'Absent' with remarks.
UPDATE attendance SET remarks = CONCAT('Was Late. ', IFNULL(remarks, '')), status = 'Absent' WHERE status = 'Late';
UPDATE attendance SET remarks = CONCAT('Was Excused. ', IFNULL(remarks, '')), status = 'Absent' WHERE status = 'Excused';

-- Alter the enum to include 'Leave' and 'Half Leave', and remove 'Late' and 'Excused'
ALTER TABLE attendance 
MODIFY COLUMN status ENUM('Present','Absent','Leave','Half Leave') NOT NULL;
