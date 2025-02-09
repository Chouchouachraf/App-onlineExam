-- Add total_score column to exam_submissions table
ALTER TABLE exam_submissions
ADD COLUMN total_score DECIMAL(5,2) DEFAULT NULL;
