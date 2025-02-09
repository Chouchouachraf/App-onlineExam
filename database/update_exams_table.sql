-- First, let's add subject and total_marks columns if they don't exist
ALTER TABLE exams
ADD COLUMN IF NOT EXISTS subject VARCHAR(100) DEFAULT 'Not specified',
ADD COLUMN IF NOT EXISTS total_marks INT DEFAULT 0;

-- Drop foreign key constraints if they exist
SET FOREIGN_KEY_CHECKS=0;

-- Remove old class_id and subject_id columns if they exist
ALTER TABLE exams
DROP FOREIGN KEY IF EXISTS fk_exams_subject,
DROP FOREIGN KEY IF EXISTS fk_exams_class,
DROP INDEX IF EXISTS subject_id,
DROP INDEX IF EXISTS class_id,
DROP COLUMN IF EXISTS class_id,
DROP COLUMN IF EXISTS subject_id;

SET FOREIGN_KEY_CHECKS=1;

-- Create exam_classrooms table if it doesn't exist
CREATE TABLE IF NOT EXISTS exam_classrooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_id INT NOT NULL,
    classroom_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
    UNIQUE KEY unique_exam_classroom (exam_id, classroom_id)
);
