ALTER TABLE notices ADD COLUMN broadcast_date DATETIME NULL DEFAULT NULL AFTER is_broadcasted;
CREATE INDEX idx_broadcast_date ON notices(broadcast_date);