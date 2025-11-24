<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../src/views/auth/login.php');
    exit;
}

$username = $_SESSION['username'] ?? 'Gebruiker';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Gezondheidsmeter</title>
</head>
<body>
    <div class="container">
        <h1>Welkom, <?= htmlspecialchars($username) ?>!</h1>
        <p class="welcome-message">Je bent succesvol ingelogd.</p>
        
        <a href="logout.php" class="logout-btn">Uitloggen</a>
    </div>
</body>
</html>
