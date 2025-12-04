<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../src/views/auth/login.php');
    exit;
}

$username = $_SESSION['username'] ?? 'Gebruiker';
$userId = $_SESSION['user_id'];

// Database connection
require_once __DIR__ . '/../src/config/database.php';
$pdo = Database::getConnection();

// Get today's entry
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT * FROM daily_entries 
    WHERE user_id = ? AND entry_date = ?
");
$stmt->execute([$userId, $today]);
$todayEntry = $stmt->fetch();

// Get today's answers with question details
$todayAnswers = [];
if ($todayEntry) {
    $stmt = $pdo->prepare("
        SELECT a.*, q.question_text, q.pillar_id, p.name as pillar_name, p.color as pillar_color
        FROM answers a
        JOIN questions q ON a.question_id = q.id
        JOIN pillars p ON q.pillar_id = p.id
        WHERE a.entry_id = ?
        ORDER BY q.pillar_id, q.id
    ");
    $stmt->execute([$todayEntry['id']]);
    $todayAnswers = $stmt->fetchAll();
}

// Get statistics for the last 7 days
$weekAgo = date('Y-m-d', strtotime('-7 days'));
$stmt = $pdo->prepare("
    SELECT 
        de.entry_date,
        COUNT(DISTINCT a.question_id) as answered_count
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
    <link rel="apple-touch-icon" href="/assets/images/icons/gm192x192.png">
</head>

<body class="auth-page">
    <?php include __DIR__ . '/../components/navbar.php'; ?>

    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="dashboard-header-left">
                <h1>Jouw Resultaten</h1>
                <p><?= date('l j F Y') ?></p>
            </div>
            <div class="dashboard-header-right">
                <a href="vragen.php" class="btn-naar-app">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        style="display: inline-block; vertical-align: middle; margin-right: 6px;">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Nieuwe Vragenlijst
                </a>
            </div>
        </div>

        <?php if ($todayEntry && $todayEntry['submitted_at']): ?>
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
                    <p>Je hebt de vragenlijst van vandaag voltooid om
                        <?= date('H:i', strtotime($todayEntry['submitted_at'])) ?> uur</p>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="stats-row">
                <div class="stat-card stat-card-primary">
                    <div class="stat-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4" />
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Vragen Beantwoord</div>
                        <div class="stat-number"><?= count($todayAnswers) ?></div>
                        <div class="stat-subtitle">Vandaag</div>
                    </div>
                </div>

                <div class="stat-card stat-card-success">
                    <div class="stat-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Huidige Streak</div>
                        <div class="stat-number"><?= $currentStreak ?> dagen</div>
                        <div class="stat-subtitle">Blijf doorgaan! 🔥</div>
                    </div>
                </div>

                <div class="stat-card stat-card-info">
                    <div class="stat-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                            <line x1="16" y1="2" x2="16" y2="6" />
                            <line x1="8" y1="2" x2="8" y2="6" />
                            <line x1="3" y1="10" x2="21" y2="10" />
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Totaal Ingevuld</div>
                        <div class="stat-number"><?= $totalEntries ?></div>
                        <div class="stat-subtitle">Vragenlijsten</div>
                    </div>
                </div>
            </div>

            <!-- Answers by Pillar -->
            <div class="dashboard-card dashboard-card-full">
                <div class="card-header">
                    <h3>Jouw Antwoorden van Vandaag</h3>
                </div>
                <div class="pillars-grid">
                    <?php foreach ($answersByPillar as $pillarData): ?>
                        <div class="pillar-section"
                            style="border-left: 4px solid <?= htmlspecialchars($pillarData['color']) ?>">
                            <h4 class="pillar-title"><?= htmlspecialchars($pillarData['name']) ?></h4>
                            <div class="pillar-answers">
                                <?php foreach ($pillarData['answers'] as $answer): ?>
                                    <div class="answer-item">
                                        <div class="answer-question"><?= htmlspecialchars($answer['question_text']) ?></div>
                                        <div class="answer-response"><?= htmlspecialchars($answer['answer_text']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Weekly Activity Chart -->
            <div class="dashboard-card dashboard-card-full">
                <div class="card-header">
                    <h3>Activiteit Afgelopen Week</h3>
                </div>
                <div class="chart-container">
                    <canvas id="weeklyActivityChart"></canvas>
                </div>
            </div>

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
                    <a href="vragen.php" class="btn-primary">Start Vragenlijst</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../components/footer.php'; ?>

    <script src="/js/pwa.js"></script>
    <script src="/js/session-guard.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Weekly Activity Chart
        const ctx = document.getElementById('weeklyActivityChart');
        if (ctx) {
            const weeklyData = <?= json_encode($weeklyStats) ?>;
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