<?php
session_start();


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../src/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../classes/History.php';

$isLoggedIn = isset($_SESSION['user_id']);

$username = $_SESSION['username'] ?? 'Gebruiker';
$userId = $_SESSION['user_id'];

$history = new History();
$weeklyStats = $history->getWeeklyStats($userId);
$summaryStats = $history->getSummaryStats($userId);

// Prepare data for Chart.js
$chartLabels = array_column($weeklyStats, 'day_name');
$chartData = [
    'Voeding' => [],
    'Beweging' => [],
    'Slaap' => [],
    'Stress' => [] // Mapped from Mentaal/Stress
];

foreach ($weeklyStats as $dayStat) {
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
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/images/icons/gm192x192.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="auth-page">
    <?php include __DIR__ . '/../components/navbar.php'; ?>
    
    <div class="dashboard-container">
        <div class="history-header">
            <h1>Jouw Gezondheidsgeschiedenis</h1>
            <p>Bekijk je voortgang en trends van de afgelopen week.</p>
        </div>

        <!-- Summary Cards -->
        <div class="stats-row">
            <div class="stat-block">
                <div class="stat-label">Gemiddelde Score</div>
                <div class="stat-number green-text"><?php echo $summaryStats['average_score']; ?></div>
            </div>
            <div class="stat-block">
                <div class="stat-label">Beste Dag</div>
                <div class="stat-number green-text"><?php echo htmlspecialchars($summaryStats['best_day']); ?></div>
            </div>
            <div class="stat-block">
                <div class="stat-label">Trend</div>
                <div class="stat-number green-text"><?php echo htmlspecialchars($summaryStats['trend']); ?></div>
            </div>
            <div class="stat-block">
                <div class="stat-label">Streak</div>
                <div class="stat-number green-text"><?php echo $summaryStats['streak']; ?> Dagen</div>
            </div>
        </div>

        <!-- Weekly Progress Chart -->
        <div class="chart-section">
            <div class="chart-block">
                <h2 class="chart-title">Wekelijkse Voortgang</h2>
                <div class="chart-legend">
                    <div class="legend-item">
                        <span class="legend-icon" style="color: #22c55e;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                        </span>
                        <span class="legend-text" style="color: #22c55e;">Beweging</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-icon" style="color: #3b82f6;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h20M2 12l5-5m0 10l-5-5m20 0l-5-5m0 10l5-5"/></svg>
                        </span>
                        <span class="legend-text" style="color: #3b82f6;">Slaap</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-icon" style="color: #eab308;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M16 16s-1.5-2-4-2-4 2-4 2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                        </span>
                        <span class="legend-text" style="color: #eab308;">Stress</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-icon" style="color: #f97316;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
                        </span>
                        <span class="legend-text" style="color: #f97316;">Voeding</span>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="weeklyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Detailed Statistics Table -->
        <div class="table-section">
            <div class="table-block">
                <h2 class="table-title">Gedetailleerde Statistieken</h2>
                <div class="table-responsive">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Dag</th>
                                <th>Slaap</th>
                                <th>Voeding</th>
                                <th>Beweging</th>
                                <th>Stress</th>
                                <!-- <th>Hydratatie</th> --> <!-- Not in main chart, maybe add later if needed -->
                                <!-- <th>Mentaal</th> --> <!-- Mapped to Stress -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($weeklyStats as $stat): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stat['day_name']); ?></td>
                                <td><?php echo $stat['scores']['Slaap'] ?? '-'; ?></td>
                                <td><?php echo $stat['scores']['Voeding'] ?? '-'; ?></td>
                                <td><?php echo $stat['scores']['Beweging'] ?? '-'; ?></td>
                                <td><?php echo $stat['scores']['Stress'] ?? '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <?php include __DIR__ . '/../components/footer.php'; ?>
    <script src="/js/pwa.js"></script>
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
                        backgroundColor: '#22c55e',
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#22c55e',
                        pointBorderWidth: 2,
                        borderWidth: 2,
                        tension: 0.4
                    },
                    {
                        label: 'Slaap',
                        data: <?php echo json_encode($chartData['Slaap']); ?>,
                        borderColor: '#3b82f6',
                        backgroundColor: '#3b82f6',
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#3b82f6',
                        pointBorderWidth: 2,
                        borderWidth: 2,
                        tension: 0.4
                    },
                    {
                        label: 'Stress',
                        data: <?php echo json_encode($chartData['Stress']); ?>,
                        borderColor: '#eab308',
                        backgroundColor: '#eab308',
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#eab308',
                        pointBorderWidth: 2,
                        borderWidth: 2,
                        tension: 0.4
                    },
                    {
                        label: 'Voeding',
                        data: <?php echo json_encode($chartData['Voeding']); ?>,
                        borderColor: '#f97316',
                        backgroundColor: '#f97316',
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#f97316',
                        pointBorderWidth: 2,
                        borderWidth: 2,
                        tension: 0.4
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