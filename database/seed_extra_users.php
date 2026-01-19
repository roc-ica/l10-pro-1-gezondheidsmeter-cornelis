<?php
$host = '127.0.0.1';
$port = '3307';
$db   = 'gezondheidsmeter';
$user = 'gezond_user';
$pass = 'gezond_pass';
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Starting test user creation...\n";

    $testUsers = [
        ['username' => 'anita_gezond', 'email' => 'anita@example.com', 'display_name' => 'Anita Gezond', 'password' => 'wachtwoord123'],
        ['username' => 'mark_sport', 'email' => 'mark@example.com', 'display_name' => 'Mark Sportief', 'password' => 'wachtwoord123'],
        ['username' => 'lisa_stress', 'email' => 'lisa@example.com', 'display_name' => 'Lisa Stressvrij', 'password' => 'wachtwoord123'],
        ['username' => 'bram_fit', 'email' => 'bram@example.com', 'display_name' => 'Bram Fit', 'password' => 'wachtwoord123'],
        ['username' => 'sophie_balans', 'email' => 'sophie@example.com', 'display_name' => 'Sophie Balans', 'password' => 'wachtwoord123']
    ];

    foreach ($testUsers as $u) {
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$u['username']]);
        $existing = $check->fetch();
        
        if ($existing) {
            $userId = $existing['id'];
            echo "User {$u['username']} already exists (ID: $userId). Updating data.\n";
        } else {
            $hash = password_hash($u['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, display_name, password_hash, created_at, is_active, is_admin) VALUES (?, ?, ?, ?, NOW(), 1, 0)");
            $stmt->execute([$u['username'], $u['email'], $u['display_name'], $hash]);
            $userId = $pdo->lastInsertId();
            echo "Created user: {$u['username']} (ID: $userId)\n";
        }

        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            
            $checkEntry = $pdo->prepare("SELECT id FROM daily_entries WHERE user_id = ? AND entry_date = ?");
            $checkEntry->execute([$userId, $date]);
            $entry = $checkEntry->fetch();
            
            if (!$entry) {
                $pdo->prepare("INSERT INTO daily_entries (user_id, entry_date, submitted_at) VALUES (?, ?, NOW())")
                    ->execute([$userId, $date]);
                $entryId = $pdo->lastInsertId();
            } else {
                $entryId = $entry['id'];
                $pdo->prepare("DELETE FROM answers WHERE entry_id = ?")->execute([$entryId]);
            }

            // Fixed parameter counts
            $pdo->prepare("INSERT INTO answers (entry_id, question_id, sub_question_id, answer_text) VALUES (?, 1, 2, ?)")
                ->execute([$entryId, rand(4, 10)]);

            $pdo->prepare("INSERT INTO answers (entry_id, question_id, sub_question_id, answer_text) VALUES (?, 3, 4, ?)")
                ->execute([$entryId, rand(20, 60)]);

            $pdo->prepare("INSERT INTO answers (entry_id, question_id, sub_question_id, answer_text) VALUES (?, 5, 6, ?)")
                ->execute([$entryId, rand(6, 9)]);

            $pdo->prepare("INSERT INTO answers (entry_id, question_id, answer_text) VALUES (?, 12, ?)")
                ->execute([$entryId, rand(1, 8)]);
            
            $pdo->prepare("INSERT INTO answers (entry_id, question_id, answer_text) VALUES (?, 13, ?)")
                ->execute([$entryId, rand(1, 6)]);
        }
    }

    echo "Finished creating users and dummy data.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
