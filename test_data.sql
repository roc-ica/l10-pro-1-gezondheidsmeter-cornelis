-- Test Data Generator voor Gezondheidsmeter
-- Dit script maakt nieuwe gebruikers aan en genereert test data voor een jaar

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- =====================================================
-- 1. NIEUWE TEST GEBRUIKERS AANMAKEN
-- =====================================================

-- Wachtwoord voor alle test gebruikers is: Test123!
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

INSERT INTO `users` (`username`, `email`, `password_hash`, `is_admin`, `display_name`, `birthdate`, `geslacht`, `created_at`, `is_active`) VALUES
('emma', 'emma@test.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 'Emma de Vries', '1995-03-15', 'vrouw', '2025-06-01 10:00:00', 1),
('lucas', 'lucas@test.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 'Lucas Jansen', '1988-11-22', 'man', '2025-07-15 14:30:00', 1),
('sophie', 'sophie@test.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 'Sophie Berg', '1992-07-08', 'vrouw', '2025-08-20 09:15:00', 1),
('thomas', 'thomas@test.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 'Thomas Bakker', '1985-04-30', 'man', '2025-09-10 11:45:00', 1),
('lisa', 'lisa@test.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 'Lisa Smit', '1998-12-03', 'vrouw', '2025-10-05 16:20:00', 1);

-- =====================================================
-- 2. DATA VOOR COLIN (USER_ID = 1) - EEN JAAR TERUG
-- =====================================================

-- We genereren data voor elke 3 dagen over het afgelopen jaar (ca. 120 entries)
-- Dit geeft een realistisch patroon met variatie

DELIMITER $$

DROP PROCEDURE IF EXISTS GenerateColinYearData$$

CREATE PROCEDURE GenerateColinYearData()
BEGIN
    DECLARE v_date DATE;
    DECLARE v_entry_id BIGINT;
    DECLARE v_counter INT DEFAULT 0;
    DECLARE v_water INT;
    DECLARE v_exercise INT;
    DECLARE v_sleep INT;
    DECLARE v_social INT;
    DECLARE v_score DECIMAL(5,2);
    
    -- Start een jaar geleden
    SET v_date = DATE_SUB(CURDATE(), INTERVAL 365 DAY);
    
    -- Loop door het hele jaar (elke 3 dagen)
    WHILE v_date <= CURDATE() DO
        -- Varieer de waardes voor realisme
        SET v_water = 5 + FLOOR(RAND() * 6); -- 5-10 glazen
        SET v_exercise = 15 + FLOOR(RAND() * 46); -- 15-60 minuten
        SET v_sleep = 6 + FLOOR(RAND() * 3); -- 6-8 uur
        SET v_social = FLOOR(RAND() * 12); -- 0-11 uur
        
        -- Maak daily entry
        INSERT INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) 
        VALUES (1, v_date, CONCAT(v_date, ' ', TIME_FORMAT(SEC_TO_TIME(28800 + FLOOR(RAND() * 14400)), '%H:%i:%s')));
        
        SET v_entry_id = LAST_INSERT_ID();
        
        -- Voeding vragen (Question 1)
        INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `answer_sequence`, `created_at`)
        VALUES 
        (v_entry_id, 1, NULL, 'Ja', 1, CONCAT(v_date, ' 08:00:00')),
        (v_entry_id, 1, 2, v_water, 1, CONCAT(v_date, ' 08:00:05'));
        
        -- Beweging vragen (Question 3)
        INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `answer_sequence`, `created_at`)
        VALUES 
        (v_entry_id, 3, NULL, IF(v_exercise > 20, 'Ja', 'Nee'), 1, CONCAT(v_date, ' 08:00:10')),
        (v_entry_id, 3, 4, v_exercise, 1, CONCAT(v_date, ' 08:00:15'));
        
        -- Slaap vragen (Question 5)
        INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `answer_sequence`, `created_at`)
        VALUES 
        (v_entry_id, 5, NULL, IF(v_sleep >= 7, 'Ja', 'Nee'), 1, CONCAT(v_date, ' 08:00:20')),
        (v_entry_id, 5, 6, v_sleep, 1, CONCAT(v_date, ' 08:00:25'));
        
        -- Verslavingen (Question 7) - meestal nee
        IF RAND() > 0.85 THEN
            INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `answer_sequence`, `created_at`)
            VALUES 
            (v_entry_id, 7, NULL, 'Ja', 1, CONCAT(v_date, ' 08:00:30')),
            (v_entry_id, 7, 8, 1, 1, CONCAT(v_date, ' 08:00:35'));
        ELSE
            INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `answer_sequence`, `created_at`)
            VALUES (v_entry_id, 7, NULL, 'Nee', 1, CONCAT(v_date, ' 08:00:30'));
        END IF;
        
        -- Sociaal (Question 9)
        INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `answer_sequence`, `created_at`)
        VALUES 
        (v_entry_id, 9, NULL, IF(v_social > 2, 'Ja', 'Nee'), 1, CONCAT(v_date, ' 08:00:40')),
        (v_entry_id, 9, 10, v_social, 1, CONCAT(v_date, ' 08:00:45'));
        
        -- Bereken en sla health score op
        -- Simplified score berekening (gemiddelde van alle aspecten)
        SET v_score = (
            ((v_water / 8) * 100) + -- Water score
            ((v_exercise / 30) * 100) + -- Exercise score
            (IF(v_sleep >= 7 AND v_sleep <= 8, 100, IF(v_sleep = 6, 80, 60))) + -- Sleep score
            (IF(EXISTS(SELECT 1 FROM answers WHERE entry_id = v_entry_id AND question_id = 7 AND answer_text = 'Ja'), 40, 100)) + -- Drugs score
            ((v_social / 10) * 100) -- Social score
        ) / 5;
        
        -- Zorg dat score tussen 0 en 100 ligt
        SET v_score = LEAST(100, GREATEST(0, v_score));
        
        INSERT INTO `user_health_scores` (`user_id`, `score_date`, `overall_score`, `created_at`)
        VALUES (1, v_date, v_score, CONCAT(v_date, ' 08:01:00'));
        
        -- Volgende datum (elke 3 dagen)
        SET v_date = DATE_ADD(v_date, INTERVAL 3 DAY);
        SET v_counter = v_counter + 1;
    END WHILE;
    
    SELECT CONCAT('Generated ', v_counter, ' entries for Colin') AS Result;
END$$

DELIMITER ;

-- Voer de procedure uit
CALL GenerateColinYearData();

-- Verwijder de procedure
DROP PROCEDURE IF EXISTS GenerateColinYearData;

-- =====================================================
-- 3. RECENTE DATA VOOR NIEUWE TEST GEBRUIKERS
-- =====================================================

-- Emma - Actieve gebruiker (laatste 2 weken, bijna elke dag)
INSERT INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) VALUES
(5, '2026-01-14', '2026-01-14 09:30:00'),
(5, '2026-01-13', '2026-01-13 19:15:00'),
(5, '2026-01-12', '2026-01-12 08:45:00'),
(5, '2026-01-11', '2026-01-11 20:00:00'),
(5, '2026-01-10', '2026-01-10 10:30:00'),
(5, '2026-01-08', '2026-01-08 18:20:00'),
(5, '2026-01-07', '2026-01-07 09:00:00'),
(5, '2026-01-05', '2026-01-05 21:10:00');

-- Emma's answers (voor 14 jan)
INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `created_at`) VALUES
((SELECT id FROM daily_entries WHERE user_id = 5 AND entry_date = '2026-01-14'), 1, NULL, 'Ja', '2026-01-14 09:30:10'),
((SELECT id FROM daily_entries WHERE user_id = 5 AND entry_date = '2026-01-14'), 1, 2, '8', '2026-01-14 09:30:15'),
((SELECT id FROM daily_entries WHERE user_id = 5 AND entry_date = '2026-01-14'), 3, NULL, 'Ja', '2026-01-14 09:30:20'),
((SELECT id FROM daily_entries WHERE user_id = 5 AND entry_date = '2026-01-14'), 3, 4, '45', '2026-01-14 09:30:25'),
((SELECT id FROM daily_entries WHERE user_id = 5 AND entry_date = '2026-01-14'), 5, NULL, 'Ja', '2026-01-14 09:30:30'),
((SELECT id FROM daily_entries WHERE user_id = 5 AND entry_date = '2026-01-14'), 5, 6, '7', '2026-01-14 09:30:35'),
((SELECT id FROM daily_entries WHERE user_id = 5 AND entry_date = '2026-01-14'), 7, NULL, 'Nee', '2026-01-14 09:30:40'),
((SELECT id FROM daily_entries WHERE user_id = 5 AND entry_date = '2026-01-14'), 9, NULL, 'Ja', '2026-01-14 09:30:45'),
((SELECT id FROM daily_entries WHERE user_id = 5 AND entry_date = '2026-01-14'), 9, 10, '8', '2026-01-14 09:30:50');

-- Emma health score
INSERT INTO `user_health_scores` (`user_id`, `score_date`, `overall_score`, `created_at`) VALUES
(5, '2026-01-14', 92.50, '2026-01-14 09:31:00');

-- Lucas - Matige gebruiker (laatste week, paar keer)
INSERT INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) VALUES
(6, '2026-01-13', '2026-01-13 22:00:00'),
(6, '2026-01-10', '2026-01-10 15:30:00'),
(6, '2026-01-07', '2026-01-07 11:45:00');

-- Lucas answers (voor 13 jan)
INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `created_at`) VALUES
((SELECT id FROM daily_entries WHERE user_id = 6 AND entry_date = '2026-01-13'), 1, NULL, 'Nee', '2026-01-13 22:00:10'),
((SELECT id FROM daily_entries WHERE user_id = 6 AND entry_date = '2026-01-13'), 3, NULL, 'Nee', '2026-01-13 22:00:15'),
((SELECT id FROM daily_entries WHERE user_id = 6 AND entry_date = '2026-01-13'), 5, NULL, 'Nee', '2026-01-13 22:00:20'),
((SELECT id FROM daily_entries WHERE user_id = 6 AND entry_date = '2026-01-13'), 5, 6, '5', '2026-01-13 22:00:25'),
((SELECT id FROM daily_entries WHERE user_id = 6 AND entry_date = '2026-01-13'), 7, NULL, 'Ja', '2026-01-13 22:00:30'),
((SELECT id FROM daily_entries WHERE user_id = 6 AND entry_date = '2026-01-13'), 7, 8, '2', '2026-01-13 22:00:35'),
((SELECT id FROM daily_entries WHERE user_id = 6 AND entry_date = '2026-01-13'), 9, NULL, 'Nee', '2026-01-13 22:00:40');

-- Lucas health score
INSERT INTO `user_health_scores` (`user_id`, `score_date`, `overall_score`, `created_at`) VALUES
(6, '2026-01-13', 35.00, '2026-01-13 22:01:00');

-- Sophie - Zeer actieve gebruiker (laatste maand, consistent)
INSERT INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) VALUES
(7, '2026-01-14', '2026-01-14 07:00:00'),
(7, '2026-01-13', '2026-01-13 07:05:00'),
(7, '2026-01-12', '2026-01-12 07:10:00'),
(7, '2026-01-11', '2026-01-11 07:00:00'),
(7, '2026-01-10', '2026-01-10 07:05:00'),
(7, '2026-01-09', '2026-01-09 07:00:00'),
(7, '2026-01-08', '2026-01-08 07:10:00');

-- Sophie answers (voor 14 jan - gezonde gebruiker)
INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `created_at`) VALUES
((SELECT id FROM daily_entries WHERE user_id = 7 AND entry_date = '2026-01-14'), 1, NULL, 'Ja', '2026-01-14 07:00:10'),
((SELECT id FROM daily_entries WHERE user_id = 7 AND entry_date = '2026-01-14'), 1, 2, '10', '2026-01-14 07:00:15'),
((SELECT id FROM daily_entries WHERE user_id = 7 AND entry_date = '2026-01-14'), 3, NULL, 'Ja', '2026-01-14 07:00:20'),
((SELECT id FROM daily_entries WHERE user_id = 7 AND entry_date = '2026-01-14'), 3, 4, '60', '2026-01-14 07:00:25'),
((SELECT id FROM daily_entries WHERE user_id = 7 AND entry_date = '2026-01-14'), 5, NULL, 'Ja', '2026-01-14 07:00:30'),
((SELECT id FROM daily_entries WHERE user_id = 7 AND entry_date = '2026-01-14'), 5, 6, '8', '2026-01-14 07:00:35'),
((SELECT id FROM daily_entries WHERE user_id = 7 AND entry_date = '2026-01-14'), 7, NULL, 'Nee', '2026-01-14 07:00:40'),
((SELECT id FROM daily_entries WHERE user_id = 7 AND entry_date = '2026-01-14'), 9, NULL, 'Ja', '2026-01-14 07:00:45'),
((SELECT id FROM daily_entries WHERE user_id = 7 AND entry_date = '2026-01-14'), 9, 10, '6', '2026-01-14 07:00:50');

-- Sophie health score
INSERT INTO `user_health_scores` (`user_id`, `score_date`, `overall_score`, `created_at`) VALUES
(7, '2026-01-14', 98.00, '2026-01-14 07:01:00');

-- Thomas - Nieuwe gebruiker (net begonnen)
INSERT INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) VALUES
(8, '2026-01-14', '2026-01-14 12:00:00'),
(8, '2026-01-13', '2026-01-13 11:30:00');

-- Thomas answers (voor 14 jan)
INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `created_at`) VALUES
((SELECT id FROM daily_entries WHERE user_id = 8 AND entry_date = '2026-01-14'), 1, NULL, 'Ja', '2026-01-14 12:00:10'),
((SELECT id FROM daily_entries WHERE user_id = 8 AND entry_date = '2026-01-14'), 1, 2, '6', '2026-01-14 12:00:15'),
((SELECT id FROM daily_entries WHERE user_id = 8 AND entry_date = '2026-01-14'), 3, NULL, 'Ja', '2026-01-14 12:00:20'),
((SELECT id FROM daily_entries WHERE user_id = 8 AND entry_date = '2026-01-14'), 3, 4, '30', '2026-01-14 12:00:25'),
((SELECT id FROM daily_entries WHERE user_id = 8 AND entry_date = '2026-01-14'), 5, NULL, 'Ja', '2026-01-14 12:00:30'),
((SELECT id FROM daily_entries WHERE user_id = 8 AND entry_date = '2026-01-14'), 5, 6, '7', '2026-01-14 12:00:35'),
((SELECT id FROM daily_entries WHERE user_id = 8 AND entry_date = '2026-01-14'), 7, NULL, 'Nee', '2026-01-14 12:00:40'),
((SELECT id FROM daily_entries WHERE user_id = 8 AND entry_date = '2026-01-14'), 9, NULL, 'Ja', '2026-01-14 12:00:45'),
((SELECT id FROM daily_entries WHERE user_id = 8 AND entry_date = '2026-01-14'), 9, 10, '4', '2026-01-14 12:00:50');

-- Thomas health score
INSERT INTO `user_health_scores` (`user_id`, `score_date`, `overall_score`, `created_at`) VALUES
(8, '2026-01-14', 78.00, '2026-01-14 12:01:00');

COMMIT;

-- =====================================================
-- OVERZICHT VAN AANGEMAAKTE DATA
-- =====================================================

SELECT 'OVERZICHT TEST DATA' AS '=================';

SELECT 
    'Nieuwe Gebruikers' AS Info,
    COUNT(*) AS Aantal 
FROM users 
WHERE id > 4;

SELECT 
    'Daily Entries voor Colin (1 jaar)' AS Info,
    COUNT(*) AS Aantal 
FROM daily_entries 
WHERE user_id = 1 
AND entry_date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY);

SELECT 
    'Totaal Daily Entries' AS Info,
    COUNT(*) AS Aantal 
FROM daily_entries;

SELECT 
    'Totaal Health Scores' AS Info,
    COUNT(*) AS Aantal 
FROM user_health_scores;

-- Toon gebruikers overzicht
SELECT 
    u.id,
    u.username,
    u.display_name,
    u.email,
    COUNT(DISTINCT de.id) AS total_checkins,
    MAX(de.entry_date) AS last_checkin,
    ROUND(AVG(uhs.overall_score), 2) AS avg_score
FROM users u
LEFT JOIN daily_entries de ON u.id = de.user_id
LEFT JOIN user_health_scores uhs ON u.id = uhs.user_id
WHERE u.is_admin = 0
GROUP BY u.id
ORDER BY u.id;
