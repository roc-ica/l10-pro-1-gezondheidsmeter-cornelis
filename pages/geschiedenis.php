<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../src/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../src/models/History.php';

$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? 'Gebruiker';
$userId = $_SESSION['user_id'];

// Get the period parameter (default to 'week')
$period = $_GET['period'] ?? 'week';
$validPeriods = ['week', 'month', 'year'];
if (!in_array($period, $validPeriods)) {
    $period = 'week';
}

$history = new History();

// Get stats based on period
switch ($period) {
    case 'month':
        $stats = $history->getMonthlyStats($userId);
        $periodLabel = 'maand';
        break;
    case 'year':
        $stats = $history->getYearlyStats($userId);
        $periodLabel = 'jaar';
        break;
    default:
        $stats = $history->getWeeklyStats($userId);
        $periodLabel = 'week';
}

$summaryStats = $history->getSummaryStats($userId);

// Prepare data for Chart.js
$chartLabels = array_column($stats, 'day_name');
$chartData = [
    'Voeding' => [],
    'Beweging' => [],
    'Slaap' => [],
    'Stress' => []
];

foreach ($stats as $dayStat) {
    $chartData['Voeding'][] = $dayStat['scores']['Voeding'] ?? 0;
    $chartData['Beweging'][] = $dayStat['scores']['Beweging'] ?? 0;
    $chartData['Slaap'][] = $dayStat['scores']['Slaap'] ?? 0;
    $chartData['Stress'][] = $dayStat['scores']['Stress'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geschiedenis - Gezondheidsmeter</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/popup.css">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/images/icons/gm192x192.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .period-selector {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .period-btn {
            background: #ffffff;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 24px;
            font-size: 15px;
            font-weight: 600;
            color: #374151;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .period-btn:hover {
            border-color: #16a34a;
            color: #16a34a;
            transform: translateY(-1px);
        }

        .period-btn.active {
            background: #16a34a;
            border-color: #16a34a;
            color: white;
            box-shadow: 0 2px 6px rgba(22, 163, 74, 0.25);
        }

        .period-btn.active:hover {
            background: #15803d;
            border-color: #15803d;
        }
    </style>
</head>
<body class="auth-page">
    <?php include __DIR__ . '/../components/navbar.php'; ?>
    
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="dashboard-header-left">
                <h1>Jouw Gezondheidsgeschiedenis</h1>
                <p>Bekijk je voortgang en trends van de afgelopen <?php echo $periodLabel; ?>.</p>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="stats-row">
            <div class="stat-card stat-card-primary">
                <div class="stat-content">
                    <div class="stat-label">Gemiddelde Score</div>
                    <div class="stat-number"><?php echo $summaryStats['average_score']; ?></div>
                </div>
            </div>
            <div class="stat-card stat-card-success">
                <div class="stat-content">
                    <div class="stat-label">Beste Dag</div>
                    <div class="stat-number"><?php echo htmlspecialchars($summaryStats['best_day']); ?></div>
                </div>
            </div>
            <div class="stat-card stat-card-warning">
                <div class="stat-content">
                    <div class="stat-label">Trend</div>
                    <div class="stat-number"><?php echo htmlspecialchars($summaryStats['trend']); ?></div>
                </div>
            </div>
            <div class="stat-card stat-card-info">
                <div class="stat-content">
                    <div class="stat-label">Streak</div>
                    <div class="stat-number"><?php echo $summaryStats['streak']; ?> Dagen</div>
                </div>
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

        <!-- Weekly Progress Chart -->
        <div class="dashboard-card dashboard-card-full">
            <div class="card-header">
                <h3><?php echo ucfirst($periodLabel); ?>lijkse Voortgang</h3>
                <div class="chart-legend">
                    <div class="legend-item">
                        <div class="legend-dot" style="background: #22c55e;"></div>
                        <span>Beweging</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot" style="background: #3b82f6;"></div>
                        <span>Slaap</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot" style="background: #eab308;"></div>
                        <span>Stress</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot" style="background: #f97316;"></div>
                        <span>Voeding</span>
                    </div>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="weeklyChart"></canvas>
            </div>
        </div>

       
    </div>

    <?php include __DIR__ . '/../components/footer.php'; ?>
    <script src="/js/pwa.js"></script>
    <script src="../assets/js/popup.js"></script>
    <script>
        // Chart.js Configuration
        const ctx = document.getElementById('weeklyChart').getContext('2d');
        const weeklyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [
                    {
                        label: 'Beweging',
                        data: <?php echo json_encode($chartData['Beweging']); ?>,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#22c55e',
                        pointBorderWidth: 2,
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Slaap',
                        data: <?php echo json_encode($chartData['Slaap']); ?>,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#3b82f6',
                        pointBorderWidth: 2,
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Stress',
                        data: <?php echo json_encode($chartData['Stress']); ?>,
                        borderColor: '#eab308',
                        backgroundColor: 'rgba(234, 179, 8, 0.1)',
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#eab308',
                        pointBorderWidth: 2,
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Voeding',
                        data: <?php echo json_encode($chartData['Voeding']); ?>,
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.1)',
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#f97316',
                        pointBorderWidth: 2,
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            borderDash: [2, 2]
                        },
                        ticks: {
                            stepSize: 25
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
    <script src="/js/session-guard.js"></script>
</body>
</html>