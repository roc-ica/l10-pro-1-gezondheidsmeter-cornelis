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
    <title>Account - Gezondheidsmeter</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/images/icons/gm192x192.png">
</head>
<body class="auth-page">
    <?php include __DIR__ . '/../components/navbar.php'; ?>
        
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="dashboard-header-left">
                <h1>Mijn Account</h1>
                <p>Welkom, <?= htmlspecialchars($username) ?>!</p>
            </div>
        </div>
    </div>    

    <?php include __DIR__ . '/../components/footer.php'; ?>
    <script src="/js/pwa.js"></script>
</body>
</html>