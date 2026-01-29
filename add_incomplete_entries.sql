-- Add incomplete daily entries for testing
-- This will add incomplete entries (submitted_at = NULL) for the last 7 days

-- Get some random user IDs to use
SET @user1 = (SELECT id FROM users WHERE is_admin = 0 ORDER BY RAND() LIMIT 1);
SET @user2 = (SELECT id FROM users WHERE is_admin = 0 AND id != @user1 ORDER BY RAND() LIMIT 1);
SET @user3 = (SELECT id FROM users WHERE is_admin = 0 AND id NOT IN (@user1, @user2) ORDER BY RAND() LIMIT 1);

-- Add incomplete entries for the last 7 days
-- Day 1 (today) - 2 incomplete entries
INSERT INTO daily_entries (user_id, entry_date, created_at, updated_at, submitted_at)
VALUES 
    (@user1, CURDATE(), NOW(), NOW(), NULL),
    (@user2, CURDATE(), NOW(), NOW(), NULL);

-- Day 2 (yesterday) - 3 incomplete entries
INSERT INTO daily_entries (user_id, entry_date, created_at, updated_at, submitted_at)
VALUES 
    (@user1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), NULL),
    (@user2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), NULL),
    (@user3, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), NULL);

-- Day 3 - 1 incomplete entry
INSERT INTO daily_entries (user_id, entry_date, created_at, updated_at, submitted_at)
VALUES 
    (@user2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), NULL);

-- Day 4 - 4 incomplete entries
INSERT INTO daily_entries (user_id, entry_date, created_at, updated_at, submitted_at)
VALUES 
    (@user1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), NULL),
    (@user2, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), NULL),
    (@user3, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), NULL);

-- Day 5 - 2 incomplete entries
INSERT INTO daily_entries (user_id, entry_date, created_at, updated_at, submitted_at)
VALUES 
    (@user1, DATE_SUB(CURDATE(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY), NULL),
    (@user3, DATE_SUB(CURDATE(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY), NULL);

-- Day 6 - 1 incomplete entry
INSERT INTO daily_entries (user_id, entry_date, created_at, updated_at, submitted_at)
VALUES 
    (@user2, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), NULL);

-- Day 7 - 3 incomplete entries
INSERT INTO daily_entries (user_id, entry_date, created_at, updated_at, submitted_at)
VALUES 
    (@user1, DATE_SUB(CURDATE(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY), NULL),
    (@user2, DATE_SUB(CURDATE(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY), NULL),
    (@user3, DATE_SUB(CURDATE(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY), NULL);

SELECT 'Incomplete entries added successfully!' as Result;
SELECT 
    entry_date,
    COUNT(*) as incomplete_count
FROM daily_entries 
WHERE submitted_at IS NULL 
    AND entry_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY entry_date
ORDER BY entry_date DESC;
