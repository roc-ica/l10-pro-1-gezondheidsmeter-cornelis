<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../src/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../classes/HealthScoreCalculator.php';

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Gebruiker';
$pdo = Database::getConnection();
$today = date('Y-m-d');

// Handle reset request
if (isset($_GET['reset']) && $_GET['reset'] == '1') {
    // 1. Get today's entry ID if exists
    $stmt = $pdo->prepare("SELECT id FROM daily_entries WHERE user_id = ? AND entry_date = ?");
    $stmt->execute([$userId, $today]);
    $entry = $stmt->fetch();
    
    if ($entry) {
        // 2. Delete existing answers
        $stmt = $pdo->prepare("DELETE FROM answers WHERE entry_id = ?");
        $stmt->execute([$entry['id']]);
        
        // 3. Reset submission status
        $stmt = $pdo->prepare("UPDATE daily_entries SET submitted_at = NULL WHERE id = ?");
        $stmt->execute([$entry['id']]);

        // 4. Also clear calculated score for today to keep data consistent
        $stmt = $pdo->prepare("DELETE FROM user_health_scores WHERE user_id = ? AND score_date = ?");
        $stmt->execute([$userId, $today]);
    }
    
    // 5. Clear session data
    unset($_SESSION['answered_questions']);
    
    // 6. Redirect to clean URL
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

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

// Handle previous question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['go_back'])) {
    $prevQuestionId = $_POST['go_back'];
    if (isset($_SESSION['answered_questions'][$prevQuestionId])) {
        unset($_SESSION['answered_questions'][$prevQuestionId]);
    }
    // Refresh page to show the previous question
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
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
    WHERE q.active = 1 AND q.question_type = 'main' AND q.parent_question_id IS NULL
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

// Find next unanswered question pair (main + secondary)
$currentQuestion = null;
$currentQuestionIndex = 0;

foreach ($mainQuestions as $idx => $q) {
    if (!isset($_SESSION['answered_questions'][$q['id']])) {
        $currentQuestion = $q;
        $currentQuestionIndex = $idx;
        break;
    }
}

$totalQuestions = count($mainQuestions);
$answeredCount = 0;
foreach ($mainQuestions as $q) {
    if (isset($_SESSION['answered_questions'][$q['id']])) {
        $answeredCount++;
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
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
            <div class="progress-count"><?php echo $answeredCount; ?> van <?php echo $totalQuestions; ?> vragensets beantwoord</div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%"></div>
            </div>
        </div>

        <!-- Error Message -->
        <div id="errorMessage" class="error-message"></div>

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
                                    <p class="pillar-label">
                                        <?php echo htmlspecialchars($healthScore['pillar_names'][$pillarId] ?? "Pilaar $pillarId"); ?>
                                    </p>
                                    <p class="pillar-score"><?php echo round($score, 1); ?></p>
                                </div>
                            <div class="pillar-item">
                                <p class="pillar-label">Pilaar <?php echo $pillarId; ?></p>
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
            <!-- Two-Part Question Card -->
            <div class="question-card">
                <div class="question-badge">Vraag <?php echo $currentQuestionIndex + 1; ?> van <?php echo $totalQuestions; ?></div>
                
                <form method="POST" class="question-form">
                    <input type="hidden" name="question_id" value="<?php echo $currentQuestion['id']; ?>">
                    <input type="hidden" name="save_answer" value="1">


                    <div class="answer-section">
                        <p class="answer-label">Kies een antwoord:</p>

                        <?php
                        // Determine options based on pillar
                        $options = [];
                        $gridClass = 'answer-grid';
                        $unit = '';

                        $val = $currentQuestion['pillar_id'];

                        // Check for Drug question specific
                        if (!empty($currentQuestion['is_drugs_question']) || $val == 4) {
                            $options = ['Nee', 'Softdrugs', 'Harddrugs'];
                        } else {
                            // Unified 4-point scale for all standard questions
                            $options = ['Nee / Laag', 'Neutraal', 'Goed', 'Zeer Goed'];
                        }
                        ?>

                        <div class="<?php echo $gridClass; ?>">
                            <?php foreach ($options as $opt): ?>
                                <?php
                                $displayValue = $opt;
                                $submitValue = $opt;
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
                    <div class="question-nav"
                        style="justify-content: space-between; border:none; padding-top:10px; display: flex;">
                        <?php if ($currentQuestionIndex > 0): ?>
                            <?php $prevQId = $flatQuestions[$currentQuestionIndex - 1]['id']; ?>
                            <button type="submit" name="go_back" value="<?php echo $prevQId; ?>"
                                class="nav-btn prev-btn">Vorige</button>
                        <?php else: ?>
                            <button type="button" class="nav-btn prev-btn" style="visibility: hidden">Vorige</button>
                        <?php endif; ?>

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
    <script>
        // AJAX Helper Function
        async function sendAjax(data) {
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams(data)
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const result = await response.json();
                return result;
            } catch (error) {
                console.error('AJAX Error:', error);
                showError('Er is een fout opgetreden. Probeer het opnieuw.');
                return { success: false };
            }
        }

        // Show error message
        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 5000);
        }

        // Answer Main Question
        async function answerMain(answer, questionId) {
            const button = event.target;
            button.disabled = true;

            const result = await sendAjax({
                action: 'answer_main',
                question_id: questionId,
                answer: answer
            });

            if (result.success) {
                setTimeout(() => {
                    location.reload();
                }, 300);
            } else {
                button.disabled = false;
                showError('Could not save answer');
            }
        }

        // Answer Secondary Question
        async function answerSecondary(questionId) {
            const input = document.getElementById('secondaryAnswer');
            const answer = input.value.trim();

            if (answer === '' || isNaN(answer)) {
                showError('Voer een geldig getal in');
                return;
            }

            const button = event.target;
            button.disabled = true;

            const result = await sendAjax({
                action: 'answer_secondary',
                question_id: questionId,
                answer: answer
            });

            if (result.success) {
                setTimeout(() => {
                    location.reload();
                }, 300);
            } else {
                button.disabled = false;
                showError('Could not save answer');
            }
        }

        // Go Previous (via AJAX)
        async function goPrevious(currentStep) {
            const button = event.target;
            button.disabled = true;

            const result = await sendAjax({
                action: 'go_back',
                current_step: currentStep
            });

            if (result.success) {
                setTimeout(() => {
                    location.reload();
                }, 300);
            } else {
                button.disabled = false;
                showError('Could not go back');
            }
        }

        // Allow only numbers in secondary answer
        const secondaryInput = document.getElementById('secondaryAnswer');
        if (secondaryInput) {
            secondaryInput.addEventListener('keydown', function(e) {
                const allowedKeys = ['Backspace', 'Tab', 'ArrowLeft', 'ArrowRight', 'Delete', 'Enter'];
                const isNumber = /[0-9]/.test(e.key);
                
                if (!isNumber && !allowedKeys.includes(e.key)) {
                    e.preventDefault();
                }
            });
        }
    </script>
</body>
</html>
