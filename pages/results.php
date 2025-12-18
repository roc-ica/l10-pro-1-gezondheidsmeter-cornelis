<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../src/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../classes/UserHealthHistory.php';

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Gebruiker';
$date = $_GET['date'] ?? date('Y-m-d');

$pdo = Database::getConnection();

// Get today's answers with pillar info
$stmt = $pdo->prepare("
    SELECT a.*, q.pillar_id, p.name as pillar_name, p.color as pillar_color, q.question_text
    FROM answers a
    JOIN questions q ON a.question_id = q.id
    JOIN pillars p ON q.pillar_id = p.id
    JOIN daily_entries de ON a.entry_id = de.id
    WHERE de.user_id = ? AND de.entry_date = ?
    ORDER BY q.pillar_id ASC
");
$stmt->execute([$userId, $date]);
$todayAnswers = $stmt->fetchAll();

// Get total entries count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total FROM daily_entries 
    WHERE user_id = ? AND submitted_at IS NOT NULL
");
$stmt->execute([$userId]);
$totalEntries = $stmt->fetch()['total'];

// Calculate current streak
$stmt = $pdo->prepare("
    SELECT entry_date FROM daily_entries 
    WHERE user_id = ? AND submitted_at IS NOT NULL
    ORDER BY entry_date DESC
");
$stmt->execute([$userId]);
$allEntries = $stmt->fetchAll(PDO::FETCH_COLUMN);

$currentStreak = 0;
$checkDate = new DateTime();
foreach ($allEntries as $entryDate) {
    $entry = new DateTime($entryDate);
    if ($entry->format('Y-m-d') === $checkDate->format('Y-m-d')) {
        $currentStreak++;
        $checkDate->modify('-1 day');
    } else {
        break;
    }
}

// Get today's score
$history = new UserHealthHistory($userId);
$todayScore = $history->getTodayScore();
$pillarScores = !empty($todayAnswers) ? $history->getPillarScores($date) : null;
$trendData = $history->getTrendData(30); // Get last 30 days
$avgScore = $history->getAverageScore(7);

// Group answers by pillar
$answersByPillar = [];
foreach ($todayAnswers as $answer) {
    $pillarId = $answer['pillar_id'];
    if (!isset($answersByPillar[$pillarId])) {
        $answersByPillar[$pillarId] = [
            'name' => $answer['pillar_name'],
            'color' => $answer['pillar_color'],
            'answers' => []
        ];
    }
    $answersByPillar[$pillarId]['answers'][] = $answer;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultaten - Gezondheidsmeter</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="manifest" href="/manifest.json">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Specific overrides for a cleaner, professional look */
        body {
            background-color: #f8f9fa;
        }
        .dashboard-container {
            max-width: 1000px;
        }
        .dashboard-header h1 {
            color: #1a1a1a;
        }
        .status-banner {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #16a34a;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .status-content h2 {
            font-size: 18px;
            margin: 0 0 4px 0;
            color: #111827;
        }
        .status-content p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }
        
        .score-display-large {
            text-align: center;
            padding: 30px;
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .score-value-main {
            font-size: 48px;
            font-weight: 800;
            color: #16a34a;
            line-height: 1;
            margin-bottom: 8px;
        }
        .score-label-main {
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            font-size: 12px;
            font-weight: 600;
        }
        
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .kpi-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            text-align: center;
        }
        .kpi-label {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        .kpi-value {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
        }
        
        .chart-section {
            background: white;
            padding: 24px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .chart-header {
            margin-bottom: 20px;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 10px;
        }
        .chart-title {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            margin: 0;
        }
        
        .details-table-container {
            background: white;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .details-header {
            padding: 16px 24px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }
        .details-header h3 {
            margin: 0;
            font-size: 16px;
            color: #1f2937;
        }
        
        .answer-row {
            display: flex;
            justify-content: space-between;
            padding: 16px 24px;
            border-bottom: 1px solid #f3f4f6;
        }
        .answer-row:last-child {
            border-bottom: none;
        }
        .answer-q {
            color: #4b5563;
            font-weight: 500;
        }
        .answer-a {
            color: #111827;
            font-weight: 600;
        }
        
        .pillar-header {
            padding: 12px 24px;
            background: #f3f4f6;
            color: #374151;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .pillar-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .action-row {
            display: flex;
            gap: 12px;
            margin-top: 32px;
        }
        .btn-clean {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
            text-align: center;
        }
        .btn-clean-primary {
            background-color: #16a34a;
            color: white;
        }
        .btn-clean-primary:hover {
            background-color: #15803d;
        }
        .btn-clean-secondary {
            background-color: white;
            border: 1px solid #d1d5db;
            color: #374151;
        }
        .btn-clean-secondary:hover {
            background-color: #f9fafb;
            border-color: #9ca3af;
        }
        
        .empty-state-clean {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .empty-icon-clean {
            color: #d1d5db;
            margin-bottom: 16px;
        }
    </style>
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
            <a href="../pages/vragen.php?reset=1" class="btn-clean btn-clean-primary" style="flex: 1;" onclick="return confirm('Weet je zeker dat je je antwoorden wilt wissen en opnieuw wilt beginnen?');">
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
    <script>
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
