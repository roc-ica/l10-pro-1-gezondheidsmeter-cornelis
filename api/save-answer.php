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

// Initialize answered_questions array if not exists
if (!isset($_SESSION['answered_questions'])) {
    $_SESSION['answered_questions'] = [];
}

// Save the answer (keyed by question_id to avoid duplicates)
$_SESSION['answered_questions'][$questionId] = $answer;

// Get total questions count
require_once __DIR__ . '/../classes/Question.php';
$questionModel = new Question();
$totalQuestions = $questionModel->getTotalCount();
$answeredCount = count($_SESSION['answered_questions']);
$progress = $questionModel->calculateProgress($answeredCount, $totalQuestions);

echo json_encode([
    'success' => true,
    'answered_count' => $answeredCount,
    'total_questions' => $totalQuestions,
    'progress' => $progress
]);
