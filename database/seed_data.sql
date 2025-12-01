-- Seed Data for Gezondheidsmeter
-- Generated for User ID 1 (Test User)
-- Range: Last 14 days

-- 1. Insert Test User (if not exists)
INSERT IGNORE INTO `users` (`id`, `username`, `email`, `password_hash`, `display_name`) VALUES
(1, 'testuser', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test User');

-- 2. Insert Daily Entries and Answers

-- Day 1: 2025-11-18
INSERT IGNORE INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) VALUES (1, '2025-11-18', NOW());
SET @entry_id = (SELECT id FROM `daily_entries` WHERE user_id = 1 AND entry_date = '2025-11-18');
INSERT INTO `answers` (`entry_id`, `question_id`, `score`) VALUES 
(@entry_id, 1, 8), -- Water
(@entry_id, 2, 9), -- Frisdrank
(@entry_id, 3, 6), -- Beweging
(@entry_id, 4, 7), -- Slaap
(@entry_id, 5, 8), -- Schermtijd
(@entry_id, 6, 10); -- Alcohol

-- Day 2: 2025-11-19
INSERT IGNORE INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) VALUES (1, '2025-11-19', NOW());
SET @entry_id = (SELECT id FROM `daily_entries` WHERE user_id = 1 AND entry_date = '2025-11-19');
INSERT INTO `answers` (`entry_id`, `question_id`, `score`) VALUES 
(@entry_id, 1, 7), (@entry_id, 2, 8), (@entry_id, 3, 5), (@entry_id, 4, 6), (@entry_id, 5, 7), (@entry_id, 6, 10);

-- Day 3: 2025-11-20
INSERT IGNORE INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) VALUES (1, '2025-11-20', NOW());
SET @entry_id = (SELECT id FROM `daily_entries` WHERE user_id = 1 AND entry_date = '2025-11-20');
INSERT INTO `answers` (`entry_id`, `question_id`, `score`) VALUES 
(@entry_id, 1, 9), (@entry_id, 2, 10), (@entry_id, 3, 8), (@entry_id, 4, 8), (@entry_id, 5, 9), (@entry_id, 6, 10);

-- Day 4: 2025-11-21
INSERT IGNORE INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) VALUES (1, '2025-11-21', NOW());
SET @entry_id = (SELECT id FROM `daily_entries` WHERE user_id = 1 AND entry_date = '2025-11-21');
INSERT INTO `answers` (`entry_id`, `question_id`, `score`) VALUES 
(@entry_id, 1, 6), (@entry_id, 2, 7), (@entry_id, 3, 4), (@entry_id, 4, 5), (@entry_id, 5, 6), (@entry_id, 6, 9);

-- Day 5: 2025-11-22
INSERT IGNORE INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) VALUES (1, '2025-11-22', NOW());
SET @entry_id = (SELECT id FROM `daily_entries` WHERE user_id = 1 AND entry_date = '2025-11-22');
INSERT INTO `answers` (`entry_id`, `question_id`, `score`) VALUES 
(@entry_id, 1, 8), (@entry_id, 2, 9), (@entry_id, 3, 7), (@entry_id, 4, 7), (@entry_id, 5, 8), (@entry_id, 6, 10);

-- Day 6: 2025-11-23
INSERT IGNORE INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) VALUES (1, '2025-11-23', NOW());
SET @entry_id = (SELECT id FROM `daily_entries` WHERE user_id = 1 AND entry_date = '2025-11-23');
INSERT INTO `answers` (`entry_id`, `question_id`, `score`) VALUES 
(@entry_id, 1, 10), (@entry_id, 2, 10), (@entry_id, 3, 9), (@entry_id, 4, 9), (@entry_id, 5, 10), (@entry_id, 6, 10);

-- Day 7: 2025-11-24
INSERT IGNORE INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) VALUES (1, '2025-11-24', NOW());
SET @entry_id = (SELECT id FROM `daily_entries` WHERE user_id = 1 AND entry_date = '2025-11-24');
INSERT INTO `answers` (`entry_id`, `question_id`, `score`) VALUES 
(@entry_id, 1, 7), (@entry_id, 2, 8), (@entry_id, 3, 6), (@entry_id, 4, 6), (@entry_id, 5, 7), (@entry_id, 6, 10);

-- Day 8: 2025-11-25
INSERT IGNORE INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) VALUES (1, '2025-11-25', NOW());
SET @entry_id = (SELECT id FROM `daily_entries` WHERE user_id = 1 AND entry_date = '2025-11-25');
INSERT INTO `answers` (`entry_id`, `question_id`, `score`) VALUES 
(@entry_id, 1, 8), (@entry_id, 2, 9), (@entry_id, 3, 7), (@entry_id, 4, 7), (@entry_id, 5, 8), (@entry_id, 6, 10);

-- Day 9: 2025-11-26
INSERT IGNORE INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) VALUES (1, '2025-11-26', NOW());
SET @entry_id = (SELECT id FROM `daily_entries` WHERE user_id = 1 AND entry_date = '2025-11-26');
INSERT INTO `answers` (`entry_id`, `question_id`, `score`) VALUES 
(@entry_id, 1, 6), (@entry_id, 2, 7), (@entry_id, 3, 5), (@entry_id, 4, 6), (@entry_id, 5, 6), (@entry_id, 6, 9);

-- Day 10: 2025-11-27
INSERT IGNORE INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) VALUES (1, '2025-11-27', NOW());
SET @entry_id = (SELECT id FROM `daily_entries` WHERE user_id = 1 AND entry_date = '2025-11-27');
INSERT INTO `answers` (`entry_id`, `question_id`, `score`) VALUES 
(@entry_id, 1, 9), (@entry_id, 2, 10), (@entry_id, 3, 8), (@entry_id, 4, 8), (@entry_id, 5, 9), (@entry_id, 6, 10);

-- Day 11: 2025-11-28
INSERT IGNORE INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) VALUES (1, '2025-11-28', NOW());
SET @entry_id = (SELECT id FROM `daily_entries` WHERE user_id = 1 AND entry_date = '2025-11-28');
INSERT INTO `answers` (`entry_id`, `question_id`, `score`) VALUES 
(@entry_id, 1, 7), (@entry_id, 2, 8), (@entry_id, 3, 6), (@entry_id, 4, 7), (@entry_id, 5, 8), (@entry_id, 6, 10);

-- Day 12: 2025-11-29
INSERT IGNORE INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) VALUES (1, '2025-11-29', NOW());
SET @entry_id = (SELECT id FROM `daily_entries` WHERE user_id = 1 AND entry_date = '2025-11-29');
INSERT INTO `answers` (`entry_id`, `question_id`, `score`) VALUES 
(@entry_id, 1, 8), (@entry_id, 2, 9), (@entry_id, 3, 7), (@entry_id, 4, 8), (@entry_id, 5, 9), (@entry_id, 6, 10);

-- Day 13: 2025-11-30
INSERT IGNORE INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) VALUES (1, '2025-11-30', NOW());
SET @entry_id = (SELECT id FROM `daily_entries` WHERE user_id = 1 AND entry_date = '2025-11-30');
INSERT INTO `answers` (`entry_id`, `question_id`, `score`) VALUES 
(@entry_id, 1, 9), (@entry_id, 2, 10), (@entry_id, 3, 8), (@entry_id, 4, 9), (@entry_id, 5, 10), (@entry_id, 6, 10);

-- Day 14: 2025-12-01 (Today)
INSERT IGNORE INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) VALUES (1, '2025-12-01', NOW());
SET @entry_id = (SELECT id FROM `daily_entries` WHERE user_id = 1 AND entry_date = '2025-12-01');
INSERT INTO `answers` (`entry_id`, `question_id`, `score`) VALUES 
(@entry_id, 1, 8), (@entry_id, 2, 9), (@entry_id, 3, 7), (@entry_id, 4, 8), (@entry_id, 5, 9), (@entry_id, 6, 10);
