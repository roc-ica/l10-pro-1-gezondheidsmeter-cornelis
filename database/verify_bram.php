<?php
$host = '127.0.0.1';
$port = '3307';
$db   = 'gezondheidsmeter';
$user = 'gezond_user';
$pass = 'gezond_pass';
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    $username = 'bram_fit';
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        die("User $username not found\n");
    }
    
    $userId = $user['id'];
    echo "Checking entries for $username (ID: $userId):\n";
    
    $stmt = $pdo->prepare("SELECT e.id, e.entry_date, COUNT(a.id) as answer_count 
                           FROM daily_entries e 
                           LEFT JOIN answers a ON e.id = a.entry_id 
                           WHERE e.user_id = ? 
                           GROUP BY e.id 
                           ORDER BY e.entry_date DESC");
    $stmt->execute([$userId]);
    
    while ($row = $stmt->fetch()) {
        echo "Date: {$row['entry_date']} - Answers: {$row['answer_count']} (ID: {$row['id']})\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
