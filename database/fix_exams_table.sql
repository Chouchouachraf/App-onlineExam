-- First, let's remove the existing foreign key constraint if it exists
SET FOREIGN_KEY_CHECKS=0;
ALTER TABLE exams
DROP FOREIGN KEY IF EXISTS fk_created_by;
SET FOREIGN_KEY_CHECKS=1;

-- Now let's add or modify columns one by one
ALTER TABLE exams
MODIFY COLUMN subject VARCHAR(100) DEFAULT 'Not specified',
MODIFY COLUMN total_marks INT DEFAULT 0,
MODIFY COLUMN duration INT DEFAULT 60,
MODIFY COLUMN created_by INT,
MODIFY COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
MODIFY COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add the foreign key constraint
ALTER TABLE exams
ADD CONSTRAINT fk_created_by FOREIGN KEY (created_by) REFERENCES users(id);

-- Update existing records to have default values where null
UPDATE exams SET 
    subject = COALESCE(subject, 'Not specified'),
    total_marks = COALESCE(total_marks, 0),
    duration = COALESCE(duration, 60)
WHERE subject IS NULL OR total_marks IS NULL OR duration IS NULL;
