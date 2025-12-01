<?php

// Configuration
$userId = 1;
$daysToGenerate = 30;
$questions = [
    1 => ['min' => 1, 'max' => 10], // Water (Voeding)
    2 => ['min' => 1, 'max' => 10], // Frisdrank (Voeding)
    3 => ['min' => 1, 'max' => 10], // Beweging (Beweging)
    4 => ['min' => 1, 'max' => 10], // Slaap (Slaap)
    5 => ['min' => 1, 'max' => 10], // Schermtijd (Mentaal)
    6 => ['min' => 1, 'max' => 10]  // Alcohol (Verslavingen)
];

$sql = "-- Seed Data for Gezondheidsmeter\n";
$sql .= "-- Generated on " . date('Y-m-d H:i:s') . "\n\n";

// 1. Insert User (if not exists)
$sql .= "-- 1. Insert Test User\n";
$sql .= "INSERT IGNORE INTO `users` (`id`, `username`, `email`, `password_hash`, `display_name`) VALUES\n";
$sql .= "($userId, 'testuser', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test User');\n\n"; // Password is 'password'

// 2. Generate Daily Entries and Answers
$sql .= "-- 2. Insert Daily Entries and Answers\n";

$startDate = new DateTime();
$startDate->modify("-$daysToGenerate days");

for ($i = 0; $i < $daysToGenerate; $i++) {
    $currentDate = clone $startDate;
    $currentDate->modify("+$i days");
    $dateStr = $currentDate->format('Y-m-d');
    
    // Insert Daily Entry
    // We use INSERT IGNORE or ON DUPLICATE KEY UPDATE to avoid errors if run multiple times
    // But for simplicity, let's just use INSERT IGNORE and assume ID auto-increment is handled or we fetch it.
    // Since we can't easily fetch the ID in a raw SQL script without variables, 
    // we will assume a deterministic ID or use a subquery.
    
    // Using a subquery to get the ID for the answers is safer.
    
    $sql .= "-- Date: $dateStr\n";
    $sql .= "INSERT IGNORE INTO `daily_entries` (`user_id`, `entry_date`, `submitted_at`) VALUES ($userId, '$dateStr', NOW());\n";
    
    // Insert Answers
    // We need the entry_id. We can use LAST_INSERT_ID() if we are sure it ran, 
    // but if it was ignored, LAST_INSERT_ID might be wrong.
    // Safest is to look it up.
    $sql .= "SET @entry_id = (SELECT id FROM `daily_entries` WHERE user_id = $userId AND entry_date = '$dateStr');\n";
    
    foreach ($questions as $qId => $range) {
        $score = rand($range['min'], $range['max']);
        $sql .= "INSERT INTO `answers` (`entry_id`, `question_id`, `score`) VALUES (@entry_id, $qId, $score);\n";
    }
    $sql .= "\n";
}

file_put_contents(__DIR__ . '/seed_data.sql', $sql);

echo "Seed data generated in seed_data.sql\n";
