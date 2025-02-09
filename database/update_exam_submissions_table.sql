-- Update exam_submissions table structure
CREATE TABLE IF NOT EXISTS exam_submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_score DECIMAL(5,2) DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'submitted',
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);
