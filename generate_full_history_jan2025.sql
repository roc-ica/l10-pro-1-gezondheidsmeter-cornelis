-- =====================================================
-- DATA GENERATOR: JANUARI 2025 TOT NU
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

DELIMITER $$

DROP PROCEDURE IF EXISTS GenerateFullHistory$$

CREATE PROCEDURE GenerateFullHistory()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_user_id BIGINT;
    DECLARE v_username VARCHAR(255);
    DECLARE v_display_name VARCHAR(255);
    
    -- Cursor voor alle niet-admin gebruikers
    DECLARE user_cursor CURSOR FOR 
        SELECT id, username, COALESCE(display_name, username) 
        FROM users 
        WHERE is_admin = 0;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN user_cursor;
    
    SELECT '============================================' AS '';
    SELECT 'Start: Genereren data van 1 jan 2025 tot nu' AS '';
    SELECT '============================================' AS '';
    
    read_loop: LOOP
        FETCH user_cursor INTO v_user_id, v_username, v_display_name;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Genereer data voor deze gebruiker
        CALL GenerateHistoryForUser(v_user_id, '2025-01-01');
        
    END LOOP;
    
    CLOSE user_cursor;
END$$

-- =====================================================
-- Procedure voor één gebruiker
-- =====================================================

DROP PROCEDURE IF EXISTS GenerateHistoryForUser$$

CREATE PROCEDURE GenerateHistoryForUser(
    IN p_user_id BIGINT,
    IN p_start_date DATE
)
BEGIN
    DECLARE v_date DATE;
    DECLARE v_entry_id BIGINT;
    DECLARE v_counter INT DEFAULT 0;
    
    -- Variabelen voor scores
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
    DECLARE v_existing_entry INT;
    
    -- Score calculations
    DECLARE v_water_score DECIMAL(5,2);
    DECLARE v_exercise_score DECIMAL(5,2);
    DECLARE v_sleep_score DECIMAL(5,2);
    DECLARE v_drugs_score DECIMAL(5,2);
    DECLARE v_social_score DECIMAL(5,2);
    DECLARE v_pillar_scores JSON;
    
    SET v_date = p_start_date;
    
    -- Loop tot vandaag
    WHILE v_date <= CURDATE() DO
        
        -- Check of er al data is voor deze datum (zodat we geen dubbele hebben)
        SELECT COUNT(*) INTO v_existing_entry
        FROM daily_entries
        WHERE user_id = p_user_id AND entry_date = v_date;
        
        IF v_existing_entry = 0 THEN
            
            -- Bepaal of het weekend is
            SET v_is_weekend = (DAYOFWEEK(v_date) IN (1, 7));
            
            -- GENEREER ANTWOORDEN (Met realistische variatie per seizoen/weekend)
            
            -- Water: Zomer iets meer? Weekend minder structureel?
            SET v_water = IF(v_is_weekend, 4 + FLOOR(RAND() * 6), 6 + FLOOR(RAND() * 6));
            
            -- Beweging: In de zomer (jun-aug) meer actief
            IF MONTH(v_date) BETWEEN 6 AND 8 THEN
                SET v_exercise = IF(v_is_weekend, 45 + FLOOR(RAND() * 90), 30 + FLOOR(RAND() * 60));
            ELSE
                SET v_exercise = IF(v_is_weekend, 30 + FLOOR(RAND() * 60), 10 + FLOOR(RAND() * 40));
            END IF;
            
            -- Slaap
            SET v_sleep = IF(v_is_weekend, 7 + FLOOR(RAND() * 3), 6 + FLOOR(RAND() * 3));
            
            -- Sociaal
            SET v_social = IF(v_is_weekend, 3 + FLOOR(RAND() * 8), 0 + FLOOR(RAND() * 4));
            
            -- Drugs/Alcohol (Weekend piek)
            SET v_has_drugs = IF(v_is_weekend, RAND() < 0.35, RAND() < 0.1);
            SET v_drugs_count = IF(v_has_drugs, 1 + FLOOR(RAND() * 4), 0);
            
            -- Tijdstip invullen
            SET v_hour = 8 + FLOOR(RAND() * 14); -- Tussen 08:00 en 22:00
            SET v_minute = FLOOR(RAND() * 60);
            
            -- 1. MAAK DAILY ENTRY
            INSERT INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) 
            VALUES (p_user_id, v_date, CONCAT(v_date, ' ', LPAD(v_hour, 2, '0'), ':', LPAD(v_minute, 2, '0'), ':00'));
            
            SET v_entry_id = LAST_INSERT_ID();
            
            -- 2. ANTWOORDEN INVOEGEN
            
            -- Water (Q1)
            INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `answer_sequence`, `created_at`) VALUES 
            (v_entry_id, 1, NULL, IF(v_water >= 6, 'Ja', 'Nee'), 1, NOW()),
            (v_entry_id, 1, 2, v_water, 1, NOW());
            
            -- Beweging (Q3)
            INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `answer_sequence`, `created_at`) VALUES 
            (v_entry_id, 3, NULL, IF(v_exercise > 20, 'Ja', 'Nee'), 1, NOW()),
            (v_entry_id, 3, 4, v_exercise, 1, NOW());
            
            -- Slaap (Q5)
            INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `answer_sequence`, `created_at`) VALUES 
            (v_entry_id, 5, NULL, IF(v_sleep >= 7, 'Ja', 'Nee'), 1, NOW()),
            (v_entry_id, 5, 6, v_sleep, 1, NOW());
            
            -- Verslavingen (Q7)
            IF v_has_drugs THEN
                 INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `answer_sequence`, `created_at`) VALUES 
                 (v_entry_id, 7, NULL, 'Ja', 1, NOW()),
                 (v_entry_id, 7, 8, v_drugs_count, 1, NOW());
            ELSE
                 INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `answer_sequence`, `created_at`) VALUES 
                 (v_entry_id, 7, NULL, 'Nee', 1, NOW());
            END IF;
            
            -- Sociaal (Q9)
            INSERT INTO `answers` (`entry_id`, `question_id`, `sub_question_id`, `answer_text`, `answer_sequence`, `created_at`) VALUES 
            (v_entry_id, 9, NULL, IF(v_social > 2, 'Ja', 'Nee'), 1, NOW()),
            (v_entry_id, 9, 10, v_social, 1, NOW());
            
            -- 3. BEREKEN SCORES
            SET v_water_score = LEAST((v_water / 8) * 100, 100);
            SET v_exercise_score = LEAST((v_exercise / 60) * 100, 100);
            SET v_sleep_score = IF(v_sleep >= 7 AND v_sleep <= 9, 100, IF(v_sleep = 6, 80, 60));
            SET v_drugs_score = IF(v_has_drugs, GREATEST(100 - (v_drugs_count * 20), 20), 100);
            SET v_social_score = LEAST((v_social / 8) * 100, 100);
            
            SET v_score = (v_water_score + v_exercise_score + v_sleep_score + v_drugs_score + v_social_score) / 5;
            
            SET v_pillar_scores = JSON_OBJECT(
                '1', ROUND(v_water_score, 2),
                '2', ROUND(v_exercise_score, 2),
                '3', ROUND(v_sleep_score, 2),
                '4', ROUND(v_drugs_score, 2),
                '6', ROUND(v_social_score, 2)
            );
            
            -- 4. SCORE OPSLAAN
            INSERT INTO `user_health_scores` (`user_id`, `score_date`, `overall_score`, `pillar_scores`, `created_at`)
            VALUES (p_user_id, v_date, v_score, v_pillar_scores, CONCAT(v_date, ' ', LPAD(v_hour, 2, '0'), ':', LPAD(v_minute, 2, '0'), ':00'));
            
            SET v_counter = v_counter + 1;
        END IF;
        
        SET v_date = DATE_ADD(v_date, INTERVAL 1 DAY);
    END WHILE;
    
    SELECT CONCAT('  User ', p_user_id, ': ', v_counter, ' dagen toegevoegd.') as Status;

END$$

DELIMITER ;

-- Voer de procedure uit
CALL GenerateFullHistory();

-- Opruimen
DROP PROCEDURE IF EXISTS GenerateFullHistory;
DROP PROCEDURE IF EXISTS GenerateHistoryForUser;

COMMIT;
