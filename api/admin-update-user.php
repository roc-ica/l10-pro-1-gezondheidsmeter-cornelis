<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Niet ingelogd']);
    exit;
}

require_once __DIR__ . '/../src/models/User.php';
$currentUser = User::findByIdStatic($_SESSION['user_id']);

if (!$currentUser || !$currentUser->is_admin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geen admin rechten']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ongeldig verzoek']);
    exit;
}

// Validate input
$userId = intval($data['user_id'] ?? 0);
$email = trim($data['email'] ?? '');
$birthdate = trim($data['birthdate'] ?? '');
$gender = trim($data['gender'] ?? '');

if (!$userId || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Ongeldig e-mailadres']);
    exit;
}

// Get user to update
$user = User::findByIdStatic($userId);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Gebruiker niet gevonden']);
    exit;
}

// Check if email is already taken by another user
require_once __DIR__ . '/../src/config/database.php';
$pdo = Database::getConnection();
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
$stmt->execute([$email, $userId]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Dit e-mailadres is al in gebruik']);
    exit;
}

// Update user data
$result = $user->update([
    'email' => $email,
    'birthdate' => $birthdate ?: null,
    'gender' => $gender ?: null
]);

if ($result['success']) {
    echo json_encode(['success' => true, 'message' => 'Gebruiker bijgewerkt']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Er is een fout opgetreden']);
}
