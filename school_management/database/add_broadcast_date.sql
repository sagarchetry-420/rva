-- Add broadcast_date column to notices table
ALTER TABLE notices ADD COLUMN broadcast_date DATETIME NULL DEFAULT NULL AFTER is_broadcasted;

-- Create index for better query performance
CREATE INDEX idx_broadcast_date ON notices(broadcast_date);
