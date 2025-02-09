-- Add teacher_id column to exams table if it doesn't exist
ALTER TABLE exams
ADD COLUMN IF NOT EXISTS teacher_id INT,
ADD CONSTRAINT fk_teacher_id FOREIGN KEY (teacher_id) REFERENCES users(id);

-- Update existing exams to set teacher_id from created_by if it exists
UPDATE exams SET teacher_id = created_by WHERE teacher_id IS NULL AND created_by IS NOT NULL;
