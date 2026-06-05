-- Migration: Add billing_cycle to services and create class_service_fees table

ALTER TABLE services 
ADD COLUMN billing_cycle ENUM('One-Time', 'Monthly', 'Quarterly', 'Term-wise', 'Yearly') NOT NULL DEFAULT 'One-Time';

CREATE TABLE IF NOT EXISTS class_service_fees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service_id INT NOT NULL,
    class_id INT NOT NULL,
    fee_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    UNIQUE KEY unique_class_service (service_id, class_id)
);
