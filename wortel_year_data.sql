-- =====================================================
-- TEST DATA GENERATOR VOOR GEBRUIKER: wortel scharrel tarrel
-- Dit script maakt de gebruiker aan en genereert een jaar aan data
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- =====================================================
-- 1. MAAK GEBRUIKER "wortel scharrel tarrel" AAN
-- =====================================================

-- Wachtwoord: Test123!
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

INSERT INTO `users` (`username`, `email`, `password_hash`, `is_admin`, `display_name`, `birthdate`, `geslacht`, `created_at`, `is_active`) VALUES
('wortel', 'wortel@scharrel.tarrel', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 'wortel scharrel tarrel', '1990-05-15', 'man', '2025-01-16 10:00:00', 1);

-- Haal het user_id op
SET @wortel_user_id = LAST_INSERT_ID();

-- =====================================================
-- 2. GENEREER EEN JAAR AAN TESTDATA
-- =====================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS GenerateWortelYearData$$

CREATE PROCEDURE GenerateWortelYearData()
BEGIN
    DECLARE v_date DATE;
    DECLARE v_entry_id BIGINT;
    DECLARE v_counter INT DEFAULT 0;
    DECLARE v_water INT;
    DECLARE v_exercise INT;
    DECLARE v_sleep INT;
    DECLARE v_social INT;
    DECLARE v_has_drugs BOOLEAN;
    DECLARE v_drugs_count INT;
    DECLARE v_score DECIMAL(5,2);
    DECLARE v_user_id BIGINT;
    DECLARE v_hour INT;
    DECLARE v_minute INT;
    
    -- Haal het user_id op van wortel
    SELECT id INTO v_user_id FROM users WHERE username = 'wortel' LIMIT 1;
    
    -- Start een jaar geleden (365 dagen)
    SET v_date = DATE_SUB(CURDATE(), INTERVAL 365 DAY);
    
    -- Loop door het hele jaar - ELKE DAG
    WHILE v_date <= CURDATE() DO
        -- Genereer realistische variaties met patronen
        -- Weekend heeft andere patronen dan doordeweeks
        SET @is_weekend = (DAYOFWEEK(v_date) IN (1, 7));
        
        -- Water: 4-12 glazen (weekend iets minder)
        SET v_water = IF(@is_weekend, 5 + FLOOR(RAND() * 5), 6 + FLOOR(RAND() * 7));
        
        -- Beweging: 10-90 minuten (weekend meer)
        SET v_exercise = IF(@is_weekend, 30 + FLOOR(RAND() * 61), 15 + FLOOR(RAND() * 46));
        
        -- Slaap: 5-9 uur (weekend meer)
        SET v_sleep = IF(@is_weekend, 7 + FLOOR(RAND() * 3), 6 + FLOOR(RAND() * 4));
        
        -- Sociaal: 0-12 uur (weekend veel meer)
        SET v_social = IF(@is_weekend, 4 + FLOOR(RAND() * 9), FLOOR(RAND() * 6));
        
        -- Alcohol/drugs: weekend vaker (20% kans doordeweeks, 40% weekend)
        SET v_has_drugs = IF(@is_weekend, RAND() < 0.4, RAND() < 0.2);
        SET v_drugs_count = IF(v_has_drugs, 1 + FLOOR(RAND() * 3), 0);
        
        -- Varieer de tijden van invullen (7-23 uur)
        SET v_hour = 7 + FLOOR(RAND() * 17);
        SET v_minute = FLOOR(RAND() * 60);
        
        -- Maak daily entry
        INSERT INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) 
        VALUES (v_user_id, v_date, CONCAT(v_date, ' ', LPAD(v_hour, 2, '0'), ':', LPAD(v_minute, 2, '0'), ':00'));
        
        SET v_entry_id = LAST_INSERT_ID();
        
        -- ==========================================
        -- VRAAG 1: VOEDING (Water)
        -- ==========================================
        INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `answer_sequence`, `created_at`)
        VALUES 
        (v_entry_id, 1, NULL, IF(v_water >= 6, 'Ja', 'Nee'), 1, CONCAT(v_date, ' ', LPAD(v_hour, 2, '0'), ':', LPAD(v_minute, 2, '0'), ':00')),
        (v_entry_id, 1, 2, v_water, 1, CONCAT(v_date, ' ', LPAD(v_hour, 2, '0'), ':', LPAD(v_minute, 2, '0'), ':05'));
        
        -- ==========================================
        -- VRAAG 3: BEWEGING
        -- ==========================================
        INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `answer_sequence`, `created_at`)
        VALUES 
        (v_entry_id, 3, NULL, IF(v_exercise > 20, 'Ja', 'Nee'), 1, CONCAT(v_date, ' ', LPAD(v_hour, 2, '0'), ':', LPAD(v_minute, 2, '0'), ':10')),
        (v_entry_id, 3, 4, v_exercise, 1, CONCAT(v_date, ' ', LPAD(v_hour, 2, '0'), ':', LPAD(v_minute, 2, '0'), ':15'));
        
        -- ==========================================
        -- VRAAG 5: SLAAP
        -- ==========================================
        INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `answer_sequence`, `created_at`)
        VALUES 
        (v_entry_id, 5, NULL, IF(v_sleep >= 7, 'Ja', 'Nee'), 1, CONCAT(v_date, ' ', LPAD(v_hour, 2, '0'), ':', LPAD(v_minute, 2, '0'), ':20')),
        (v_entry_id, 5, 6, v_sleep, 1, CONCAT(v_date, ' ', LPAD(v_hour, 2, '0'), ':', LPAD(v_minute, 2, '0'), ':25'));
        
        -- ==========================================
        -- VRAAG 7: VERSLAVINGEN (Alcohol/Drugs)
        -- ==========================================
        IF v_has_drugs THEN
            INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `answer_sequence`, `created_at`)
            VALUES 
            (v_entry_id, 7, NULL, 'Ja', 1, CONCAT(v_date, ' ', LPAD(v_hour, 2, '0'), ':', LPAD(v_minute, 2, '0'), ':30')),
            (v_entry_id, 7, 8, v_drugs_count, 1, CONCAT(v_date, ' ', LPAD(v_hour, 2, '0'), ':', LPAD(v_minute, 2, '0'), ':35'));
        ELSE
            INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `answer_sequence`, `created_at`)
            VALUES (v_entry_id, 7, NULL, 'Nee', 1, CONCAT(v_date, ' ', LPAD(v_hour, 2, '0'), ':', LPAD(v_minute, 2, '0'), ':30'));
        END IF;
        
        -- ==========================================
        -- VRAAG 9: SOCIAAL
        -- ==========================================
        INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `answer_sequence`, `created_at`)
        VALUES 
        (v_entry_id, 9, NULL, IF(v_social > 2, 'Ja', 'Nee'), 1, CONCAT(v_date, ' ', LPAD(v_hour, 2, '0'), ':', LPAD(v_minute, 2, '0'), ':40')),
        (v_entry_id, 9, 10, v_social, 1, CONCAT(v_date, ' ', LPAD(v_hour, 2, '0'), ':', LPAD(v_minute, 2, '0'), ':45'));
        
        -- ==========================================
        -- BEREKEN HEALTH SCORE
        -- ==========================================
        -- Simplified score berekening (gemiddelde van alle aspecten)
        SET v_score = (
            (LEAST(v_water / 8, 1) * 100) + -- Water score (max 8 glazen = 100%)
            (LEAST(v_exercise / 60, 1) * 100) + -- Exercise score (60 min = 100%)
            (IF(v_sleep >= 7 AND v_sleep <= 8, 100, IF(v_sleep = 6 OR v_sleep = 9, 80, 60))) + -- Sleep score
            (IF(v_has_drugs, GREATEST(100 - (v_drugs_count * 20), 30), 100)) + -- Drugs penalty
            (LEAST(v_social / 8, 1) * 100) -- Social score (8 uur = 100%)
        ) / 5;
        
        -- Zorg dat score tussen 0 en 100 ligt
        SET v_score = LEAST(100, GREATEST(0, v_score));
        
        -- Sla health score op
        INSERT INTO `user_health_scores` (`user_id`, `score_date`, `overall_score`, `created_at`)
        VALUES (v_user_id, v_date, v_score, CONCAT(v_date, ' ', LPAD(v_hour, 2, '0'), ':', LPAD(v_minute + 1, 2, '0'), ':00'));
        
        -- Volgende dag
        SET v_date = DATE_ADD(v_date, INTERVAL 1 DAY);
        SET v_counter = v_counter + 1;
    END WHILE;
    
    SELECT CONCAT('✓ Successfully generated ', v_counter, ' daily entries for user "wortel scharrel tarrel"') AS Result;
    SELECT CONCAT('  User ID: ', v_user_id) AS UserInfo;
    SELECT CONCAT('  Date range: ', DATE_SUB(CURDATE(), INTERVAL 365 DAY), ' to ', CURDATE()) AS DateRange;
END$$

DELIMITER ;

-- Voer de procedure uit
CALL GenerateWortelYearData();

-- Verwijder de procedure
DROP PROCEDURE IF EXISTS GenerateWortelYearData;

COMMIT;

-- =====================================================
-- 3. OVERZICHT VAN AANGEMAAKTE DATA
-- =====================================================

SELECT '============================================' AS '';
SELECT 'OVERZICHT: wortel scharrel tarrel' AS '';
SELECT '============================================' AS '';

-- Toon gebruiker info
SELECT 
    u.id AS UserID,
    u.username AS Username,
    u.display_name AS DisplayName,
    u.email AS Email,
    u.birthdate AS Birthdate,
    u.created_at AS AccountCreated
FROM users u
WHERE u.username = 'wortel';

-- Toon statistieken
SELECT 
    COUNT(DISTINCT de.id) AS TotalCheckins,
    MIN(de.entry_date) AS FirstEntry,
    MAX(de.entry_date) AS LastEntry,
    ROUND(AVG(uhs.overall_score), 2) AS AvgHealthScore,
    ROUND(MIN(uhs.overall_score), 2) AS MinScore,
    ROUND(MAX(uhs.overall_score), 2) AS MaxScore
FROM users u
LEFT JOIN daily_entries de ON u.id = de.user_id
LEFT JOIN user_health_scores uhs ON u.id = uhs.user_id
WHERE u.username = 'wortel';

-- Toon aantal antwoorden
SELECT 
    COUNT(*) AS TotalAnswers
FROM answers a
INNER JOIN daily_entries de ON a.entry_id = de.id
INNER JOIN users u ON de.user_id = u.id
WHERE u.username = 'wortel';

-- Toon recente entries (laatste 10)
SELECT 
    de.entry_date AS Date,
    uhs.overall_score AS Score,
    DATE_FORMAT(de.submitted_at, '%H:%i') AS SubmittedTime
FROM daily_entries de
INNER JOIN users u ON de.user_id = u.id
LEFT JOIN user_health_scores uhs ON u.id = uhs.user_id AND de.entry_date = uhs.score_date
WHERE u.username = 'wortel'
ORDER BY de.entry_date DESC
LIMIT 10;

SELECT '============================================' AS '';
SELECT 'Data generation complete!' AS '';
SELECT 'Username: wortel' AS '';
SELECT 'Password: Test123!' AS '';
SELECT '============================================' AS '';
