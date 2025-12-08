<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Niet ingelogd']);
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
$email = trim($data['email'] ?? '');
$birthdate = trim($data['birthdate'] ?? '');
$gender = trim($data['gender'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Ongeldig e-mailadres']);
    exit;
}

// Update user in database
require_once __DIR__ . '/../src/models/User.php';
$user = User::findByIdStatic($_SESSION['user_id']);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Gebruiker niet gevonden']);
    exit;
}

// Check if email is already taken by another user
require_once __DIR__ . '/../src/config/database.php';
$pdo = Database::getConnection();
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
$stmt->execute([$email, $_SESSION['user_id']]);
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
    // Update session email
    $_SESSION['email'] = $email;
    echo json_encode(['success' => true, 'message' => 'Profiel bijgewerkt']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Er is een fout opgetreden']);
}
