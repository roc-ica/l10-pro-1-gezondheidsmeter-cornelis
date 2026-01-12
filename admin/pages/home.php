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

// Load dashboard stats
require_once __DIR__ . '/../../src/models/AdminDashboardStats.php';
$stats = new AdminDashboardStats();

// Get all data
$totalUsers = $stats->getTotalUsers();
$totalAnswers = $stats->getTotalAnswers();
$averageScore = $stats->getAverageScore();
$activeThisWeek = $stats->getActiveThisWeek();
$weeklyActivityData = $stats->getWeeklyActivityWithHeights();
$trendDataWithCoords = $stats->getTrendDataWithCoordinates();
$recentAdminActions = $stats->getRecentAdminActions();

$username = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Gezondheidsmeter</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body class="auth-page">
    <?php include __DIR__ . '/../../components/navbar-admin.php'; ?>
    
    <div class="dashboard-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-header-left">
                <h1>Dashboard</h1>
                <p>Overzicht van alle gegevens</p>
            </div>
            <div class="dashboard-header-right">
                <a href="#" class="btn-naar-app">Naar App</a>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-block">
                <div class="stat-label">Totaal gebruikers</div>
                <div class="stat-number"><?= number_format($totalUsers) ?></div>
            </div>
            <div class="stat-block">
                <div class="stat-label">Vragen beantwoord</div>
                <div class="stat-number"><?= number_format($totalAnswers) ?></div>
            </div>
            <div class="stat-block">
                <div class="stat-label">Gemiddelde Score</div>
                <div class="stat-number"><?= $averageScore ?></div>
            </div>
            <div class="stat-block">
                <div class="stat-label">Actief Deze Week</div>
                <div class="stat-number"><?= number_format($activeThisWeek) ?></div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="charts-row">
            <!-- Weekly Activity Chart -->
            <div class="chart-block">
                <h3 class="chart-title">Wekelijkse Activiteit</h3>
                <div class="weekly-activity">
                    <div class="chart-area">
                        <div class="y-axis">
                            <div class="y-axis-label">180</div>
                            <div class="y-axis-label">135</div>
                            <div class="y-axis-label">90</div>
                            <div class="y-axis-label">45</div>
                            <div class="y-axis-label">0</div>
                        </div>
                        <div class="bars-container">
                            <?php foreach ($weeklyActivityData as $dayData): ?>
                            <div class="day-bars">
                                <div class="bar green" style="height: <?= $dayData['submitted_height'] ?>px;"></div>
                                <div class="bar pink" style="height: <?= $dayData['incomplete_height'] ?>px;"></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="x-axis">
                        <?php foreach ($weeklyActivityData as $dayData): ?>
                        <div class="x-axis-label"><?= $dayData['day'] ?></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div class="legend-box green"></div>
                            <span>Nieuwe gebruikers</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box pink"></div>
                            <span>Vragen beantwoord</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="chart-block recent-activity-block">
                <h3 class="chart-title">Recente activiteit</h3>
                <p class="activity-subtitle">Admin logboek en systeemmeldingen</p>
                <div class="activity-log">
                    <?php if (empty($recentAdminActions)): ?>
                    <div class="activity-item">
                        <div class="activity-content">
                            <div class="activity-name" style="color: #999;">Geen activiteit in de afgelopen 7 dagen</div>
                        </div>
                    </div>
                    <?php else: ?>
                    <?php foreach ($recentAdminActions as $action): 
                        // Generate avatar initials from admin name
                        $nameParts = explode(' ', trim($action['admin_name']));
                        $initials = '';
                        foreach ($nameParts as $part) {
                            $initials .= strtoupper(substr($part, 0, 1));
                        }
                        $initials = substr($initials, 0, 2) ?: 'A';
                    ?>
                    <div class="activity-item">
                        <div class="activity-avatar"><?= htmlspecialchars($initials) ?></div>
                        <div class="activity-content">
                            <div class="activity-name"><?= htmlspecialchars($action['admin_name']) ?> (Admin)</div>
                            <div class="activity-action"><?= htmlspecialchars($action['action_text']) ?></div>
                        </div>
                        <div class="activity-time"><?= htmlspecialchars($action['time_ago']) ?></div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Trend Row -->
        <div class="trend-row">
            <div class="chart-block trend-block">
                <h3 class="chart-title">Gemiddelde Score Trend</h3>
                <div class="trend-chart">
                    <svg class="trend-svg" viewBox="0 0 300 120" preserveAspectRatio="xMidYMid meet">
                        <!-- Axes -->
                        <line x1="10" y1="10" x2="10" y2="100" stroke="#000" stroke-width="1"/>
                        <line x1="10" y1="100" x2="290" y2="100" stroke="#000" stroke-width="1"/>
                        
                        <!-- Trend line -->
                        <?php if (!empty($trendDataWithCoords['points'])): ?>
                        <polyline 
                            points="<?= $trendDataWithCoords['points'] ?>"
                            fill="none"
                            stroke="#008000"
                            stroke-width="0.5"
                        />
                        
                        <!-- Data points -->
                        <?php foreach ($trendDataWithCoords['coordinates'] as $coord): ?>
                        <circle cx="<?= $coord['x'] ?>" cy="<?= $coord['y'] ?>" r="1" fill="#008000"/>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <text x="150" y="50" text-anchor="middle" fill="#999">Geen gegevens beschikbaar</text>
                        <?php endif; ?>
                    </svg>
                </div>
                <div class="trend-legend">
                    <span class="trend-label">Gemiddelde Score</span>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../components/footer.php'; ?>
    <script src="../../assets/js/script.js"></script>
</body>
</html>
