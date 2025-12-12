-- This file adds support for hierarchical questions with main and secondary questions
-- It allows step-by-step answering with scoring rules

-- 1. Add profile data fields to users table if not present
-- ALTER TABLE users ADD COLUMN height_cm INT UNSIGNED DEFAULT NULL COMMENT 'Height in centimeters';
-- ALTER TABLE users ADD COLUMN weight_kg DECIMAL(5, 2) UNSIGNED DEFAULT NULL COMMENT 'Weight in kilograms';
-- Note: age is calculated from birthdate

-- 2. Modify questions table to support hierarchy
ALTER TABLE questions ADD COLUMN parent_question_id BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'If set, this is a secondary/tertiary question linked to a parent' AFTER pillar_id;
ALTER TABLE questions ADD COLUMN question_type ENUM('main', 'secondary', 'tertiary') DEFAULT 'main' COMMENT 'Type of question in hierarchy' AFTER parent_question_id;
ALTER TABLE questions ADD COLUMN answer_type ENUM('binary', 'number', 'choice', 'text') DEFAULT 'binary' COMMENT 'Expected answer format' AFTER question_type;
ALTER TABLE questions ADD COLUMN show_on_answer VARCHAR(50) DEFAULT NULL COMMENT 'Show this question only if parent answer equals this value (e.g., "ja", "nee", "1")' AFTER answer_type;
ALTER TABLE questions ADD KEY `fk_parent_question` (parent_question_id);
ALTER TABLE questions ADD CONSTRAINT `fk_parent_question` FOREIGN KEY (parent_question_id) REFERENCES questions(id) ON DELETE CASCADE;

-- 3. Create question_scoring_rules table for flexible scoring
CREATE TABLE IF NOT EXISTS `question_scoring_rules` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `question_id` BIGINT(20) UNSIGNED NOT NULL,
  `condition_type` ENUM('equals', 'greater_than', 'less_than', 'contains_keyword', 'range') NOT NULL COMMENT 'Type of condition to evaluate',
  `condition_value` VARCHAR(255) DEFAULT NULL COMMENT 'Value to compare against (e.g., "ja", "50", "water")',
  `condition_min` INT DEFAULT NULL COMMENT 'For range conditions',
  `condition_max` INT DEFAULT NULL COMMENT 'For range conditions',
  `base_score` DECIMAL(5, 2) NOT NULL DEFAULT 0 COMMENT 'Base score for this condition',
  `multiplier` DECIMAL(3, 2) NOT NULL DEFAULT 1.00 COMMENT 'Multiplier for score (e.g., 1.5 for water)',
  `penalty` DECIMAL(5, 2) NOT NULL DEFAULT 0 COMMENT 'Penalty if condition exceeded',
  `max_daily_value` INT DEFAULT NULL COMMENT 'Max allowed per day (e.g., 8 glasses of water)',
  `excess_penalty_per_unit` DECIMAL(5, 2) DEFAULT 0 COMMENT 'Penalty per unit above max',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_rule` (question_id, condition_type, condition_value),
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT 'Stores scoring rules for dynamic health calculation';

-- 4. Update answers table to track question hierarchy
ALTER TABLE answers ADD COLUMN question_parent_id BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'Parent question ID if this is a secondary answer' AFTER question_id;
ALTER TABLE answers ADD COLUMN answer_sequence INT DEFAULT 1 COMMENT 'Order in which questions were answered' AFTER answer_text;
ALTER TABLE answers ADD KEY `fk_parent_question_answer` (question_parent_id);

-- 5. Create user_health_scores table to store calculated health scores
CREATE TABLE IF NOT EXISTS `user_health_scores` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `score_date` DATE NOT NULL,
  `overall_score` DECIMAL(5, 2) NOT NULL COMMENT 'Overall health score 0-100',
  `pillar_scores` JSON DEFAULT NULL COMMENT 'Score breakdown by pillar',
  `calculation_details` JSON DEFAULT NULL COMMENT 'Details of how score was calculated',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_daily_score` (user_id, score_date),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT 'Stores calculated health scores for users';

-- 6. Create category_keywords table for smart scoring (e.g., "water" = 1.5x multiplier)
CREATE TABLE IF NOT EXISTS `category_keywords` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `pillar_id` TINYINT(3) UNSIGNED NOT NULL,
  `keyword` VARCHAR(100) NOT NULL COMMENT 'Keyword to search for in question text',
  `subcategory` VARCHAR(100) NOT NULL COMMENT 'Subcategory (e.g., "water", "food", "soft_drugs", "hard_drugs")',
  `multiplier` DECIMAL(3, 2) NOT NULL DEFAULT 1.00 COMMENT 'Score multiplier for this subcategory',
  `max_daily_recommended` INT DEFAULT NULL COMMENT 'Maximum recommended per day',
  `unit` VARCHAR(50) DEFAULT NULL COMMENT 'Unit of measurement (e.g., "glasses", "grams", "hours")',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_keyword` (pillar_id, keyword),
  FOREIGN KEY (pillar_id) REFERENCES pillars(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT 'Maps keywords to scoring rules';
