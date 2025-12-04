<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['question_id']) || !isset($data['answer'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing question_id or answer']);
    exit;
}

$questionId = (int) $data['question_id'];
$answer = $data['answer'];
$userId = $_SESSION['user_id'];

try {
    // Database connection
    require_once __DIR__ . '/../src/config/database.php';
    $pdo = Database::getConnection();

    // Start transaction
    $pdo->beginTransaction();

    // Get or create today's daily entry
    $today = date('Y-m-d');

    $stmt = $pdo->prepare("
        SELECT id FROM daily_entries 
        WHERE user_id = ? AND entry_date = ?
    ");
    $stmt->execute([$userId, $today]);
    $entry = $stmt->fetch();

    if ($entry) {
        $entryId = $entry['id'];
    } else {
        // Create new daily entry
        $stmt = $pdo->prepare("
            INSERT INTO daily_entries (user_id, entry_date, submitted_at) 
            VALUES (?, ?, NULL)
        ");
        $stmt->execute([$userId, $today]);
        $entryId = $pdo->lastInsertId();
    }

    // Check if answer already exists for this question
    $stmt = $pdo->prepare("
        SELECT id FROM answers 
        WHERE entry_id = ? AND question_id = ?
    ");
    $stmt->execute([$entryId, $questionId]);
    $existingAnswer = $stmt->fetch();

    if ($existingAnswer) {
        // Update existing answer
        $stmt = $pdo->prepare("
            UPDATE answers 
            SET answer_text = ?, created_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$answer, $existingAnswer['id']]);
    } else {
        // Insert new answer
        $stmt = $pdo->prepare("
            INSERT INTO answers (entry_id, question_id, answer_text, score) 
            VALUES (?, ?, ?, NULL)
        ");
        $stmt->execute([$entryId, $questionId, $answer]);
    }

    // Commit transaction
    $pdo->commit();

    // Also save to session for immediate feedback
    if (!isset($_SESSION['answered_questions'])) {
        $_SESSION['answered_questions'] = [];
    }
    $_SESSION['answered_questions'][$questionId] = $answer;

    // Get total questions count and answered count from database
    require_once __DIR__ . '/../classes/Question.php';
    $questionModel = new Question();
    $totalQuestions = $questionModel->getTotalCount();

    // Count answered questions for today's entry
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT question_id) as count 
        FROM answers 
        WHERE entry_id = ?
    ");
    $stmt->execute([$entryId]);
    $result = $stmt->fetch();
    $answeredCount = $result['count'];

    $progress = $questionModel->calculateProgress($answeredCount, $totalQuestions);

    echo json_encode([
        'success' => true,
        'answered_count' => $answeredCount,
        'total_questions' => $totalQuestions,
        'progress' => $progress,
        'entry_id' => $entryId
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to save answer',
        'message' => $e->getMessage()
    ]);
}
