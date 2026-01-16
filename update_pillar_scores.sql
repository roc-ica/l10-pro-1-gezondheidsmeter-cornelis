-- =====================================================
-- UPDATE PILLAR SCORES FOR COLIN'S DATA
-- Berekent pillar scores per dag voor betere grafieken
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

DELIMITER $$

DROP PROCEDURE IF EXISTS UpdatePillarScores$$

CREATE PROCEDURE UpdatePillarScores()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_score_id BIGINT;
    DECLARE v_user_id BIGINT;
    DECLARE v_score_date DATE;
    DECLARE v_entry_id BIGINT;
    
    -- Pillar scores
    DECLARE v_voeding_score DECIMAL(5,2);
    DECLARE v_beweging_score DECIMAL(5,2);
    DECLARE v_slaap_score DECIMAL(5,2);
    DECLARE v_verslavingen_score DECIMAL(5,2);
    DECLARE v_sociaal_score DECIMAL(5,2);
    DECLARE v_mentaal_score DECIMAL(5,2);
    
    -- Answer values
    DECLARE v_water INT;
    DECLARE v_exercise INT;
    DECLARE v_sleep INT;
    DECLARE v_has_drugs BOOLEAN;
    DECLARE v_drugs_count INT;
    DECLARE v_social INT;
    
    DECLARE v_pillar_json VARCHAR(500);
    
    -- Cursor to iterate through all health scores without pillar_scores
    DECLARE score_cursor CURSOR FOR 
        SELECT id, user_id, score_date 
        FROM user_health_scores 
        WHERE pillar_scores IS NULL OR pillar_scores = ''
   ORDER BY score_date;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN score_cursor;
    
    read_loop: LOOP
        FETCH score_cursor INTO v_score_id, v_user_id, v_score_date;
        
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Get the entry_id for this date
        SET v_entry_id = (
            SELECT id FROM daily_entries 
            WHERE user_id = v_user_id AND entry_date = v_score_date 
            LIMIT 1
        );
        
        IF v_entry_id IS NOT NULL THEN
            -- Get water intake (question_id 1, sub_question_id 2)
            SET v_water = (
                SELECT CAST(answer_text AS UNSIGNED)
                FROM answers 
                WHERE entry_id = v_entry_id AND question_id = 1 AND sub_question_id = 2
                LIMIT 1
            );
            SET v_water = IFNULL(v_water, 0);
            
            -- Get exercise minutes (question_id 3, sub_question_id 4)
            SET v_exercise = (
                SELECT CAST(answer_text AS UNSIGNED)
                FROM answers 
                WHERE entry_id = v_entry_id AND question_id = 3 AND sub_question_id = 4
                LIMIT 1
            );
            SET v_exercise = IFNULL(v_exercise, 0);
            
            -- Get sleep hours (question_id 5, sub_question_id 6)
            SET v_sleep = (
                SELECT CAST(answer_text AS UNSIGNED)
                FROM answers 
                WHERE entry_id = v_entry_id AND question_id = 5 AND sub_question_id = 6
                LIMIT 1
            );
            SET v_sleep = IFNULL(v_sleep, 0);
            
            -- Get social hours (question_id 9, sub_question_id 10)
            SET v_social = (
                SELECT CAST(answer_text AS UNSIGNED)
                FROM answers 
                WHERE entry_id = v_entry_id AND question_id = 9 AND sub_question_id = 10
                LIMIT 1
            );
            SET v_social = IFNULL(v_social, 0);
            
            -- Get drugs info (question_id 7)
            SET v_has_drugs = (
                SELECT answer_text = 'Ja'
                FROM answers 
                WHERE entry_id = v_entry_id AND question_id = 7 AND sub_question_id IS NULL
                LIMIT 1
            );
            SET v_has_drugs = IFNULL(v_has_drugs, FALSE);
            
            IF v_has_drugs THEN
                SET v_drugs_count = (
                    SELECT CAST(answer_text AS UNSIGNED)
                    FROM answers 
                    WHERE entry_id = v_entry_id AND question_id = 7 AND sub_question_id = 8
                    LIMIT 1
                );
                SET v_drugs_count = IFNULL(v_drugs_count, 0);
            ELSE
                SET v_drugs_count = 0;
            END IF;
            
            -- Calculate pillar scores (0-100)
            -- Pillar 1: Voeding (Water)
            SET v_voeding_score = LEAST((v_water / 8.0) * 100, 125);
            
            -- Pillar 2: Beweging (Exercise) 
            SET v_beweging_score = LEAST((v_exercise / 60.0) * 100, 150);
            
            -- Pillar 3: Slaap (Sleep)
            IF v_sleep >= 7 AND v_sleep <= 8 THEN
                SET v_slaap_score = 100;
            ELSEIF v_sleep = 6 OR v_sleep = 9 THEN
                SET v_slaap_score = 80;
            ELSE
                SET v_slaap_score = 60;
            END IF;
            
            -- Pillar 4: Verslavingen (Drugs/Alcohol)
            IF v_has_drugs THEN
                SET v_verslavingen_score = GREATEST(100 - (v_drugs_count * 25), 30);
            ELSE
                SET v_verslavingen_score = 100;
            END IF;
            
            -- Pillar 5: Sociaal
            SET v_sociaal_score = LEAST((v_social / 8.0) * 100, 125);
            
            -- Pillar 6: Mentaal (default to average of other pillars for now)
            SET v_mentaal_score = (v_voeding_score + v_beweging_score + v_slaap_score + v_verslavingen_score + v_sociaal_score) / 5;
            
            -- Ensure all scores are between 0 and 100
            SET v_voeding_score = LEAST(100, GREATEST(0, v_voeding_score));
            SET v_beweging_score = LEAST(100, GREATEST(0, v_beweging_score));
            SET v_slaap_score = LEAST(100, GREATEST(0, v_slaap_score));
            SET v_verslavingen_score = LEAST(100, GREATEST(0, v_verslavingen_score));
            SET v_sociaal_score = LEAST(100, GREATEST(0, v_sociaal_score));
            SET v_mentaal_score = LEAST(100, GREATEST(0, v_mentaal_score));
            
            -- Create JSON object for pillar_scores
            SET v_pillar_json = JSON_OBJECT(
                '1', ROUND(v_voeding_score, 2),
                '2', ROUND(v_beweging_score, 2),
                '3', ROUND(v_slaap_score, 2),
                '4', ROUND(v_verslavingen_score, 2),
                '5', ROUND(v_sociaal_score, 2),
                '6', ROUND(v_mentaal_score, 2)
            );
            
            -- Update the health score with pillar scores
            UPDATE user_health_scores 
            SET pillar_scores = v_pillar_json
            WHERE id = v_score_id;
        END IF;
        
    END LOOP;
    
    CLOSE score_cursor;
    
    SELECT 'Pillar scores updated successfully!' AS Result;
END$$

DELIMITER ;

-- Execute the procedure
CALL UpdatePillarScores();

-- Drop the procedure
DROP PROCEDURE IF EXISTS UpdatePillarScores;

COMMIT;

-- Verification query
SELECT '============================================' AS '';
SELECT 'VERIFICATION: Sample of updated scores' AS '';
SELECT '============================================' AS '';

SELECT 
    score_date,
    overall_score,
    pillar_scores
FROM user_health_scores
WHERE user_id = (SELECT id FROM users WHERE username = 'colin' LIMIT 1)
  AND score_date >= '2026-01-10'
ORDER BY score_date DESC
LIMIT 7;
