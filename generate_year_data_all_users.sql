-- =====================================================
-- TEST DATA GENERATOR VOOR ALLE GEBRUIKERS (BEHALVE ADMINS)
-- Dit script genereert een jaar aan data voor alle niet-admin gebruikers
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

DELIMITER $$

DROP PROCEDURE IF EXISTS GenerateYearDataForAllUsers$$

CREATE PROCEDURE GenerateYearDataForAllUsers()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_user_id BIGINT;
    DECLARE v_username VARCHAR(255);
    DECLARE v_display_name VARCHAR(255);
    
    -- Cursor om door alle niet-admin gebruikers te loopen
    DECLARE user_cursor CURSOR FOR 
        SELECT id, username, COALESCE(display_name, username) as display_name 
        FROM users 
        WHERE is_admin = 0;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN user_cursor;
    
    SELECT '============================================' AS '';
    SELECT 'Start: Genereren van een jaar aan data' AS '';
    SELECT '============================================' AS '';
    
    read_loop: LOOP
        FETCH user_cursor INTO v_user_id, v_username, v_display_name;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Genereer data voor deze gebruiker
        SELECT CONCAT('Genereren van data voor: ', v_display_name, ' (ID: ', v_user_id, ')') AS Status;
        CALL GenerateYearDataForSingleUser(v_user_id, v_username);
        SELECT CONCAT('  ✓ Compleet voor ', v_display_name) AS '';
        
    END LOOP;
    
    CLOSE user_cursor;
    
    SELECT '============================================' AS '';
    SELECT 'Alle data succesvol gegenereerd!' AS '';
    SELECT '============================================' AS '';
END$$

-- =====================================================
-- Procedure om data te genereren voor één gebruiker
-- =====================================================

DROP PROCEDURE IF EXISTS GenerateYearDataForSingleUser$$

CREATE PROCEDURE GenerateYearDataForSingleUser(
    IN p_user_id BIGINT,
    IN p_username VARCHAR(255)
)
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
    DECLARE v_hour INT;
    DECLARE v_minute INT;
    DECLARE v_is_weekend BOOLEAN;
    DECLARE v_water_score DECIMAL(5,2);
    DECLARE v_exercise_score DECIMAL(5,2);
    DECLARE v_sleep_score DECIMAL(5,2);
    DECLARE v_drugs_score DECIMAL(5,2);
    DECLARE v_social_score DECIMAL(5,2);
    DECLARE v_pillar_scores JSON;
    DECLARE v_existing_entry INT;
    
    -- Start een jaar geleden (365 dagen)
    SET v_date = DATE_SUB(CURDATE(), INTERVAL 365 DAY);
    
    -- Loop door het hele jaar - ELKE DAG
    WHILE v_date <= CURDATE() DO
        -- Check of er al een entry bestaat voor deze dag
        SELECT COUNT(*) INTO v_existing_entry
        FROM daily_entries
        WHERE user_id = p_user_id AND entry_date = v_date;
        
        -- Alleen als er nog geen entry is, maak nieuwe data aan
        IF v_existing_entry = 0 THEN
            -- Genereer realistische variaties met patronen
            -- Weekend heeft andere patronen dan doordeweeks
            SET v_is_weekend = (DAYOFWEEK(v_date) IN (1, 7));
            
            -- Water: 4-12 glazen (weekend iets minder)
            SET v_water = IF(v_is_weekend, 5 + FLOOR(RAND() * 5), 6 + FLOOR(RAND() * 7));
            
            -- Beweging: 10-90 minuten (weekend meer)
            SET v_exercise = IF(v_is_weekend, 30 + FLOOR(RAND() * 61), 15 + FLOOR(RAND() * 46));
            
            -- Slaap: 5-9 uur (weekend meer)
            SET v_sleep = IF(v_is_weekend, 7 + FLOOR(RAND() * 3), 6 + FLOOR(RAND() * 4));
            
            -- Sociaal: 0-12 uur (weekend veel meer)
            SET v_social = IF(v_is_weekend, 4 + FLOOR(RAND() * 9), FLOOR(RAND() * 6));
            
            -- Alcohol/drugs: weekend vaker (20% kans doordeweeks, 40% weekend)
            SET v_has_drugs = IF(v_is_weekend, RAND() < 0.4, RAND() < 0.2);
            SET v_drugs_count = IF(v_has_drugs, 1 + FLOOR(RAND() * 3), 0);
            
            -- Varieer de tijden van invullen (7-23 uur)
            SET v_hour = 7 + FLOOR(RAND() * 17);
            SET v_minute = FLOOR(RAND() * 60);
            
            -- Maak daily entry
            INSERT INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) 
            VALUES (p_user_id, v_date, CONCAT(v_date, ' ', LPAD(v_hour, 2, '0'), ':', LPAD(v_minute, 2, '0'), ':00'));
            
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
            
            -- Water score
            SET v_water_score = LEAST((v_water / 8) * 100, 100);
            
            -- Exercise score
            SET v_exercise_score = LEAST((v_exercise / 60) * 100, 100);
            
            -- Sleep score
            SET v_sleep_score = IF(v_sleep >= 7 AND v_sleep <= 8, 100, 
                                IF(v_sleep = 6 OR v_sleep = 9, 80, 60));
            
            -- Drugs score (inverted: no drugs = 100)
            SET v_drugs_score = IF(v_has_drugs, GREATEST(100 - (v_drugs_count * 20), 30), 100);
            
            -- Social score
            SET v_social_score = LEAST((v_social / 8) * 100, 100);
            
            -- Overall score
            SET v_score = (v_water_score + v_exercise_score + v_sleep_score + v_drugs_score + v_social_score) / 5;
            
            -- Zorg dat score tussen 0 en 100 ligt
            SET v_score = LEAST(100, GREATEST(0, v_score));
            
            -- Maak pillar scores JSON
            SET v_pillar_scores = JSON_OBJECT(
                '1', ROUND(v_water_score, 2),
                '2', ROUND(v_exercise_score, 2),
                '3', ROUND(v_sleep_score, 2),
                '4', ROUND(v_drugs_score, 2),
                '6', ROUND(v_social_score, 2)
            );
            
            -- Sla health score op
            INSERT INTO `user_health_scores` (`user_id`, `score_date`, `overall_score`, `pillar_scores`, `created_at`)
            VALUES (p_user_id, v_date, v_score, v_pillar_scores, CONCAT(v_date, ' ', LPAD(v_hour, 2, '0'), ':', LPAD(v_minute + 1, 2, '0'), ':00'))
            ON DUPLICATE KEY UPDATE 
                overall_score = VALUES(overall_score),
                pillar_scores = VALUES(pillar_scores);
            
            SET v_counter = v_counter + 1;
        END IF;
        
        -- Volgende dag
        SET v_date = DATE_ADD(v_date, INTERVAL 1 DAY);
    END WHILE;
    
    SELECT CONCAT('  - ', v_counter, ' nieuwe dagboek entries aangemaakt') AS Info;
END$$

DELIMITER ;

-- Voer de hoofdprocedure uit
CALL GenerateYearDataForAllUsers();

-- Verwijder de procedures
DROP PROCEDURE IF EXISTS GenerateYearDataForAllUsers;
DROP PROCEDURE IF EXISTS GenerateYearDataForSingleUser;

COMMIT;

-- =====================================================
-- OVERZICHT VAN AANGEMAAKTE DATA
-- =====================================================

SELECT '============================================' AS '';
SELECT 'OVERZICHT: Alle gebruikers' AS '';
SELECT '============================================' AS '';

-- Toon alle niet-admin gebruikers met hun statistieken
SELECT 
    u.id AS UserID,
    u.username AS Username,
    u.display_name AS DisplayName,
    COUNT(DISTINCT de.id) AS TotalEntries,
    ROUND(AVG(uhs.overall_score), 2) AS AvgScore,
    MIN(de.entry_date) AS FirstEntry,
    MAX(de.entry_date) AS LastEntry
FROM users u
LEFT JOIN daily_entries de ON u.id = de.user_id
LEFT JOIN user_health_scores uhs ON u.id = uhs.user_id
WHERE u.is_admin = 0
GROUP BY u.id, u.username, u.display_name
ORDER BY u.id;

SELECT '============================================' AS '';
SELECT 'Data generation complete!' AS '';
SELECT '============================================' AS '';
