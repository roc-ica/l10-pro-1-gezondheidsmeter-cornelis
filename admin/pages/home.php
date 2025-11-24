<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../src/views/auth/login.php');
    exit;
}

// Check if user is admin
require_once __DIR__ . '/../../src/models/User.php';
$user = \User::findByIdStatic($_SESSION['user_id']);

if (!$user || !$user->is_admin) {
    // Not admin, redirect to normal home
    header('Location: ../../pages/home.php');
    exit;
}

$username = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Home - Gezondheidsmeter</title>

</head>
<body>
    <div class="container">
        <h1>Welkom, <?= htmlspecialchars($username) ?>! <span class="admin-badge">ADMIN</span></h1>
        <p class="welcome-message">Je bent succesvol ingelogd als beheerder.</p>
        
        <a href="../../pages/logout.php" class="logout-btn">Uitloggen</a>
    </div>
</body>
</html>