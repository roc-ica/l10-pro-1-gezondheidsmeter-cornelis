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

// Handle answer submission for both parts of a question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_answer'])) {
    $mainQuestionId = $_POST['main_question_id'] ?? null;
    $secondaryQuestionId = $_POST['secondary_question_id'] ?? null;
    $mainAnswer = $_POST['main_answer'] ?? null;
    $secondaryAnswer = $_POST['secondary_answer'] ?? null;

    // Both answers are required
    if ($mainQuestionId && $secondaryQuestionId && $mainAnswer !== null && $secondaryAnswer !== null) {
        // Get or create today's entry
        if (!$todayEntryId) {
            $stmt = $pdo->prepare("INSERT INTO daily_entries (user_id, entry_date) VALUES (?, ?)");
            $stmt->execute([$userId, $today]);
            $todayEntryId = $pdo->lastInsertId();
        }

        // Save both answers
        $stmt = $pdo->prepare("
            INSERT INTO answers (entry_id, question_id, answer_text)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE answer_text = VALUES(answer_text)
        ");

        // Save main answer
        $stmt->execute([$todayEntryId, $mainQuestionId, $mainAnswer]);
        // Save secondary answer
        $stmt->execute([$todayEntryId, $secondaryQuestionId, $secondaryAnswer]);

        // Mark both as answered in session
        $_SESSION['answered_questions'][$mainQuestionId] = $mainAnswer;
        $_SESSION['answered_questions'][$secondaryQuestionId] = $secondaryAnswer;
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

// For each main question, fetch its linked secondary question
foreach ($mainQuestions as &$q) {
    $stmt = $pdo->prepare("
        SELECT * FROM questions 
        WHERE active = 1 AND parent_question_id = ? AND is_main_question = 0
        LIMIT 1
    ");
    $stmt->execute([$q['id']]);
    $q['secondary'] = $stmt->fetch();
}

// Initialize current index if not exists
if (!isset($_SESSION['current_index'])) {
    $_SESSION['current_index'] = 0;

    // Auto-advance to first unanswered if we have a fresh session (optional, but good UX)
    // But for "Back/Next" stability, strict index is better.
    // Let's stick to 0 or last saved state? 
    // If we want to resume where left off:
    foreach ($mainQuestions as $idx => $q) {
        if (!isset($_SESSION['answered_questions'][$q['id']])) {
            $_SESSION['current_index'] = $idx;
            break;
        }
    }
}

// Handle Actions (Next/Back/Restart)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'back') {
            $_SESSION['current_index'] = max(0, $_SESSION['current_index'] - 1);
            // Redirect to GET to prevent form resubmission warnings
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } elseif ($_POST['action'] === 'restart') {
            $_SESSION['current_index'] = 0;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } elseif ($_POST['action'] === 'save_answer' && isset($_POST['save_answer'])) {
            $mainQuestionId = $_POST['main_question_id'] ?? null;
            $secondaryQuestionId = $_POST['secondary_question_id'] ?? null;
            $mainAnswer = $_POST['main_answer'] ?? null;
            $secondaryAnswer = $_POST['secondary_answer'] ?? null;

            // Both answers are required
            if ($mainQuestionId && $secondaryQuestionId && $mainAnswer !== null && $secondaryAnswer !== null) {
                // Get or create today's entry
                if (!$todayEntryId) {
                    $stmt = $pdo->prepare("INSERT INTO daily_entries (user_id, entry_date) VALUES (?, ?)");
                    $stmt->execute([$userId, $today]);
                    $todayEntryId = $pdo->lastInsertId();
                }

                // Save both answers
                $stmt = $pdo->prepare("
                    INSERT INTO answers (entry_id, question_id, answer_text)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE answer_text = VALUES(answer_text)
                ");

                $stmt->execute([$todayEntryId, $mainQuestionId, $mainAnswer]);
                $stmt->execute([$todayEntryId, $secondaryQuestionId, $secondaryAnswer]);

                // Update session
                $_SESSION['answered_questions'][$mainQuestionId] = $mainAnswer;
                $_SESSION['answered_questions'][$secondaryQuestionId] = $secondaryAnswer;

                // Advance index
                $_SESSION['current_index']++;
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    }
}

// Handle GET restart (from link)
if (isset($_GET['restart'])) {
    $_SESSION['current_index'] = 0;
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$totalQuestions = count($mainQuestions);
$currentIndex = $_SESSION['current_index'];

// Ensure index is valid
if ($currentIndex > $totalQuestions) {
    $currentIndex = $totalQuestions;
}

$currentQuestion = ($currentIndex < $totalQuestions) ? $mainQuestions[$currentIndex] : null;

// Calculate progress
$progress = $totalQuestions > 0 ? ($currentIndex / $totalQuestions * 100) : 100;

// Health Score Checking
$allQuestionsAnswered = false;
$healthScore = null;

if ($currentIndex >= $totalQuestions) {
    $allQuestionsAnswered = true;

    // Mark as submitted
    if ($todayEntryId) {
        $stmt = $pdo->prepare("
            UPDATE daily_entries 
            SET submitted_at = NOW()
            WHERE id = ? AND submitted_at IS NULL
        ");
        $stmt->execute([$todayEntryId]);
    }

    // Calculate Score
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
            <div class="progress-count"><?php echo $currentIndex; ?> van <?php echo $totalQuestions; ?> vragensets</div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%"></div>
            </div>
        </div>

        <?php if ($allQuestionsAnswered): ?>
            <!-- Completion / Result Card -->
            <?php if ($healthScore): ?>
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
                                        <p class="pillar-label">Pilaar <?php echo $pillarId; ?></p>
                                        <p class="pillar-score"><?php echo round($score, 1); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="question-nav" style="display: flex; flex-direction: column; gap: 10px;">
                        <a href="../pages/results.php" class="nav-btn submit-btn">Bekijk Resultaten</a>
                        <form method="POST" style="width: 100%;">
                            <input type="hidden" name="action" value="restart">
                            <button type="submit" class="nav-btn secondary-btn"
                                style="background: #e2e8f0; color: #475569; width: 100%;">← Antwoorden Aanpassen</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="question-card">
                    <div class="question-badge">Klaar!</div>
                    <h2 class="question-text">Bedankt!</h2>
                    <p>Je antwoorden zijn opgeslagen.</p>
                    <div class="question-nav">
                        <a href="../pages/home.php" class="nav-btn submit-btn">Terug naar Home</a>
                    </div>
                </div>
            <?php endif; ?>

        <?php elseif ($currentQuestion && $currentQuestion['secondary']): ?>
            <!-- Two-Part Question Card -->
            <?php
            // Pre-fill existing answer if going back
            $existingMain = $_SESSION['answered_questions'][$currentQuestion['id']] ?? '';
            $existingSec = $_SESSION['answered_questions'][$currentQuestion['secondary']['id']] ?? '';
            ?>
            <div class="question-card">
                <div class="question-badge">Vraag <?php echo $currentIndex + 1; ?> van <?php echo $totalQuestions; ?></div>

                <form method="POST" class="question-form">
                    <input type="hidden" name="action" value="save_answer">
                    <input type="hidden" name="main_question_id" value="<?php echo $currentQuestion['id']; ?>">
                    <input type="hidden" name="secondary_question_id"
                        value="<?php echo $currentQuestion['secondary']['id']; ?>">
                    <input type="hidden" name="save_answer" value="1">

                    <!-- Main Question -->
                    <div class="question-part-main">
                        <h2 class="question-text">
                            <?php echo htmlspecialchars($currentQuestion['question_text']); ?>
                        </h2>
                        <div class="answer-section">
                            <p class="answer-label">Voer antwoord in:</p>
                            <?php if (($currentQuestion['input_type'] ?? 'number') === 'text'): ?>
                                <input type="text" name="main_answer" class="form-input-large"
                                    value="<?php echo htmlspecialchars($existingMain); ?>" placeholder="Voer tekst in" required>
                            <?php else: ?>
                                <input type="number" name="main_answer" class="form-input-large"
                                    value="<?php echo htmlspecialchars($existingMain); ?>" placeholder="Voer getal in"
                                    step="any" required>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Secondary Question -->
                    <div class="question-part-secondary">
                        <h2 class="question-text">
                            <?php echo htmlspecialchars($currentQuestion['secondary']['question_text']); ?>
                        </h2>
                        <div class="answer-section">
                            <p class="answer-label">Voer antwoord in:</p>
                            <?php if (($currentQuestion['secondary']['input_type'] ?? 'number') === 'text'): ?>
                                <input type="text" name="secondary_answer" class="form-input-large"
                                    value="<?php echo htmlspecialchars($existingSec); ?>" placeholder="Voer tekst in" required>
                            <?php else: ?>
                                <input type="number" name="secondary_answer" class="form-input-large"
                                    value="<?php echo htmlspecialchars($existingSec); ?>" placeholder="Voer getal in" step="any"
                                    required>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div class="question-nav">
                        <?php if ($currentIndex > 0): ?>
                            <button type="submit" name="action" value="back" class="nav-btn prev-btn" formnovalidate>←
                                Vorige</button>
                        <?php else: ?>
                            <span></span>
                        <?php endif; ?>

                        <button type="submit" class="nav-btn next-btn">Volgende →</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../components/footer.php'; ?>
    <script src="/js/pwa.js"></script>
    <script src="/js/session-guard.js"></script>
</body>

</html>