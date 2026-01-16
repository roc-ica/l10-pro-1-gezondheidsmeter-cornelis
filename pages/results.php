<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../src/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/UserHealthHistory.php';

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Gebruiker';
$date = $_GET['date'] ?? date('Y-m-d');

$history = new UserHealthHistory($userId);

// Fetch Stats via Model (Refactored)
$todayAnswers = $history->getAnswersByDate($date);
$totalEntries = $history->getTotalSubmittedEntries();
$currentStreak = $history->getStreak();

// Get today's score and trend data
$todayScore = $history->getTodayScore();
$pillarScores = !empty($todayAnswers) ? $history->getPillarScores($date) : null;
$trendData = $history->getTrendData(30); 
$avgScore = $history->getAverageScore(7);

// Group answers by pillar via Model
$answersByPillar = $history->getGroupedAnswers($date);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultaten - Gezondheidsmeter</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/popup.css">
    <link rel="manifest" href="/manifest.json">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/results.css">
</head>
<body class="auth-page">
    <?php include __DIR__ . '/../components/navbar.php'; ?>
    
    <div class="dashboard-container">
        
        <?php if (!empty($todayAnswers)): ?>
            <!-- Status Banner -->
            <div class="status-banner">
                <div class="status-content">
                    <h2>Meting voltooid</h2>
                    <p>De gegevens van vandaag (<?php echo date('d-m-Y'); ?>) zijn succesvol opgeslagen.</p>
                </div>
            </div>

            <!-- Main Score -->
            <?php if ($todayScore): ?>
            <div class="score-display-large">
                <div class="score-label-main">Dagelijkse Score</div>
                <div class="score-value-main"><?php echo round($todayScore['overall_score']); ?></div>
                <div style="font-size: 13px; color: #9ca3af;">van de 100 punten</div>
            </div>
            <?php endif; ?>

            <!-- KPIs -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">7-daags Gemiddelde</div>
                    <div class="kpi-value"><?php echo $avgScore ? round($avgScore, 1) : '-'; ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Huidige Reeks (Dagen)</div>
                    <div class="kpi-value"><?php echo $currentStreak; ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Totaal Ingevuld</div>
                    <div class="kpi-value"><?php echo $totalEntries; ?></div>
                </div>
            </div>

            <!-- Score Trend Chart -->
             <?php 
                // Prepare last 7 days data for chart
                $chartLabels = [];
                $chartData = [];
                // Sort trend data by date
                $last7Days = array_slice($trendData, 0, 7); 
                $last7Days = array_reverse($last7Days); // Oldest to newest
                
                foreach ($last7Days as $point) {
                    $chartLabels[] = date('d M', strtotime($point['score_date']));
                    $chartData[] = round($point['overall_score'], 1);
                }
            ?>
            <?php if (!empty($chartData)): ?>
            <div class="chart-section">
                <div class="chart-header">
                    <h3 class="chart-title">Verloop Gezondheidsscore (Laatste 7 dagen)</h3>
                </div>
                <div style="height: 300px; width: 100%;">
                    <canvas id="scoreTrendChart"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <!-- Detailed Answers -->
            <div class="details-table-container">
                <div class="details-header">
                    <h3>Antwoordenoverzicht</h3>
                </div>
                <?php foreach ($answersByPillar as $pillarData): ?>
                    <div class="pillar-header">
                        <div class="pillar-indicator" style="background-color: <?php echo htmlspecialchars($pillarData['color'] ?? '#16a34a'); ?>"></div>
                        <?php echo htmlspecialchars($pillarData['name']); ?>
                    </div>
                    <?php foreach ($pillarData['answers'] as $answer): ?>
                        <div class="answer-row">
                            <span class="answer-q"><?php echo htmlspecialchars($answer['question_text']); ?></span>
                            <span class="answer-a"><?php echo htmlspecialchars($answer['answer_text']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state-clean">
                <div class="empty-icon-clean">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <h2 style="font-size: 20px; color: #1f2937; margin-bottom: 8px;">Nog geen gegevens</h2>
                <p style="color: #6b7280; margin-bottom: 24px;">Je hebt vandaag nog geen meting gedaan.</p>
                <a href="vragen.php" class="btn-clean btn-clean-primary" style="display: inline-block;">Start Meting</a>
                <br><br>
                <a href="../pages/home.php" style="color: #6b7280; text-decoration: none; font-size: 14px;">Terug naar dashboard</a>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <?php if (!empty($todayAnswers)): ?>
        <div class="action-row">
            <a href="#" class="btn-clean btn-clean-primary" style="flex: 1;" onclick="event.preventDefault(); confirmReset();">
                Opnieuw Invullen
            </a>
            <a href="../pages/home.php" class="btn-clean btn-clean-secondary" style="flex: 1;">
               Terug naar Dashboard
            </a>
        </div>
        <?php endif; ?>

    </div>

    <?php include __DIR__ . '/../components/footer.php'; ?>
    <script src="/js/pwa.js"></script>
    <script src="../assets/js/popup.js"></script>
    <script>
        // Confirm reset function
        function confirmReset() {
            showConfirm(
                'Weet je zeker dat je je antwoorden wilt wissen en opnieuw wilt beginnen?',
                'Opnieuw Beginnen',
                function() {
                    window.location.href = '../pages/vragen.php?reset=1';
                }
            );
        }
        
        // Score Chart
        const ctx = document.getElementById('scoreTrendChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chartLabels); ?>,
                    datasets: [{
                        label: 'Gezondheidsscore',
                        data: <?php echo json_encode($chartData); ?>,
                        backgroundColor: 'rgba(22, 163, 74, 0.1)',
                        borderColor: '#16a34a',
                        borderWidth: 2,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#16a34a',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#1f2937',
                            padding: 10,
                            bodyFont: {
                                size: 13
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            grid: {
                                color: '#f3f4f6'
                            },
                            ticks: {
                                font: {
                                    size: 11
                                },
                                color: '#6b7280'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 11
                                },
                                color: '#6b7280'
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
