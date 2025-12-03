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
        <div class="identity-bar">
            <h2>Mijn Account</h2>
            <div class="identity-card">

                <div class="identity-info1">
                   <div class="identity-details">
                     <div class="identity-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="identity-username">
                        <?= htmlspecialchars($username) ?>
                                     eddasda
                    </div>
                   </div>
                    <div class="identity-button">
                        <button>Bewerken</button>
                    </div>
                </div>
                <div class="identity-info2">
                    <p>email:<php></php></p>
                    <p>+31 212133321</p>
                    <p>Straatnaam 12, Amsterdam</p>
                </div>

            </div>
        </div>

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

