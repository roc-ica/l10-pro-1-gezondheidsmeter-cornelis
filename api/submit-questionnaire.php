<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Database connection
    require_once __DIR__ . '/../src/config/database.php';
    $pdo = Database::getConnection();

    // Get today's entry
    $today = date('Y-m-d');

    $stmt = $pdo->prepare("
        SELECT id FROM daily_entries 
        WHERE user_id = ? AND entry_date = ?
    ");
    $stmt->execute([$userId, $today]);
    $entry = $stmt->fetch();

    if (!$entry) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Geen vragen beantwoord vandaag.'
        ]);
        exit;
    }

    $entryId = $entry['id'];

    // Update the entry to mark it as submitted
    $stmt = $pdo->prepare("
        UPDATE daily_entries 
        SET submitted_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->execute([$entryId]);

    // Clear session answered questions
    unset($_SESSION['answered_questions']);

    echo json_encode([
        'success' => true,
        'message' => 'Vragenlijst succesvol voltooid!',
        'entry_id' => $entryId
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to submit questionnaire',
        'message' => $e->getMessage()
    ]);
}
