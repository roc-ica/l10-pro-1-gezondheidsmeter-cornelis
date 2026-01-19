<?php
$host = '127.0.0.1';
$port = '3307';
$db   = 'gezondheidsmeter';
$user = 'gezond_user';
$pass = 'gezond_pass';
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Check available sub_questions
    echo "Available sub_questions:\n";
    $stmt = $pdo->query("SELECT id, question_text FROM sub_questions LIMIT 10");
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']} - {$row['question_text']}\n";
    }

    echo "\nAvailable questions:\n";
    $stmt = $pdo->query("SELECT id, question_text FROM questions LIMIT 10");
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']} - {$row['question_text']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
