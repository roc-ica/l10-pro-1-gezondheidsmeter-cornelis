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

// Time-based greeting
$hour = date('H');
if ($hour < 12) {
    $greeting = 'Goedemorgen';
} elseif ($hour < 18) {
    $greeting = 'Goedemiddag';
} else {
    $greeting = 'Goedenavond';
}

// Inspirational quotes
$quotes = [
    "Elke stap is er één. Ga zo door!",
    "Gezondheid is je grootste rijkdom.",
    "Luister naar je lichaam, het vertelt je verhaal.",
    "Vandaag is een nieuwe kans om je goed te voelen.",
    "Kleine veranderingen zorgen voor grote resultaten.",
    "Jouw welzijn staat vandaag op nummer één.",
    "Rust is net zo belangrijk als inspanning."
];
$randomQuote = $quotes[array_rand($quotes)];

// Get current date
$currentDate = date('l j F Y'); // Note: For localized Dutch date, setlocale() would be needed, or a mapping array.
// Simple Dutch date mapping for "human" feel
$days = ['Monday' => 'Maandag', 'Tuesday' => 'Dinsdag', 'Wednesday' => 'Woensdag', 'Thursday' => 'Donderdag', 'Friday' => 'Vrijdag', 'Saturday' => 'Zaterdag', 'Sunday' => 'Zondag'];
$months = ['January' => 'januari', 'February' => 'februari', 'March' => 'maart', 'April' => 'april', 'May' => 'mei', 'June' => 'juni', 'July' => 'juli', 'August' => 'augustus', 'September' => 'september', 'October' => 'oktober', 'November' => 'november', 'December' => 'december'];
$dayName = $days[date('l')];
$monthName = $months[date('F')];
$dayNum = date('j');
$year = date('Y');
$dutchDate = "$dayName $dayNum $monthName $year";


// Get total completed questionnaires
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM daily_entries 
    WHERE user_id = ? AND submitted_at IS NOT NULL
");
$stmt->execute([$userId]);
$totalQuestions = $stmt->fetch()['total'];

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

// Get weekly progress (last 7 days)
$weekAgo = date('Y-m-d', strtotime('-7 days'));
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM daily_entries 
    WHERE user_id = ? AND entry_date >= ? AND submitted_at IS NOT NULL
");
$stmt->execute([$userId, $weekAgo]);
$weeklyCompleted = $stmt->fetch()['count'];
$weeklyProgress = round(($weeklyCompleted / 7) * 100);

// Calculate health score (average based on completed questionnaires)
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT a.question_id) as answered,
           (SELECT COUNT(*) FROM questions WHERE active = 1) as total_questions
    FROM daily_entries de
    LEFT JOIN answers a ON de.id = a.entry_id
    WHERE de.user_id = ? AND de.entry_date >= ?
    GROUP BY de.user_id
");
$stmt->execute([$userId, $weekAgo]);
$scoreData = $stmt->fetch();

if ($scoreData && $scoreData['total_questions'] > 0) {
    $healthScore = round(($scoreData['answered'] / ($scoreData['total_questions'] * 7)) * 100);
    $healthScore = min(100, $healthScore); // Cap at 100%
} else {
    $healthScore = 0;
}

// Random Health Tips
$allTips = [
    ['icon' => '💧', 'title' => 'Start met water', 'text' => 'Drink een glas water direct na het opstaan om je stofwisseling te activeren.'],
    ['icon' => '🚶', 'title' => 'Even bewegen', 'text' => 'Een wandeling van 10 minuten kan je stemming en energie al direct verbeteren.'],
    ['icon' => '🌙', 'title' => 'Digitale rust', 'text' => 'Probeer vanavond een uur voor het slapen je schermen weg te leggen.'],
    ['icon' => '🥦', 'title' => 'Kleur op je bord', 'text' => 'Voeg vandaag één extra stuk fruit of groente toe aan je maaltijd.'],
    ['icon' => '🧠', 'title' => 'Even niets', 'text' => 'Neem 5 minuten pauze om even helemaal niets te doen. Gewoon ademen.'],
    ['icon' => '👥', 'title' => 'Verbinding', 'text' => 'Stuur een berichtje naar iemand die je waardeert. Sociaal contact is gezond!'],
    ['icon' => '☀️', 'title' => 'Daglicht', 'text' => 'Probeer in de ochtend wat daglicht te vangen voor een beter slaapritme.'],
    ['icon' => '📝', 'title' => 'Dankbaarheid', 'text' => 'Schrijf aan het einde van de dag 3 dingen op waar je dankbaar voor bent.']
];
shuffle($allTips);
$displayTips = array_slice($allTips, 0, 4);

?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jouw Dashboard - Gezondheidsmeter</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/images/icons/gm192x192.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Gezondheid">
</head>

<body class="auth-page">
    <?php include __DIR__ . '/../components/navbar.php'; ?>

    <div class="dashboard-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-header-left">
                <h1><?= $greeting ?>, <?= htmlspecialchars($username) ?></h1>
                <p class="quote-text">"<?= $randomQuote ?>"</p>
                <p class="date-display"><?= $dutchDate ?></p>
            </div>
            <div class="dashboard-header-right">
                <a href="vragen.php" class="btn-naar-app pulse-animation">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        style="display: inline-block; vertical-align: middle; margin-right: 6px;">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Hoe voel je je vandaag?
                </a>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="stats-row">
            <div class="stat-card stat-card-primary">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Je Welzijn</div>
                    <div class="stat-number"><?= $healthScore ?>%</div>
                    <div class="stat-trend stat-trend-up">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="3">
                            <polyline points="18 15 12 9 6 15" />
                        </svg>
                        Balans deze week
                    </div>
                </div>
            </div>

            <div class="stat-card stat-card-success">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <polyline points="22 4 12 14.01 9 11.01" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Jouw Week</div>
                    <div class="stat-number"><?= $weeklyProgress ?>%</div>
                    <div class="stat-progress">
                        <div class="stat-progress-bar" style="width: <?= $weeklyProgress ?>%"></div>
                    </div>
                </div>
            </div>

            <div class="stat-card stat-card-warning">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Dagen op Rij</div>
                    <div class="stat-number"><?= $currentStreak ?> dagen</div>
                    <div class="stat-subtitle">
                        <?= $currentStreak > 0 ? 'Lekker bezig! 🔥' : 'Begin vandaag je reeks!' ?></div>
                </div>
            </div>

            <div class="stat-card stat-card-info">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 16v-4M12 8h.01" />
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Jouw Reis</div>
                    <div class="stat-number"><?= $totalQuestions ?></div>
                    <div class="stat-subtitle">Metingen voltooid</div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="dashboard-grid">
            <!-- Recent Activity -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Recente Momenten</h3>
                    <a href="results.php" class="card-link">Alles bekijken →</a>
                </div>
                <div class="activity-list">
                    <?php
                    // Get recent activities
                    $stmt = $pdo->prepare("
                        SELECT de.entry_date, de.submitted_at, COUNT(a.id) as answer_count
                        FROM daily_entries de
                        LEFT JOIN answers a ON de.id = a.entry_id
                        WHERE de.user_id = ? AND de.submitted_at IS NOT NULL
                        GROUP BY de.id
                        ORDER BY de.submitted_at DESC
                        LIMIT 4
                    ");
                    $stmt->execute([$userId]);
                    $activities = $stmt->fetchAll();

                    if (count($activities) > 0):
                        foreach ($activities as $activity):
                            $timeAgo = '';
                            $submitted = new DateTime($activity['submitted_at']);
                            $now = new DateTime();
                            $diff = $now->diff($submitted);

                            if ($diff->d == 0) {
                                if ($diff->h == 0) {
                                    $timeAgo = $diff->i . ' minuten geleden';
                                } else {
                                    $timeAgo = $diff->h . ' uur geleden';
                                }
                            } else if ($diff->d == 1) {
                                $timeAgo = 'Gisteren';
                            } else {
                                $timeAgo = $diff->d . ' dagen geleden';
                            }
                            ?>
                                    <div class="activity-item">
                                        <div class="activity-icon activity-icon-success">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2">
                                                <polyline points="20 6 9 17 4 12" />
                                            </svg>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-title">Je hebt ingecheckt bij jezelf</div>
                                            <div class="activity-time"><?= $timeAgo ?></div>
                                        </div>
                                    </div>
                                <?php
                        endforeach;
                    else:
                        ?>
                            <div class="activity-item">
                                <div class="activity-content">
                                    <div class="activity-title">Nog geen metingen</div>
                                    <div class="activity-time">Zet vandaag de eerste stap!</div>
                                </div>
                            </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Direct naar</h3>
                </div>
                <div class="quick-actions">
                    <a href="vragen.php" class="action-button action-button-primary">
                        <div class="action-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path
                                    d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                            </svg>
                        </div>
                        <div class="action-content">
                            <div class="action-title">Start een check-in</div>
                            <div class="action-subtitle">Hoe voel je je nu?</div>
                        </div>
                    </a>

                    <a href="results.php" class="action-button action-button-secondary">
                        <div class="action-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <line x1="12" y1="20" x2="12" y2="10" />
                                <line x1="18" y1="20" x2="18" y2="4" />
                                <line x1="6" y1="20" x2="6" y2="16" />
                            </svg>
                        </div>
                        <div class="action-content">
                            <div class="action-title">Bekijk je inzichten</div>
                            <div class="action-subtitle">Zie hoe je groeit</div>
                        </div>
                    </a>

                    <a href="account.php" class="action-button action-button-secondary">
                        <div class="action-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        </div>
                        <div class="action-content">
                            <div class="action-title">Jouw Profiel</div>
                            <div class="action-subtitle">Instellingen & Gegevens</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Weekly Overview Chart -->
        <div class="dashboard-card dashboard-card-full">
            <div class="card-header">
                <h3>Jouw week in beeld</h3>
            </div>
            <div class="chart-container">
                <canvas id="weeklyChart"></canvas>
            </div>
        </div>

        <!-- Health Tips -->
        <div class="dashboard-card dashboard-card-full">
            <div class="card-header">
                <h3>Inspiratie voor Vandaag</h3>
            </div>
            <div class="tips-grid">
                <?php foreach ($displayTips as $tip): ?>
                        <div class="tip-card">
                            <div class="tip-icon"><?= $tip['icon'] ?></div>
                            <div class="tip-content">
                                <h4><?= $tip['title'] ?></h4>
                                <p><?= $tip['text'] ?></p>
                            </div>
                        </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../components/footer.php'; ?>

    <script src="/js/pwa.js"></script>
    <script src="/js/session-guard.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Weekly Chart with real data
        const ctx = document.getElementById('weeklyChart');
        if (ctx) {
            <?php
            // Get data for last 7 days
            $chartData = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM daily_entries 
                    WHERE user_id = ? AND entry_date = ? AND submitted_at IS NOT NULL
                ");
                $stmt->execute([$userId, $date]);
                $count = $stmt->fetch()['count'];
                $chartData[] = [
                    'date' => $date,
                    'count' => $count
                ];
            }
            ?>

            const weeklyData = <?= json_encode($chartData) ?>;
            const labels = weeklyData.map(d => {
                const date = new Date(d.date);
                return date.toLocaleDateString('nl-NL', { weekday: 'short', day: 'numeric' });
            });
            const data = weeklyData.map(d => d.count);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Check-ins',
                        data: data,
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22, 163, 74, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6
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
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
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
        }
    </script>
</body>

</html>