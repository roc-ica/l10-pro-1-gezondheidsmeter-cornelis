-- Migration to add missing 'is_main_question' column to questions table
-- This fixes the "Unknown column 'is_main_question'" error

-- 1. Add the column if it doesn't exist
ALTER TABLE `questions` ADD COLUMN IF NOT EXISTS `is_main_question` TINYINT(1) DEFAULT 1 AFTER `active`;

-- 2. Update existing secondary questions (those with a parent) to have is_main_question = 0
UPDATE `questions` 
SET `is_main_question` = 0 
WHERE `parent_question_id` IS NOT NULL;

-- 3. Ensure top-level questions have is_main_question = 1
UPDATE `questions` 
SET `is_main_question` = 1 
WHERE `parent_question_id` IS NULL;
