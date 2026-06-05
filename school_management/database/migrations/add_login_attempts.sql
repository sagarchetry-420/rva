CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    identifier VARCHAR(150) NOT NULL,
    attempts INT DEFAULT 1,
    last_attempt DATETIME NOT NULL,
    UNIQUE KEY ip_identifier (ip_address, identifier)
);
