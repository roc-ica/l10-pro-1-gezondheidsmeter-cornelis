<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../src/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/UserHealthHistory.php';

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Get health history helper
$history = new UserHealthHistory($userId);

// Get date from query parameter or use today
$scoreDate = $_GET['date'] ?? date('Y-m-d');

// Get pillar scores for the specified date
$pillarScores = $history->getPillarScores($scoreDate);
$overallScore = $history->getScoreByDate($scoreDate);

// Get all pillars for labels
$pdo = Database::getConnection();
$stmt = $pdo->query("SELECT id, name, description, color FROM pillars ORDER BY id ASC");
$pillars = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create pillar label map
$pillarMap = [];
foreach ($pillars as $p) {
    $pillarMap[$p['id']] = [
        'name' => $p['name'],
        'color' => $p['color'],
        'description' => $p['description']
    ];
}

// Get last 7 days of pillar scores for trend
$sevenDaysTrend = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $sevenDaysTrend[$date] = $history->getPillarScores($date);
}

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorie Scores - Gezondheidsmeter</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../components/navbar.php'; ?>

    <div class="dashboard-container">
        <div class="category-scores-header">
            <a href="results.php" class="back-button">← Terug naar resultaten</a>
        </div>

        <h1 class="category-scores-title">Categorie Scores</h1>
        <p class="category-scores-subtitle">Bekijk hoe goed je het doet in elke gezondheidskategorie</p>

        <!-- Date Selector -->
        <div class="date-selector">
            <label for="scoreDate">Score datum:</label>
            <input type="date" id="scoreDate" value="<?php echo htmlspecialchars($scoreDate); ?>" max="<?php echo date('Y-m-d'); ?>">
        </div>

        <?php if ($overallScore && $overallScore['overall_score']): ?>
            <!-- Summary Stats -->
            <div class="stats-summary">
                <div class="stat-box">
                    <div class="stat-label">Algehele score</div>
                    <div class="stat-value"><?php echo round($overallScore['overall_score']); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Categoriën</div>
                    <div class="stat-value"><?php echo count($pillarScores ?? []); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Datum</div>
                    <div class="stat-value"><?php echo date('d-m-Y', strtotime($scoreDate)); ?></div>
                </div>
            </div>

            <!-- Category Grid -->
            <div class="category-grid">
                <?php foreach ($pillars as $pillar):
                    $pillarId = $pillar['id'];
                    $score = $pillarScores[$pillarId] ?? 0;
                    $maxScore = 100;
                    $percentage = ($score / $maxScore) * 100;
                    
                    // Determine status
                    if ($score >= 80) {
                        $status = 'Uitstekend';
                        $statusClass = 'status-excellent';
                    } elseif ($score >= 60) {
                        $status = 'Goed';
                        $statusClass = 'status-good';
                    } elseif ($score >= 40) {
                        $status = 'Matig';
                        $statusClass = 'status-fair';
                    } else {
                        $status = 'Slecht';
                        $statusClass = 'status-poor';
                    }
                    
                    // Get trend for this pillar (last 7 days)
                    $trendValues = [];
                    foreach ($sevenDaysTrend as $date => $scores) {
                        $trendValues[] = $scores[$pillarId] ?? 0;
                    }
                ?>
                <div class="category-card" data-color="<?php echo htmlspecialchars($pillar['color']); ?>">
                    <div class="category-name"><?php echo htmlspecialchars($pillar['name']); ?></div>
                    <div class="category-description"><?php echo htmlspecialchars($pillar['description']); ?></div>
                    
                    <div class="score-display">
                        <div class="score-circle" style="background: <?php echo htmlspecialchars($pillar['color']); ?>; opacity: 0.85;">
                            <?php echo round($score); ?>%
                        </div>
                        <div class="score-info">
                            <div class="score-label">Score</div>
                            <div class="score-value"><?php echo round($score, 1); ?>/100</div>
                            <div class="score-status <?php echo $statusClass; ?>"><?php echo $status; ?></div>
                        </div>
                    </div>

                    <!-- 7-day trend -->
                    <div class="trend-mini">
                        <?php foreach ($trendValues as $val): ?>
                            <div class="trend-bar" style="background: <?php echo htmlspecialchars($pillar['color']); ?>; height: <?php echo max(5, $val * 0.3); ?>px;">
                                <?php echo $val > 0 ? round($val) : '-'; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="trend-label">Afgelopen 7 dagen</div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-data">
                <p class="no-data-title">Geen gegevens beschikbaar</p>
                <p>Er zijn nog geen gezondheidsscores opgeslagen voor <?php echo date('d-m-Y', strtotime($scoreDate)); ?>.</p>
                <p>Vul eerst de <a href="vragen.php" class="no-data-link">gezondheids vragenlijst</a> in.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../components/footer.php'; ?>

    <script>
        document.getElementById('scoreDate').addEventListener('change', function() {
            window.location.href = 'category-scores.php?date=' + this.value;
        });
    </script>
</body>
</html>
