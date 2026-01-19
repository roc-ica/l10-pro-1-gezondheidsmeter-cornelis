<?php
putenv("DB_HOST=127.0.0.1;port=3307");
putenv("DB_NAME=gezondheidsmeter");
putenv("DB_USER=gezond_user");
putenv("DB_PASSWORD=gezond_pass");

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/HealthScoreCalculator.php';

try {
    $pdo = Database::getConnection();
    
    // Get all users we just created/updated
    $usernames = ['anita_gezond', 'mark_sport', 'lisa_stress', 'bram_fit', 'sophie_balans'];
    
    foreach ($usernames as $username) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $userRow = $stmt->fetch();
        
        if (!$userRow) continue;
        $userId = $userRow['id'];
        
        echo "Recalculating scores for $username (ID: $userId)...\n";
        
        $stmtEntries = $pdo->prepare("SELECT entry_date FROM daily_entries WHERE user_id = ?");
        $stmtEntries->execute([$userId]);
        $dates = $stmtEntries->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($dates as $date) {
            $calculator = new HealthScoreCalculator($userId, $date);
            $result = $calculator->calculateScore();
            if ($result['success']) {
                echo "  $date: Score " . $result['score'] . " calculated.\n";
            } else {
                echo "  $date: FAILED - " . $result['message'] . "\n";
            }
        }
    }
    
    echo "Done.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
