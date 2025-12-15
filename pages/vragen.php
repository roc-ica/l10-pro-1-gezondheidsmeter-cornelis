<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../src/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../classes/HealthScoreCalculator.php';

$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? 'Gebruiker';
$userId = $_SESSION['user_id'];

$pdo = Database::getConnection();
$today = date('Y-m-d');

// Get today's entry to check for existing answers
$stmt = $pdo->prepare("SELECT id FROM daily_entries WHERE user_id = ? AND entry_date = ?");
$stmt->execute([$userId, $today]);
$todayEntry = $stmt->fetch();
$todayEntryId = $todayEntry ? $todayEntry['id'] : null;

// Initialize session answers tracking from database
if (!isset($_SESSION['answered_questions'])) {
    $_SESSION['answered_questions'] = [];

    // Load today's answers from database
    if ($todayEntryId) {
        $stmt = $pdo->prepare("
            SELECT question_id, answer_text FROM answers 
            WHERE entry_id = ?
        ");
        $stmt->execute([$todayEntryId]);
        $existingAnswers = $stmt->fetchAll();
        foreach ($existingAnswers as $answer) {
            $_SESSION['answered_questions'][$answer['question_id']] = $answer['answer_text'];
        }
    }
}

// Handle single answer submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_answer'])) {
    $questionId = $_POST['question_id'] ?? null;
    $answer = $_POST['answer'] ?? null;

    if ($questionId && $answer !== null) {
        // Get or create today's entry
        if (!$todayEntryId) {
            $stmt = $pdo->prepare("INSERT INTO daily_entries (user_id, entry_date) VALUES (?, ?)");
            $stmt->execute([$userId, $today]);
            $todayEntryId = $pdo->lastInsertId();
        }

        // Save answer
        $stmt = $pdo->prepare("
            INSERT INTO answers (entry_id, question_id, answer_text)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE answer_text = VALUES(answer_text)
        ");

        $stmt->execute([$todayEntryId, $questionId, $answer]);

        // Mark as answered in session
        $_SESSION['answered_questions'][$questionId] = $answer;

        // Refresh page to move to next question
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get all main questions with their linked secondary questions
$stmt = $pdo->prepare("
    SELECT q.*, p.name as pillar_name, p.color as pillar_color
    FROM questions q
    JOIN pillars p ON q.pillar_id = p.id
    WHERE q.active = 1 AND q.is_main_question = 1 AND q.parent_question_id IS NULL
    ORDER BY q.id ASC
");
$stmt->execute();
$mainQuestions = $stmt->fetchAll();

// Construct a flat list of all questions (Main and Secondary)
$flatQuestions = [];
foreach ($mainQuestions as $q) {
    // Add Main Question
    $q['type'] = 'main';
    $flatQuestions[] = $q;

    // Check for Secondary Question
    $stmt = $pdo->prepare("
        SELECT * FROM questions 
        WHERE active = 1 AND parent_question_id = ? AND is_main_question = 0
        LIMIT 1
    ");
    $stmt->execute([$q['id']]);
    $secondary = $stmt->fetch();

    if ($secondary) {
        $secondary['type'] = 'secondary';
        $secondary['pillar_name'] = $q['pillar_name']; // Inherit pillar info
        $secondary['pillar_color'] = $q['pillar_color'];
        $flatQuestions[] = $secondary;
    }
}

// Find next unanswered question
$currentQuestion = null;
$currentQuestionIndex = 0;
$totalQuestions = count($flatQuestions);
$answeredCount = 0;

foreach ($flatQuestions as $idx => $q) {
    if (isset($_SESSION['answered_questions'][$q['id']])) {
        $answeredCount++;
    } elseif ($currentQuestion === null) {
        // Found the first unanswered question
        $currentQuestion = $q;
        $currentQuestionIndex = $idx;
    }
}

$progress = $totalQuestions > 0 ? ($answeredCount / $totalQuestions * 100) : 0;

// Calculate health score if all questions answered
$healthScore = null;
$allQuestionsAnswered = false;

if ($answeredCount >= $totalQuestions && $totalQuestions > 0) {
    $allQuestionsAnswered = true;

    // Mark entry as submitted if not already
    if ($todayEntryId) {
        $stmt = $pdo->prepare("
            UPDATE daily_entries 
            SET submitted_at = NOW()
            WHERE id = ? AND submitted_at IS NULL
        ");
        $stmt->execute([$todayEntryId]);
    }

    $calculator = new HealthScoreCalculator($userId, $today);
    $scoreResult = $calculator->calculateScore();
    if ($scoreResult['success']) {
        $healthScore = $scoreResult;
    }
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vandaag's vragen - Gezondheidsmeter</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/images/icons/gm192x192.png">
</head>

<body class="auth-page">
    <?php include __DIR__ . '/../components/navbar.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="dashboard-header-left">
                <h1>Vandaag's vragen</h1>
                <p>Beantwoord de vragen om je gezondheid beter te kunnen volgen.</p>
            </div>
        </div>

        <!-- Progress Card -->
        <div class="progress-card">
            <div class="progress-label">Voortgang</div>
            <div class="progress-count"><?php echo $answeredCount; ?> van <?php echo $totalQuestions; ?> vragen
                beantwoord</div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%"></div>
            </div>
        </div>

        <?php if ($allQuestionsAnswered && $healthScore): ?>
            <!-- Health Score Display -->
            <div class="question-card">
                <div class="question-badge">Score Berekend</div>
                <h2 class="question-text">Je Gezondheid Score</h2>

                <div class="health-score-display-result">
                    <div class="score-number-large"><?php echo $healthScore['score']; ?>/100</div>
                    <p class="health-score-subtitle">Gebaseerd op je antwoorden van vandaag</p>

                    <div class="pillar-breakdown">
                        <?php if (isset($healthScore['pillar_scores']) && is_array($healthScore['pillar_scores'])): ?>
                            <?php foreach ($healthScore['pillar_scores'] as $pillarId => $score): ?>
                                <div class="pillar-item">
                                    <p class="pillar-label"><?php echo htmlspecialchars($healthScore['pillar_names'][$pillarId] ?? "Pilaar $pillarId"); ?></p>
                                    <p class="pillar-score"><?php echo round($score, 1); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="question-nav">
                    <a href="../pages/results.php" class="nav-btn submit-btn">Bekijk Resultaten</a>
                </div>
            </div>

        <?php elseif ($currentQuestion): ?>
            <!-- Single Question Card -->
            <div class="question-card">
                <div class="question-badge">Vraag <?php echo $currentQuestionIndex + 1; ?> van
                    <?php echo $totalQuestions; ?>
                </div>
                <div class="question-type-badge">
                    <?php echo $currentQuestion['type'] === 'main' ? 'Hoofdvraag' : 'Detailvraag'; ?>
                </div>

                <h2 class="question-text">
                    <?php echo htmlspecialchars($currentQuestion['question_text']); ?>
                </h2>

                <form method="POST" class="question-form">
                    <input type="hidden" name="question_id" value="<?php echo $currentQuestion['id']; ?>">
                    <input type="hidden" name="save_answer" value="1">


                    <div class="answer-section">
                        <p class="answer-label">Kies een antwoord:</p>

                        <?php
                        // Determine options based on pillar
                        $options = [];
                        $gridClass = 'answer-grid-compact';
                        $unit = '';

                        $val = $currentQuestion['pillar_id'];

                        // Check for Drug question specific
                        if (!empty($currentQuestion['is_drugs_question'])) {
                            $options = ['Nee', 'Softdrugs', 'Harddrugs'];
                            $gridClass = 'answer-grid'; // Use wider grid
                        } else {
                            switch ($val) {
                                case 1: // Voeding
                                    // 0-8+ glasses
                                    $options = range(0, 8);
                                    $options[] = '9+';
                                    $unit = 'glazen';
                                    break;
                                case 2: // Beweging
                                    $options = [0, 15, 30, 45, 60, 90, 120];
                                    $unit = 'minuten';
                                    break;
                                case 3: // Slaap
                                    $options = range(4, 12);
                                    $unit = 'uur';
                                    break;
                                case 4: // Verslavingen fallback
                                    $options = ['Nee', 'Softdrugs', 'Harddrugs'];
                                    $gridClass = 'answer-grid';
                                    break;
                                case 5: // Sociaal
                                case 6: // Mentaal
                                    $options = range(1, 10);
                                    break;
                                default:
                                    $options = range(1, 10);
                            }
                        }
                        ?>

                        <div class="<?php echo $gridClass; ?>">
                            <?php foreach ($options as $opt): ?>
                                <?php
                                $displayValue = $opt;
                                $submitValue = $opt === '9+' ? 9 : $opt; // Handle 9+ special case
                                ?>
                                <button type="submit" name="answer" value="<?php echo htmlspecialchars($submitValue); ?>"
                                    class="answer-btn">
                                    <?php echo htmlspecialchars($displayValue); ?>
                                    <?php if ($unit): ?>
                                        <span class="answer-unit"><?php echo $unit; ?></span>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div class="question-nav" style="justify-content: center; border:none; padding-top:10px;">
                        <a href="../pages/home.php" class="nav-btn prev-btn"
                            style="text-decoration:none; border:none; background:none; color:#999; font-size:12px;">Stoppen
                            en later verdergaan</a>
                    </div>

                </form>
            </div>

        <?php else: ?>
            <!-- Completion Card -->
            <div class="question-card">
                <div class="question-badge">Voltooid!</div>
                <h2 class="question-text">Alle vragen beantwoord!</h2>
                <p class="answer-label">Dank je wel voor het invullen van vandaag's vragenlijst.</p>

                <div class="question-nav">
                    <a href="../pages/home.php" class="nav-btn submit-btn">Terug naar Home</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../components/footer.php'; ?>
    <script src="/js/pwa.js"></script>
    <script src="/js/session-guard.js"></script>
</body>

</html>