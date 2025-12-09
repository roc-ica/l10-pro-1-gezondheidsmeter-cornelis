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

// Get weekly stats
$weekAgo = date('Y-m-d', strtotime('-7 days'));
$stmt = $pdo->prepare("
    SELECT de.entry_date, COUNT(a.id) as answered_count 
    FROM daily_entries de
    LEFT JOIN answers a ON de.id = a.entry_id
    WHERE de.user_id = ? AND de.entry_date >= ?
    GROUP BY de.entry_date
    ORDER BY de.entry_date ASC
");
$stmt->execute([$userId, $weekAgo]);
$weeklyStats = $stmt->fetchAll();

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
$trendData = $history->getTrendData(30);
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
    <title>Je Gezondheid - Gezondheidsmeter</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="manifest" href="/manifest.json">
</head>
<body class="auth-page">
    <?php include __DIR__ . '/../components/navbar.php'; ?>
    
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="dashboard-header-left">
                <h1>Je Gezondheid</h1>
                <p>Bekijk je gezondheidsscores en voortgang</p>
            </div>
        </div>

        <?php if (!empty($todayAnswers)): ?>
            <!-- Success Message -->
            <div class="success-message">
                <div class="success-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <polyline points="22 4 12 14.01 9 11.01" />
                    </svg>
                </div>
                <div class="success-content">
                    <h2>Gefeliciteerd! 🎉</h2>
                    <p>Je hebt de vragenlijst van vandaag voltooid</p>
                </div>
            </div>

            <!-- Today's Score Card -->
            <?php if ($todayScore): ?>
            <div class="today-score-card">
                <p class="today-score-subtitle">Vandaag's Gezondheid Score</p>
                <div class="today-score-value">
                    <?php echo round($todayScore['overall_score'], 1); ?>/100
                </div>
                <p class="today-score-date">
                    <?php echo htmlspecialchars(date('d M Y', strtotime($todayScore['score_date']))); ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Stats Row -->
            <div class="stats-row">
                <div class="stats-card">
                    <p class="stats-label">Gemiddelde (7 dagen)</p>
                    <p class="stats-value stats-value-average">
                        <?php echo $avgScore ? round($avgScore, 1) : 'N/A'; ?>
                    </p>
                </div>
                <div class="stats-card">
                    <p class="stats-label">Huidige Streak</p>
                    <p class="stats-value stats-value-days">
                        <?php echo $currentStreak; ?> 🔥
                    </p>
                </div>
                <div class="stats-card">
                    <p class="stats-label">Totaal Ingevuld</p>
                    <p class="stats-value stats-value-days">
                        <?php echo $totalEntries; ?>
                    </p>
                </div>
            </div>

            <!-- Pillar Breakdown -->
            <?php if ($pillarScores && is_array($pillarScores)): ?>
            <div class="pillar-section">
                <h3 class="pillar-section-title">Score Per Categorie</h3>
                <div class="pillar-grid">
                    <?php foreach ($pillarScores as $pillarId => $score): ?>
                    <div class="pillar-card">
                        <p class="pillar-card-label">Categorie <?php echo $pillarId; ?></p>
                        <p class="pillar-card-score"><?php echo round($score, 1); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Answers by Pillar -->
            <div class="dashboard-card dashboard-card-full">
                <div class="card-header">
                    <h3>Jouw Antwoorden van Vandaag</h3>
                </div>
                <div class="pillars-grid">
                    <?php foreach ($answersByPillar as $pillarData): ?>
                        <div class="pillar-section" style="border-left: 4px solid <?php echo htmlspecialchars($pillarData['color']); ?>">
                            <h4 class="pillar-title"><?php echo htmlspecialchars($pillarData['name']); ?></h4>
                            <div class="pillar-answers">
                                <?php foreach ($pillarData['answers'] as $answer): ?>
                                    <div class="answer-item">
                                        <div class="answer-question"><?php echo htmlspecialchars($answer['question_text']); ?></div>
                                        <div class="answer-response"><?php echo htmlspecialchars($answer['answer_text']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Trends Section -->
            <?php if (!empty($trendData)): ?>
            <div class="trends-section">
                <h3 class="trends-title">Gezondheid Trend (30 dagen)</h3>
                <div class="trends-table">
                    <table class="trends-chart">
                        <thead>
                            <tr>
                                <th>Datum</th>
                                <th>Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse($trendData) as $trend): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('d M Y', strtotime($trend['score_date']))); ?></td>
                                <td><?php echo round($trend['overall_score'], 1); ?>/100</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Weekly Activity Chart -->
            <?php if (!empty($weeklyStats)): ?>
            <div class="dashboard-card dashboard-card-full">
                <div class="card-header">
                    <h3>Activiteit Afgelopen Week</h3>
                </div>
                <div class="chart-container">
                    <canvas id="weeklyActivityChart"></canvas>
                </div>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- No results yet -->
            <div class="dashboard-card dashboard-card-full">
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </div>
                    <h2>Nog geen resultaten</h2>
                    <p>Je hebt vandaag nog geen vragenlijst ingevuld.</p>
                    <a href="vragen-hierarchical.php" class="btn-primary">Start Vragenlijst</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="action-buttons-container">
            <a href="../pages/vragen-hierarchical.php" class="btn-action btn-action-primary">
                Antwoord Vragen
            </a>
            <a href="../pages/home.php" class="btn-action btn-action-secondary">
                Home
            </a>
        </div>
    </div>

    <?php include __DIR__ . '/../components/footer.php'; ?>

    <script src="/js/pwa.js"></script>
    <script src="/js/session-guard.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Weekly Activity Chart
        const ctx = document.getElementById('weeklyActivityChart');
        if (ctx) {
            const weeklyData = <?php echo json_encode($weeklyStats); ?>;
            const labels = weeklyData.map(d => {
                const date = new Date(d.entry_date);
                return date.toLocaleDateString('nl-NL', { weekday: 'short', day: 'numeric', month: 'short' });
            });
            const data = weeklyData.map(d => parseInt(d.answered_count));

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Beantwoorde Vragen',
                        data: data,
                        backgroundColor: 'rgba(22, 163, 74, 0.8)',
                        borderColor: '#16a34a',
                        borderWidth: 2,
                        borderRadius: 8
                    }]
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
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
