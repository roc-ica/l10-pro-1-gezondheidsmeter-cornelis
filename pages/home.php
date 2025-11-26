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
    <link rel="stylesheet" href="../assets/css/style.css">

    <!-- Link naar je manifest -->
    <link rel="manifest" href="/manifest.json">

    <!-- Apple Touch Icon -->
    <link rel="apple-touch-icon" href="/images/icons/icon-192x192.png">

    <!-- Apple Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Gezondheid">

</head>

<body class="auth-page">
    <?php include __DIR__ . '/../components/navbar.php'; ?>
    <div class="container">
        <h1>Welkom, <?= htmlspecialchars($username) ?>!</h1>
        <p class="welcome-message">Je bent succesvol ingelogd.</p>
    </div>
    <?php include __DIR__ . '/../components/footer.php'; ?>
</body>

</html>