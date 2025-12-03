<?php
/**
 * INTEGRATION GUIDE: How to Use AdminActionLogger in Admin Operations
 * 
 * This file shows examples of how to integrate action logging into your admin pages.
 */

// EXAMPLE 1: In an admin page that updates a user
// ====================================================

// At the top of your admin page:
require_once __DIR__ . '/../classes/AdminActionLogger.php';
$logger = new AdminActionLogger();
$adminUserId = $_SESSION['user_id']; // From session


// When processing a form that updates a user:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $userId = $_POST['user_id'];
    $displayName = $_POST['display_name'];
    $email = $_POST['email'];
    
    // Update the user in database
    $user->update(['display_name' => $displayName, 'email' => $email]);
    
    // LOG THE ACTION
    $logger->logUserUpdate($adminUserId, $userId, [
        'display_name' => $displayName,
        'email' => $email
    ]);
}


// EXAMPLE 2: When blocking a user
// ====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['block_user'])) {
    $userId = $_POST['user_id'];
    $reason = $_POST['reason'] ?? '';
    
    // Block the user in database
    $stmt = $pdo->prepare("UPDATE users SET is_active = 0, block_reason = ? WHERE id = ?");
    $stmt->execute([$reason, $userId]);
    
    // LOG THE ACTION
    $logger->logUserBlock($adminUserId, $userId, $reason);
}


// EXAMPLE 3: When creating a new question
// ====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_question'])) {
    $pillarId = $_POST['pillar_id'];
    $questionText = $_POST['question_text'];
    
    // Create question in database
    $stmt = $pdo->prepare(
        "INSERT INTO questions (pillar_id, question_text, input_type, created_at) 
         VALUES (?, ?, 'choice', NOW())"
    );
    $stmt->execute([$pillarId, $questionText]);
    $questionId = $pdo->lastInsertId();
    
    // LOG THE ACTION
    $logger->logQuestionCreate($adminUserId, $questionId, [
        'pillar_id' => $pillarId,
        'question_text' => $questionText
    ]);
}


// EXAMPLE 4: When viewing analytics
// ====================================================

// At the top of your analytics page:
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // LOG THE VIEW ACTION
    $logger->logAnalyticsView($adminUserId);
}


// AVAILABLE LOGGING METHODS
// ====================================================

/*
User Actions:
- logUserCreate($adminUserId, $newUserId, $userData)
- logUserUpdate($adminUserId, $userId, $changes)
- logUserDelete($adminUserId, $userId, $username)
- logUserBlock($adminUserId, $userId, $reason)
- logUserUnblock($adminUserId, $userId)
- logUserActivate($adminUserId, $userId)
- logUserDeactivate($adminUserId, $userId)

Question Actions:
- logQuestionCreate($adminUserId, $questionId, $questionData)
- logQuestionUpdate($adminUserId, $questionId, $changes)
- logQuestionDelete($adminUserId, $questionId)

Challenge Actions:
- logChallengeCreate($adminUserId, $challengeId, $challengeData)
- logChallengeUpdate($adminUserId, $challengeId, $changes)
- logChallengeDelete($adminUserId, $challengeId)

Other:
- logReset($adminUserId, $scope, $targetUserId)
- logAnalyticsView($adminUserId)
- logAction($adminUserId, $actionType, $targetTable, $targetId, $details)
*/
