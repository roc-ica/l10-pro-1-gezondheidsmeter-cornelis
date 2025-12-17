<?php
session_start();
require_once __DIR__ . '/../src/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Unauthorized');
}

$userId = $_SESSION['user_id'];
$pdo = Database::getConnection();

// 1. Fetch Profile
$stmt = $pdo->prepare("SELECT username, email, birthdate, gender, created_at FROM users WHERE id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Fetch Daily Entries and Answers
$stmt = $pdo->prepare("
    SELECT de.entry_date, de.submitted_at, de.notes,
           q.id as question_id, q.question_text, a.answer_text, p.name as pillar_name,
           a.score as answer_score
    FROM daily_entries de
    LEFT JOIN answers a ON de.id = a.entry_id
    LEFT JOIN questions q ON a.question_id = q.id
    LEFT JOIN pillars p ON q.pillar_id = p.id
    WHERE de.user_id = ?
    ORDER BY de.entry_date DESC
");
$stmt->execute([$userId]);
$rawEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group entries by date
$entries = [];
foreach ($rawEntries as $row) {
    if (empty($row['entry_date'])) continue;
    
    $date = $row['entry_date'];
    if (!isset($entries[$date])) {
        $entries[$date] = [
            'date' => $date,
            'submitted_at' => $row['submitted_at'],
            'notes' => $row['notes'],
            'answers' => []
        ];
    }
    
    if (!empty($row['question_text'])) {
        $entries[$date]['answers'][] = [
            'question_id' => $row['question_id'],
            'question' => $row['question_text'],
            'answer' => $row['answer_text'],
            'category' => $row['pillar_name'],
            'score_value' => $row['answer_score']
        ];
    }
}

// 3. Fetch Calculated Health Scores
$stmt = $pdo->prepare("
    SELECT score_date, overall_score, pillar_scores, calculation_details 
    FROM user_health_scores 
    WHERE user_id = ? 
    ORDER BY score_date DESC
");
$stmt->execute([$userId]);
$scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Decode JSON columns in scores
foreach ($scores as &$score) {
    if (!empty($score['pillar_scores'])) {
        $score['pillar_scores'] = json_decode($score['pillar_scores'], true);
    }
    if (!empty($score['calculation_details'])) {
        $score['calculation_details'] = json_decode($score['calculation_details'], true);
    }
}

// Build Final Export Array
$exportData = [
    'metadata' => [
        'exported_at' => date('Y-m-d H:i:s'),
        'app_name' => 'Gezondheidsmeter',
        'version' => '1.0'
    ],
    'profile' => $profile,
    'health_history' => array_values($entries),
    'calculated_scores' => $scores
];

// Output JSON file
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="health_export_' . $userId . '_' . date('Ymd') . '.json"');
echo json_encode($exportData, JSON_PRETTY_PRINT);
exit;
