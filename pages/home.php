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

// Daily Focus Content (Premium/Human curated feel)
$focusItems = [
    [
        'category' => 'Mentale Kracht',
        'title' => 'De kracht van stilte',
        'text' => 'In een wereld vol ruis is stilte een luxe. Probeer vandaag eens 5 minuten helemaal niets te doen. Geen telefoon, geen muziek, alleen jij en je gedachten. Het geeft je brein de kans om echt te resetten.'
    ],
    [
        'category' => 'Fysieke Gezondheid',
        'title' => 'Bewegen als medicijn',
        'text' => 'Je hoeft niet direct een marathon te lopen. Een korte wandeling na de lunch verlaagt je bloedsuikerspiegel en geeft je nieuwe energie voor de middag. Maak er vandaag een prioriteit van.'
    ],
    [
        'category' => 'Voeding',
        'title' => 'Eet de regenboog',
        'text' => 'Kijk eens kritisch naar je bord vanavond. Hoeveel verschillende kleuren zie je? Probeer minstens drie verschillende kleuren groenten toe te voegen voor een breder spectrum aan vitaminen.'
    ],
    [
        'category' => 'Slaaphygiëne',
        'title' => 'Licht en Ritme',
        'text' => 'Je biologische klok wordt gestuurd door licht. Probeer vanavond, een uur voor het slapen, felle lampen en schermen te vermijden. Het helpt je lichaam om op natuurlijke wijze melatonine aan te maken.'
    ],
    [
        'category' => 'Persoonlijke Groei',
        'title' => 'Dankbaarheid',
        'text' => 'Geluk zit niet in meer krijgen, maar in waarderen wat je hebt. Schrijf vanavond drie kleine dingen op die vandaag goed gingen. Het traint je brein om positiever naar de wereld te kijken.'
    ]
];
$dailyFocus = $focusItems[array_rand($focusItems)];

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
<!-- Stats Overview (Minimal/Data-Driven Design) -->
        <div class="stats-row">
            <!-- 1. Health Score (Radial Chart) -->
            <div class="stat-card" style="padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <span style="font-size: 0.85rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Welzijn</span>
                </div>
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div style="position: relative; width: 64px; height: 64px;">
                        <svg width="64" height="64" viewBox="0 0 36 36">
                            <path d="M18 2.0845
                                a 15.9155 15.9155 0 0 1 0 31.831
                                a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#e5e7eb" stroke-width="3" />
                            <path d="M18 2.0845
                                a 15.9155 15.9155 0 0 1 0 31.831
                                a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#2563eb" stroke-width="3"
                                stroke-dasharray="<?= $healthScore ?>, 100" class="radial-progress" />
                        </svg>
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 0.9rem; font-weight: 700; color: #1f2937;">
                            <?= $healthScore ?>%
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 0.8rem; color: #9ca3af;">Jouw score</div>
                        <div style="font-size: 0.75rem; color: #2563eb; font-weight: 500;">Vandaag</div>
                    </div>
                </div>
            </div>

            <!-- 2. Weekly Goal (Segmented Dots) -->
            <div class="stat-card" style="padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <span style="font-size: 0.85rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Weekdoel</span>
                </div>
                <div style="margin-bottom: 10px;">
                    <span style="font-size: 2rem; font-weight: 700; color: #1f2937;"><?= $weeklyCompleted ?></span>
                    <span style="font-size: 1rem; color: #9ca3af; font-weight: 500;">/ 7</span>
                </div>
                <div style="display: flex; gap: 6px;">
                    <?php for ($i = 0; $i < 7; $i++): ?>
                        <div style="width: 100%; height: 6px; border-radius: 4px; background-color: <?= $i < $weeklyCompleted ? '#16a34a' : '#e5e7eb' ?>;"></div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- 3. Streak (Minimal) -->
            <div class="stat-card" style="padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <span style="font-size: 0.85rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Reeks</span>
                </div>
                <div style="display: flex; align-items: baseline; gap: 5px;">
                    <span style="font-size: 2.5rem; font-weight: 800; color: #d97706; line-height: 1;"><?= $currentStreak ?></span>
                    <span style="font-size: 1rem; color: #9ca3af; font-weight: 500;">dagen</span>
                </div>
                <div style="margin-top: 5px; font-size: 0.8rem; color: #d97706; background: #fef3c7; display: inline-block; padding: 2px 8px; border-radius: 12px; font-weight: 500;">
                    Hou vol!
                </div>
            </div>

            <!-- 4. Total (Clean Counter) -->
            <div class="stat-card" style="padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <span style="font-size: 0.85rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Totaal</span>
                </div>
                <div style="margin-bottom: 5px;">
                    <span style="font-size: 2rem; font-weight: 700; color: #1f2937;"><?= $totalQuestions ?></span>
                </div>
                <div style="font-size: 0.8rem; color: #9ca3af;">Check-ins voltooid</div>
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

        <!-- Inspiration / Daily Focus -->
        <div class="dashboard-card dashboard-card-full" style="background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%); border-left: 4px solid #16a34a;">
            <div class="card-header">
                <h3>Inspiratie van Vandaag</h3>
            </div>
            <div class="daily-focus-content" style="padding: 0 20px 20px 20px;">
                <span class="focus-category" style="display: inline-block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #16a34a; letter-spacing: 0.05em; margin-bottom: 8px;"><?= htmlspecialchars($dailyFocus['category']) ?></span>
                <h4 class="focus-title" style="font-size: 1.25rem; font-weight: 700; color: #1f2937; margin: 0 0 12px 0;"><?= htmlspecialchars($dailyFocus['title']) ?></h4>
                <p class="focus-text" style="color: #4b5563; line-height: 1.6; font-size: 0.95rem; margin: 0; max-width: 800px;">
                    <?= htmlspecialchars($dailyFocus['text']) ?>
                </p>
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