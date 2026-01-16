-- =====================================================
-- DATA GENERATOR VOOR GEBRUIKER: colin
-- Periode: 1 oktober 2025 t/m 16 januari 2026
-- Deze script verwijdert eerst bestaande data en maakt het opnieuw aan
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- =====================================================
-- 1. VERWIJDER BESTAANDE DATA VOOR DEZE PERIODE
-- =====================================================

-- Haal colin's user_id op
SET @colin_user_id = (SELECT id FROM users WHERE username = 'colin' LIMIT 1);

-- Verwijder bestaande health scores voor deze periode
DELETE FROM user_health_scores 
WHERE user_id = @colin_user_id 
  AND score_date >= '2025-10-01' 
  AND score_date <= '2026-01-16';

-- Verwijder bestaande daily entries voor deze periode (answers worden automatisch verwijderd door CASCADE)
DELETE FROM daily_entries 
WHERE user_id = @colin_user_id 
  AND entry_date >= '2025-10-01' 
  AND entry_date <= '2026-01-16';

SELECT CONCAT('✓ Cleared existing data for colin from 2025-10-01 to 2026-01-16') AS Status;

-- =====================================================
-- 2. GENEREER NIEUWE DATA
-- =====================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS GenerateColinOctJanData$$

CREATE PROCEDURE GenerateColinOctJanData()
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
    
    -- Haal het user_id op van colin
    SELECT id INTO v_user_id FROM users WHERE username = 'colin' LIMIT 1;
    
    IF v_user_id IS NULL THEN
        SELECT 'ERROR: Gebruiker colin niet gevonden!' AS Result;
    ELSE
        -- Start op 1 oktober 2025
        SET v_date = '2025-10-01';
        
        -- Loop tot en met 16 januari 2026
        WHILE v_date <= '2026-01-16' DO
            -- Genereer realistische variaties met patronen
            -- Weekend heeft andere patronen dan doordeweeks
            SET @is_weekend = (DAYOFWEEK(v_date) IN (1, 7));
            
            -- Water: 5-22 glazen (variërend, soms extreem zoals in de JSON)
            SET v_water = IF(@is_weekend, 
                IF(RAND() < 0.1, 15 + FLOOR(RAND() * 8), 6 + FLOOR(RAND() * 6)),
                IF(RAND() < 0.05, 18 + FLOOR(RAND() * 5), 6 + FLOOR(RAND() * 6))
            );
            
            -- Beweging: 15-120 minuten (soms veel, zoals 120 in de JSON)
            SET v_exercise = IF(@is_weekend, 
                IF(RAND() < 0.3, 60 + FLOOR(RAND() * 61), 20 + FLOOR(RAND() * 41)),
                IF(RAND() < 0.15, 60 + FLOOR(RAND() * 61), 15 + FLOOR(RAND() * 46))
            );
            
            -- Slaap: 5-9 uur
            SET v_sleep = IF(@is_weekend, 7 + FLOOR(RAND() * 3), 6 + FLOOR(RAND() * 4));
            
            -- Sociaal: 0-12 uur (soms extreem zoals 700 in JSON, maar meestal normaal)
            SET v_social = IF(@is_weekend, 
                IF(RAND() < 0.05, 50 + FLOOR(RAND() * 100), 3 + FLOOR(RAND() * 9)),
                IF(RAND() < 0.02, 50 + FLOOR(RAND() * 100), FLOOR(RAND() * 5))
            );
            
            -- Alcohol/drugs: meestal nee (95% nee)
            SET v_has_drugs = (RAND() < 0.05);
            SET v_drugs_count = IF(v_has_drugs, FLOOR(RAND() * 2), 0);
            
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
                VALUES 
                (v_entry_id, 7, NULL, 'Nee', 1, CONCAT(v_date, ' ', LPAD(v_hour, 2, '0'), ':', LPAD(v_minute, 2, '0'), ':30')),
                (v_entry_id, 7, 8, 0, 1, CONCAT(v_date, ' ', LPAD(v_hour, 2, '0'), ':', LPAD(v_minute, 2, '0'), ':35'));
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
            -- Score berekening gebaseerd op de JSON voorbeelden
            SET v_score = (
                (LEAST(v_water / 8, 1.25) * 100) + -- Water score (max bij 8 glazen, maar kan hoger)
                (LEAST(v_exercise / 60, 1.5) * 100) + -- Exercise score (60 min = 100%, kan hoger)
                (IF(v_sleep >= 7 AND v_sleep <= 8, 100, IF(v_sleep = 6 OR v_sleep = 9, 80, 60))) + -- Sleep score
                (IF(v_has_drugs, GREATEST(100 - (v_drugs_count * 25), 40), 100)) + -- Drugs penalty
                (LEAST(v_social / 8, 1.25) * 100) -- Social score (8 uur = 100%, kan hoger)
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
        
        SELECT CONCAT('✓ Successfully generated ', v_counter, ' daily entries for user "colin"') AS Result;
        SELECT CONCAT('  User ID: ', v_user_id) AS UserInfo;
        SELECT CONCAT('  Date range: 2025-10-01 to 2026-01-16') AS DateRange;
    END IF;
END$$

DELIMITER ;

-- Voer de procedure uit
CALL GenerateColinOctJanData();

-- Verwijder de procedure
DROP PROCEDURE IF EXISTS GenerateColinOctJanData;

COMMIT;

-- =====================================================
-- OVERZICHT VAN AANGEMAAKTE DATA
-- =====================================================

SELECT '============================================' AS '';
SELECT 'OVERZICHT: colin (okt 2025 - jan 2026)' AS '';
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
WHERE u.username = 'colin';

-- Toon statistieken voor de nieuwe periode
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
WHERE u.username = 'colin'
  AND de.entry_date >= '2025-10-01'
  AND de.entry_date <= '2026-01-16';

-- Toon aantal antwoorden
SELECT 
    COUNT(*) AS TotalAnswers
FROM answers a
INNER JOIN daily_entries de ON a.entry_id = de.id
INNER JOIN users u ON de.user_id = u.id
WHERE u.username = 'colin'
  AND de.entry_date >= '2025-10-01'
  AND de.entry_date <= '2026-01-16';

-- Toon recente entries (laatste 15)
SELECT 
    de.entry_date AS Date,
    uhs.overall_score AS Score,
    DATE_FORMAT(de.submitted_at, '%H:%i') AS SubmittedTime
FROM daily_entries de
INNER JOIN users u ON de.user_id = u.id
LEFT JOIN user_health_scores uhs ON u.id = uhs.user_id AND de.entry_date = uhs.score_date
WHERE u.username = 'colin'
  AND de.entry_date >= '2025-10-01'
  AND de.entry_date <= '2026-01-16'
ORDER BY de.entry_date DESC
LIMIT 15;

-- Toon maandelijkse statistieken
SELECT 
    DATE_FORMAT(de.entry_date, '%Y-%m') AS Maand,
    COUNT(DISTINCT de.id) AS AantalEntries,
    ROUND(AVG(uhs.overall_score), 2) AS GemScore
FROM daily_entries de
INNER JOIN users u ON de.user_id = u.id
LEFT JOIN user_health_scores uhs ON u.id = uhs.user_id AND de.entry_date = uhs.score_date
WHERE u.username = 'colin'
  AND de.entry_date >= '2025-10-01'
  AND de.entry_date <= '2026-01-16'
GROUP BY DATE_FORMAT(de.entry_date, '%Y-%m')
ORDER BY Maand;

SELECT '============================================' AS '';
SELECT 'Data generation complete!' AS '';
SELECT '============================================' AS '';
