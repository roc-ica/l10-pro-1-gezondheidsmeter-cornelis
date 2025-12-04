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

    // Start transaction
    $pdo->beginTransaction();

    // Get all daily entries for this user
    $stmt = $pdo->prepare("SELECT id FROM daily_entries WHERE user_id = ?");
    $stmt->execute([$userId]);
    $entries = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $deletedEntries = count($entries);
    $deletedAnswers = 0;

    if ($deletedEntries > 0) {
        // Delete all answers for these entries
        $placeholders = str_repeat('?,', count($entries) - 1) . '?';
        $stmt = $pdo->prepare("DELETE FROM answers WHERE entry_id IN ($placeholders)");
        $stmt->execute($entries);
        $deletedAnswers = $stmt->rowCount();

        // Delete all daily entries for this user
        $stmt = $pdo->prepare("DELETE FROM daily_entries WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    // Commit transaction
    $pdo->commit();

    // Clear session data
    unset($_SESSION['answered_questions']);

    echo json_encode([
        'success' => true,
        'message' => 'Alle gezondheidsgegevens zijn succesvol gewist',
        'deleted_entries' => $deletedEntries,
        'deleted_answers' => $deletedAnswers
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to delete health data',
        'message' => $e->getMessage()
    ]);
}
