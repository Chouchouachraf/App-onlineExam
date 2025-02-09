-- Create table to link exams with classrooms
CREATE TABLE IF NOT EXISTS exam_classrooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_id INT NOT NULL,
    classroom_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
    UNIQUE KEY unique_exam_classroom (exam_id, classroom_id)
);
