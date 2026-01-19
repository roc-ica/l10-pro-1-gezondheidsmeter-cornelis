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
    $userId = $user['id'];

    echo "Checking submitted_at for $username:\n";
    $stmt = $pdo->prepare("SELECT entry_date, submitted_at FROM daily_entries WHERE user_id = ? ORDER BY entry_date DESC");
    $stmt->execute([$userId]);
    while ($row = $stmt->fetch()) {
        echo "Date: {$row['entry_date']} - Submitted: " . ($row['submitted_at'] ?? 'NULL') . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
