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
    <style>
        /* Tooltip Styles */
        .chart-tooltip {
            position: absolute;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s ease;
            z-index: 1000;
            white-space: nowrap;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .chart-tooltip.visible {
            opacity: 1;
        }
        
        .chart-tooltip-label {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .chart-tooltip-value {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .chart-tooltip-value:last-child {
            margin-bottom: 0;
        }
        
        /* Make bar groups interactive */
        .day-bars {
            cursor: pointer;
            position: relative;
        }
        
        .day-bars:hover .bar {
            opacity: 0.85;
            transition: opacity 0.2s ease;
        }
    </style>
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
                            <div class="day-bars" 
                                 data-day="<?= $dayData['day'] ?>" 
                                 data-date="<?= $dayData['date'] ?>" 
                                 data-submitted="<?= $dayData['submitted'] ?>" 
                                 data-incomplete="<?= $dayData['incomplete'] ?>">
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
                <div class="chart-tooltip" id="tooltip-weekly"></div>
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
    <script>
        // Tooltip functionality for weekly activity chart
        document.addEventListener('DOMContentLoaded', function() {
            const dayBars = document.querySelectorAll('.day-bars');
            const tooltip = document.getElementById('tooltip-weekly');
            
            if (tooltip) {
                dayBars.forEach(bar => {
                    bar.addEventListener('mouseenter', function(e) {
                        const day = this.getAttribute('data-day');
                        const date = this.getAttribute('data-date');
                        const submitted = this.getAttribute('data-submitted');
                        const incomplete = this.getAttribute('data-incomplete');
                        const total = parseInt(submitted) + parseInt(incomplete);
                        
                        // Format date
                        const dateObj = new Date(date + 'T00:00:00');
                        const formattedDate = dateObj.toLocaleDateString('nl-NL', { 
                            day: '2-digit', 
                            month: '2-digit', 
                            year: 'numeric' 
                        });
                        
                        tooltip.innerHTML = 
                            '<div class="chart-tooltip-label">' + day + ' - ' + formattedDate + '</div>' +
                            '<div class="chart-tooltip-value" style="color: #22c55e;">✓ Afgerond: ' + submitted + '</div>' +
                            '<div class="chart-tooltip-value" style="color: #ff6c6c;">○ Incompleet: ' + incomplete + '</div>' +
                            '<div class="chart-tooltip-value" style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 4px; margin-top: 4px;">Totaal: ' + total + '</div>';
                        tooltip.classList.add('visible');
                    });
                    
                    bar.addEventListener('mousemove', function(e) {
                        // Get tooltip dimensions
                        const tooltipRect = tooltip.getBoundingClientRect();
                        const tooltipWidth = tooltipRect.width;
                        const tooltipHeight = tooltipRect.height;
                        
                        // Get viewport dimensions
                        const viewportWidth = window.innerWidth;
                        const viewportHeight = window.innerHeight;
                        
                        // Calculate initial position
                        let left = e.pageX + 15;
                        let top = e.pageY - 10;
                        
                        // Adjust if tooltip goes off right edge
                        if (left + tooltipWidth > viewportWidth) {
                            left = e.pageX - tooltipWidth - 15;
                        }
                        
                        // Adjust if tooltip goes off bottom edge
                        if (top + tooltipHeight > viewportHeight + window.scrollY) {
                            top = e.pageY - tooltipHeight - 10;
                        }
                        
                        // Adjust if tooltip goes off left edge
                        if (left < 0) {
                            left = 10;
                        }
                        
                        // Adjust if tooltip goes off top edge
                        if (top < window.scrollY) {
                            top = window.scrollY + 10;
                        }
                        
                        tooltip.style.left = left + 'px';
                        tooltip.style.top = top + 'px';
                    });
                    
                    bar.addEventListener('mouseleave', function() {
                        tooltip.classList.remove('visible');
                    });
                    
                    // Add touch support for mobile
                    bar.addEventListener('touchstart', function(e) {
                        e.preventDefault();
                        const day = this.getAttribute('data-day');
                        const date = this.getAttribute('data-date');
                        const submitted = this.getAttribute('data-submitted');
                        const incomplete = this.getAttribute('data-incomplete');
                        const total = parseInt(submitted) + parseInt(incomplete);
                        
                        const dateObj = new Date(date + 'T00:00:00');
                        const formattedDate = dateObj.toLocaleDateString('nl-NL', { 
                            day: '2-digit', 
                            month: '2-digit', 
                            year: 'numeric' 
                        });
                        
                        tooltip.innerHTML = 
                            '<div class="chart-tooltip-label">' + day + ' - ' + formattedDate + '</div>' +
                            '<div class="chart-tooltip-value" style="color: #22c55e;">✓ Afgerond: ' + submitted + '</div>' +
                            '<div class="chart-tooltip-value" style="color: #ff6c6c;">○ Incompleet: ' + incomplete + '</div>' +
                            '<div class="chart-tooltip-value" style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 4px; margin-top: 4px;">Totaal: ' + total + '</div>';
                        
                        // Position tooltip centered above the bar on mobile
                        const barRect = this.getBoundingClientRect();
                        const tooltipRect = tooltip.getBoundingClientRect();
                        
                        let left = barRect.left + (barRect.width / 2) - (tooltipRect.width / 2);
                        let top = barRect.top + window.scrollY - tooltipRect.height - 10;
                        
                        // Keep within viewport
                        if (left < 10) left = 10;
                        if (left + tooltipRect.width > window.innerWidth - 10) {
                            left = window.innerWidth - tooltipRect.width - 10;
                        }
                        if (top < window.scrollY + 10) {
                            top = barRect.bottom + window.scrollY + 10;
                        }
                        
                        tooltip.style.left = left + 'px';
                        tooltip.style.top = top + 'px';
                        tooltip.classList.add('visible');
                    });
                    
                    bar.addEventListener('touchend', function() {
                        setTimeout(() => {
                            tooltip.classList.remove('visible');
                        }, 2000); // Hide after 2 seconds on mobile
                    });
                });
            }
        });
    </script>
</body>
</html>
