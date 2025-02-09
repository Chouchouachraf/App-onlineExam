-- Add missing columns to exams table if they don't exist
ALTER TABLE exams
ADD COLUMN IF NOT EXISTS subject VARCHAR(100) DEFAULT 'Not specified',
ADD COLUMN IF NOT EXISTS total_marks INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS duration INT DEFAULT 60,
ADD COLUMN IF NOT EXISTS created_by INT,
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD CONSTRAINT fk_created_by FOREIGN KEY (created_by) REFERENCES users(id);
