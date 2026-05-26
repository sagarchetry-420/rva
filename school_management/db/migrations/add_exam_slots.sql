CREATE TABLE IF NOT EXISTS exam_slots (
    slot_id INT PRIMARY KEY AUTO_INCREMENT,
    exam_id INT NOT NULL,
    class_id INT NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    full_marks DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    pass_marks DECIMAL(5,2) NOT NULL DEFAULT 35.00,
    UNIQUE KEY (exam_id, class_id, start_time, end_time),
    FOREIGN KEY (exam_id) REFERENCES examinations(exam_id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE
);
