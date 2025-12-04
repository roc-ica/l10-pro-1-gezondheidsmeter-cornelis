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
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div class="identity-username">
                            <?= htmlspecialchars($username) ?>
                        </div>
                    </div>
                    <div class="identity-button">
                        <button>Bewerken</button>
                    </div>
                </div>
                <div class="identity-info2">
                    <p>email:<php></php>
                    </p>
                    <p>+31 212133321</p>
                    <p>Straatnaam 12, Amsterdam</p>
                </div>

            </div>
        </div>

        <div class="account-summary">
            <div class="summary-header">
                <h3>Gezondheid Samenvatting</h3>
            </div>
            <div class="summary-content">
                <div class="summary-item">
                    <div class="score-label">Gemiddelde Score</div>
                    <div class="score-value">85%</div>
                </div>
                <div class="summary-item">
                    <div class="score-label">Beantwoorde Vragen</div>
                    <div class="score-value">85%</div>
                </div>
                <div class="summary-item">
                    <div class="streak-label">Huidige Streak</div>
                    <div class="streak-value">7 Dagen</div>
                </div>
            </div>
        </div>

        <div class="account-inst">
            <div class="inst-header">
                <h3>INstellingen</h3>
            </div>
            <div class="inst-content">
                <div class="inst-item">
                    <div>
                        <div class="score-label"><strong>Dagelijkse Herinneringen</strong></div>
                        <div class="score-value">Ontvang herinneringen om vragen in te vullen</div>
                    </div>
                    <div>
                        <label for="notifications"></label>
                        <input type="checkbox" id="notifications" name="notifications" checked>
                    </div>
                </div>
                <div class="inst-item">
                    <div>
                        <div class="score-label"><strong>Email rapportages</strong></div>
                        <div class="score-value">Ontvang wekelijkse gezondheids rapportages</div>
                    </div>
                    <div>
                        <label for="notifications"></label>
                        <input type="checkbox" id="notifications" name="notifications" checked>
                    </div>
                </div>
                <div class="inst-item">
                    <div>
                        <div class="score-label"><strong>Gezondheids gegevens wissen?</strong></div>
                        <div class="score-value">Let op, je gezondheids gegevens kunnen niet meer terug gezet worden
                        </div>
                    </div>
                    <div>
                        <label for="notifications"></label>
                        <input type="checkbox" id="notifications" name="notifications" checked>
                    </div>
                </div>
            </div>
        </div>

        <button class="account-logout" onclick="window.location.href='logout.php'">Afmelden?</button>
        <!-- <a class="account-logout" href="../../pages/logout.php" class="nav-link">Afmelden?</a> -->
    </div>
    <?php include __DIR__ . '/../components/footer.php'; ?>
    <script src="/js/pwa.js"></script>
    <script src="/js/session-guard.js"></script>
</body>

</html>