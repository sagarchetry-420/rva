-- Add payment_proof column to fees table
ALTER TABLE fees ADD COLUMN payment_proof VARCHAR(255) AFTER receipt_number;
