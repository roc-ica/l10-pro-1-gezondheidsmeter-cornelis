
-- Update questions table with missing columns
ALTER TABLE `questions` ADD COLUMN IF NOT EXISTS `is_main_question` tinyint(1) DEFAULT 1;
ALTER TABLE `questions` ADD COLUMN IF NOT EXISTS `is_drugs_question` tinyint(1) DEFAULT 0;
ALTER TABLE `questions` ADD COLUMN IF NOT EXISTS `parent_question_id` bigint(20) UNSIGNED DEFAULT NULL;
ALTER TABLE `questions` ADD COLUMN IF NOT EXISTS `question_type` enum('main','secondary','tertiary') DEFAULT 'main';
ALTER TABLE `questions` ADD COLUMN IF NOT EXISTS `answer_type` enum('binary','number','choice','text') DEFAULT 'binary';
ALTER TABLE `questions` ADD COLUMN IF NOT EXISTS `show_on_answer` varchar(50) DEFAULT NULL;

-- Create missing tables
CREATE TABLE IF NOT EXISTS `user_health_scores` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `score_date` date NOT NULL,
  `overall_score` decimal(5,2) NOT NULL,
  `pillar_scores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pillar_scores`)),
  `calculation_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`calculation_details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_date` (`user_id`, `score_date`),
  KEY `user_id` (`user_id`),
  KEY `score_date` (`score_date`),
  CONSTRAINT `user_health_scores_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `question_scoring_rules` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id` bigint(20) UNSIGNED NOT NULL,
  `condition_type` varchar(50) NOT NULL COMMENT 'equals, contains_keyword, greater_than, less_than, range',
  `condition_value` varchar(255) DEFAULT NULL,
  `condition_min` int(11) DEFAULT NULL,
  `condition_max` int(11) DEFAULT NULL,
  `base_score` decimal(5,2) NOT NULL,
  `multiplier` decimal(3,2) DEFAULT 1.00,
  `max_daily_value` int(11) DEFAULT NULL,
  `excess_penalty_per_unit` decimal(3,2) DEFAULT 0.50,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `question_scoring_rules_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `category_keywords` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pillar_id` tinyint(3) UNSIGNED NOT NULL,
  `keyword` varchar(255) NOT NULL,
  `subcategory` varchar(255) DEFAULT NULL,
  `multiplier` decimal(3,2) DEFAULT 1.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `pillar_id` (`pillar_id`),
  KEY `keyword` (`keyword`),
  CONSTRAINT `category_keywords_ibfk_1` FOREIGN KEY (`pillar_id`) REFERENCES `pillars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
