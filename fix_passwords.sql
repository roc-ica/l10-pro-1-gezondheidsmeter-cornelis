-- =====================================================
-- FIX ALLE GEBRUIKER WACHTWOORDEN
-- Zet alle wachtwoorden naar: Wortelboot12
-- =====================================================

START TRANSACTION;

-- Hash voor wachtwoord "Wortelboot12"
SET @wortel_hash = '$2y$10$aY.lb.nSTNz9XBfBXt63seHGqQOflu/guJsTKS6q26zm8Bzn6CdRu';

-- Update alle gebruikers met het juiste gehashte wachtwoord
UPDATE users 
SET password_hash = @wortel_hash
WHERE username IN ('emma', 'lucas', 'sophie', 'thomas', 
                    'anita_gezond', 'lisa_stress', 'bram_fit', 'sophie_balans', 'lisa', 'wortel');

-- Toon resultaat
SELECT '============================================' AS '';
SELECT 'WACHTWOORDEN GEÜPDATET' AS '';
SELECT '============================================' AS '';

SELECT 
    username,
    LEFT(password_hash, 20) AS hash_preview,
    is_admin,
    is_active
FROM users
ORDER BY id;

SELECT '============================================' AS '';
SELECT 'Alle gebruikers hebben nu wachtwoord: Wortelboot12' AS '';
SELECT '============================================' AS '';

COMMIT;
