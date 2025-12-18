<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Niet ingelogd']);
    exit;
}

require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/config/database.php';

$currentUser = User::findByIdStatic($_SESSION['user_id']);

if (!$currentUser || !$currentUser->is_admin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geen admin rechten']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Alleen POST toegestaan']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geen geldige JSON']);
    exit;
}

$action = $data['action'] ?? null;
$userId = (int)($data['user_id'] ?? 0);

if (!$action || !$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ontbrekende parameters']);
    exit;
}

if ($action === 'block') {
    $reason = trim($data['reason'] ?? '');
    $result = User::blockUser($userId, $reason, $currentUser->id);
    echo json_encode($result);
} elseif ($action === 'unblock') {
    $result = User::unblockUser($userId, $currentUser->id);
    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Onbekende actie']);
}
?>
