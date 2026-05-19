-- Run this in phpMyAdmin SQL tab to update notices schema
ALTER TABLE notices ADD COLUMN file_attachment VARCHAR(255) AFTER target_audience;
ALTER TABLE notices ADD COLUMN link_url VARCHAR(255) AFTER file_attachment;
