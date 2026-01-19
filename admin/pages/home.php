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

// Get period from URL parameter (default to 'week')
$period = $_GET['period'] ?? 'week';
$validPeriods = ['week', 'month', 'year'];
if (!in_array($period, $validPeriods)) {
    $period = 'week';
}

// Get period label
$periodLabels = [
    'week' => 'Week',
    'month' => 'Maand',
    'year' => 'Jaar'
];
$periodLabel = $periodLabels[$period];

// Get all data based on selected period
$totalUsers = $stats->getTotalUsers();
$totalAnswers = $stats->getTotalAnswersInPeriod($period);
$averageScore = $stats->getAverageScoreInPeriod($period);
$activeInPeriod = $stats->getActiveInPeriod($period);
$weeklyActivityData = $stats->getWeeklyActivityWithHeights();
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
                <h1>Admin Dashboard</h1>
                <p>Overzicht van alle gegevens</p>
            </div>
            <div class="dashboard-header-right">
                <a href="../../pages/home.php" class="btn-naar-app">Naar App</a>
            </div>
        </div>

        <!-- Period Selector -->
        <div class="period-selector">
            <a href="?period=week" class="period-btn <?php echo $period === 'week' ? 'active' : ''; ?>">
                Week
            </a>
            <a href="?period=month" class="period-btn <?php echo $period === 'month' ? 'active' : ''; ?>">
                Maand
            </a>
            <a href="?period=year" class="period-btn <?php echo $period === 'year' ? 'active' : ''; ?>">
                Jaar
            </a>
        </div>
        
        <div class="period-info">
            Statistieken van de afgelopen <strong><?= strtolower($periodLabel) ?></strong>
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
                <div class="stat-sublabel">In de afgelopen <?= strtolower($periodLabel) ?></div>
            </div>
            <div class="stat-block">
                <div class="stat-label">Gemiddelde Score</div>
                <div class="stat-number"><?= $averageScore ?></div>
                <div class="stat-sublabel">In de afgelopen <?= strtolower($periodLabel) ?></div>
            </div>
            <div class="stat-block">
                <div class="stat-label">Actieve Gebruikers</div>
                <div class="stat-number"><?= number_format($activeInPeriod) ?></div>
                <div class="stat-sublabel">In de afgelopen <?= strtolower($periodLabel) ?></div>
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
                            <?php 
                            $maxVal = $weeklyActivityData['max'];
                            ?>
                            <div class="y-axis-label"><?= $maxVal ?></div>
                            <div class="y-axis-label"><?= round($maxVal * 0.75) ?></div>
                            <div class="y-axis-label"><?= round($maxVal * 0.5) ?></div>
                            <div class="y-axis-label"><?= round($maxVal * 0.25) ?></div>
                            <div class="y-axis-label">0</div>
                        </div>
                        <div class="bars-container">
                            <?php foreach ($weeklyActivityData['days'] as $dayData): ?>
                            <div class="day-bars" title="<?= $dayData['day'] ?>: <?= $dayData['submitted'] ?> afgerond, <?= $dayData['incomplete'] ?> incompleet">
                                <div class="bar green" style="height: <?= $dayData['submitted_height'] ?>px;"></div>
                                <div class="bar pink" style="height: <?= $dayData['incomplete_height'] ?>px;"></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="x-axis">
                        <?php foreach ($weeklyActivityData['days'] as $dayData): ?>
                        <div class="x-axis-label"><?= $dayData['day'] ?></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div class="legend-box green"></div>
                            <span>Afgerond</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box pink"></div>
                            <span>Incompleet</span>
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

    </div>

    <?php include __DIR__ . '/../../components/footer.php'; ?>
    <script src="../../assets/js/script.js"></script>
</body>
</html>
