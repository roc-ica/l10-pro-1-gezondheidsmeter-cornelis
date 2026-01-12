<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../src/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/HealthScoreCalculator.php';
require_once __DIR__ . '/../src/services/QuestionnaireService.php';

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Gebruiker';
$pdo = Database::getConnection();
$today = date('Y-m-d');

// Initialize session state on first visit
if (!isset($_SESSION['questionnaire_state'])) {
    $_SESSION['questionnaire_state'] = [
        'answers' => [],
        'current_pair_idx' => 0,
        'current_step' => 'main' // 'main' or 'secondary'
    ];
}

// Handle POST requests with QuestionnaireService
$questionnaireService = new QuestionnaireService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is an AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    if ($isAjax) {
        // Return JSON for AJAX
        header('Content-Type: application/json');
        
        $action = $_POST['action'] ?? null;
        
        // ACTION: Save answer using service
        if ($action === 'answer_main' || $action === 'answer_secondary') {
            $questionId = (int)($_POST['question_id'] ?? 0);
            $answer = $_POST['answer'] ?? null;
            
            error_log("DEBUG: AJAX - Attempting to save answer - QuestionID: $questionId, Answer: $answer");
            
            if ($questionId && $answer !== null) {
                try {
                    $result = $questionnaireService->saveAnswer($userId, $questionId, $answer);
                    error_log("DEBUG: Service result: " . json_encode($result));
                    
                    // Update session state if successful
                    if ($result['success']) {
                        if (!isset($_SESSION['questionnaire_state']['answers'])) {
                            $_SESSION['questionnaire_state']['answers'] = [];
                        }
                        $_SESSION['questionnaire_state']['answers'][$questionId] = $answer;
                        
                        // Update progress state
                        if ($action === 'answer_main') {
                            $_SESSION['questionnaire_state']['current_step'] = 'secondary';
                        } elseif ($action === 'answer_secondary') {
                            $_SESSION['questionnaire_state']['current_pair_idx']++;
                            $_SESSION['questionnaire_state']['current_step'] = 'main';
                        }
                        error_log("DEBUG: Session state updated");
                    }
                    
                    echo json_encode($result);
                    exit;
                } catch (Exception $e) {
                    error_log("DEBUG: Exception caught: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
                    exit;
                }
            }
            error_log("DEBUG: Invalid parameters - QuestionID: $questionId, Answer: $answer");
            echo json_encode(['success' => false, 'message' => 'Ongeldig antwoord']);
            exit;
        }
        
        // ACTION: Submit questionnaire
        if ($action === 'submit_questionnaire') {
            $result = $questionnaireService->submitQuestionnaire($userId);
            if ($result['success']) {
                unset($_SESSION['questionnaire_state']);
            }
            echo json_encode($result);
            exit;
        }
        
        // ACTION: Go back
        if ($action === 'go_back') {
            $currentStep = $_POST['current_step'] ?? 'main';
            
            if ($currentStep === 'secondary') {
                $_SESSION['questionnaire_state']['current_step'] = 'main';
            } elseif ($currentStep === 'main' && $_SESSION['questionnaire_state']['current_pair_idx'] > 0) {
                $_SESSION['questionnaire_state']['current_pair_idx']--;
                $_SESSION['questionnaire_state']['current_step'] = 'secondary';
            }
            
            echo json_encode(['success' => true, 'message' => 'Teruggegaan naar vorige vraag']);
            exit;
        }
        
        // ACTION: Reset today
        if ($action === 'reset') {
            $result = $questionnaireService->resetTodayEntry($userId);
            $_SESSION['questionnaire_state'] = [
                'answers' => [],
                'current_pair_idx' => 0,
                'current_step' => 'main'
            ];
            echo json_encode($result);
            exit;
        }
    }
}

// Get or create today's entry
$stmt = $pdo->prepare("SELECT id FROM daily_entries WHERE user_id = ? AND entry_date = ?");
$stmt->execute([$userId, $today]);
$todayEntry = $stmt->fetch();
$todayEntryId = $todayEntry ? $todayEntry['id'] : null;

// Load today's existing answers from database
if ($todayEntryId && empty($_SESSION['questionnaire_state']['answers'])) {
    $stmt = $pdo->prepare("SELECT question_id, answer_text FROM answers WHERE entry_id = ?");
    $stmt->execute([$todayEntryId]);
    $existingAnswers = $stmt->fetchAll();
    foreach ($existingAnswers as $ans) {
        $_SESSION['questionnaire_state']['answers'][$ans['question_id']] = $ans['answer_text'];
    }
}

// Fetch all question pairs (main + secondary)
$stmt = $pdo->prepare("
    SELECT q.*, p.name as pillar_name, p.color as pillar_color
    FROM questions q
    JOIN pillars p ON q.pillar_id = p.id
    WHERE q.active = 1 AND q.is_main_question = 1 AND q.parent_question_id IS NULL
    ORDER BY q.id ASC
");
$stmt->execute();
$mainQuestions = $stmt->fetchAll();

// Build question pairs
$questionPairs = [];
foreach ($mainQuestions as $main) {
    $stmt = $pdo->prepare("
        SELECT * FROM questions 
        WHERE active = 1 AND parent_question_id = ? AND is_main_question = 0
        LIMIT 1
    ");
    $stmt->execute([$main['id']]);
    $secondary = $stmt->fetch();
    
    if ($secondary) {
        $questionPairs[] = [
            'main' => $main,
            'secondary' => $secondary
        ];
    }
}

// Calculate current display state
$currentPairIdx = $_SESSION['questionnaire_state']['current_pair_idx'] ?? 0;
$currentStep = $_SESSION['questionnaire_state']['current_step'] ?? 'main';
$answers = $_SESSION['questionnaire_state']['answers'] ?? [];

$currentPair = isset($questionPairs[$currentPairIdx]) ? $questionPairs[$currentPairIdx] : null;
$totalPairs = count($questionPairs);

// Count answered pairs
$answeredPairs = 0;
foreach ($questionPairs as $pair) {
    if (isset($answers[$pair['main']['id']]) && isset($answers[$pair['secondary']['id']])) {
        $answeredPairs++;
    }
}

// Check if all answered
$allAnswered = ($answeredPairs === $totalPairs && $totalPairs > 0);
$progress = $totalPairs > 0 ? ($answeredPairs / $totalPairs * 100) : 0;

// Calculate health score if all answered
$healthScore = null;
if ($allAnswered && $todayEntryId) {
    $stmt = $pdo->prepare("UPDATE daily_entries SET submitted_at = NOW() WHERE id = ? AND submitted_at IS NULL");
    $stmt->execute([$todayEntryId]);
    
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
    <style>
        /* Question Card */
        .question-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .question-badge {
            display: inline-block;
            background: #f0f0f0;
            color: #666;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .question-text {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin: 20px 0;
            line-height: 1.4;
        }

        .answer-section {
            margin: 30px 0;
        }

        /* Button Styling */
        .button-group {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin: 20px 0;
        }

        .btn-yes,
        .btn-no {
            flex: 1;
            min-width: 140px;
            padding: 16px 24px;
            font-size: 16px;
            font-weight: 600;
            border: 2px solid #ddd;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-yes {
            color: #2ecc71;
            border-color: #2ecc71;
        }

        .btn-yes:hover {
            background: #e8f8f0;
            border-color: #27ae60;
        }

        .btn-yes.selected {
            background: #2ecc71;
            color: white;
            border-color: #27ae60;
        }

        .btn-no {
            color: #e74c3c;
            border-color: #e74c3c;
        }

        .btn-no:hover {
            background: #fae8e6;
            border-color: #c0392b;
        }

        .btn-no.selected {
            background: #e74c3c;
            color: white;
            border-color: #c0392b;
        }

        /* Input Field */
        .form-input-number {
            width: 100%;
            padding: 14px 16px;
            font-size: 16px;
            border: 2px solid #ddd;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .form-input-number:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        /* Remove number input spinners */
        .form-input-number::-webkit-outer-spin-button,
        .form-input-number::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .form-input-number[type=number] {
            -moz-appearance: textfield;
        }

        /* Navigation */
        .question-nav {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-top: 30px;
        }

        .nav-btn {
            flex: 1;
            padding: 14px 24px;
            font-size: 16px;
            font-weight: 600;
            border: 2px solid #3498db;
            border-radius: 10px;
            background: white;
            color: #3498db;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .nav-btn:hover {
            background: #3498db;
            color: white;
        }

        .nav-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .next-btn {
            border-color: #2ecc71;
            color: #2ecc71;
        }

        .next-btn:hover {
            background: #2ecc71;
            color: white;
        }

        .prev-btn {
            border-color: #95a5a6;
            color: #95a5a6;
        }

        .prev-btn:hover {
            background: #95a5a6;
            color: white;
        }

        /* Progress */
        .progress-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .progress-label {
            font-size: 12px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .progress-count {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
        }

        .progress-bar-container {
            width: 100%;
            height: 8px;
            background: #ecf0f1;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            transition: width 0.3s ease;
        }

        /* Error Message */
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
        }

        /* Completion Card */
        .completion-card {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3);
        }

        .completion-card h2 {
            font-size: 32px;
            margin: 20px 0;
        }

        .completion-card p {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 30px;
        }

        .submit-btn {
            border-color: white;
            color: white;
            background: transparent;
        }

        .submit-btn:hover {
            background: white;
            color: #2ecc71;
        }

        /* Health Score Display */
        .health-score-display-result {
            text-align: center;
            padding: 30px 0;
        }

        .score-number-large {
            font-size: 64px;
            font-weight: 700;
            color: #2ecc71;
            margin: 20px 0;
        }

        .health-score-subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
        }

        .pillar-breakdown {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 16px;
            margin: 30px 0;
        }

        .pillar-item {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 10px;
            text-align: center;
        }

        .pillar-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
        }

        .pillar-score {
            font-size: 24px;
            font-weight: 700;
            color: #3498db;
        }
    </style>
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
            <div class="progress-count"><?php echo $answeredPairs; ?> van <?php echo $totalPairs; ?> vragensets beantwoord</div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%"></div>
            </div>
        </div>

        <!-- Error Message -->
        <div id="errorMessage" class="error-message"></div>

        <?php if ($allAnswered && $healthScore): ?>
            <!-- All Answered - Show Score -->
            <div class="question-card">
                <div class="question-badge">Score Berekend</div>
                <h2 class="question-text">Je Gezondheid Score</h2>
                
                <div class="health-score-display-result">
                    <div class="score-number-large"><?php echo $healthScore['score']; ?>/100</div>
                    <p class="health-score-subtitle">Gebaseerd op je antwoorden van vandaag</p>
                    
                    <?php if (isset($healthScore['pillar_scores']) && is_array($healthScore['pillar_scores'])): ?>
                    <div class="pillar-breakdown">
                        <?php foreach ($healthScore['pillar_scores'] as $pillarId => $score): ?>
                        <div class="pillar-item">
                            <p class="pillar-label">Pilaar <?php echo $pillarId; ?></p>
                            <p class="pillar-score"><?php echo round($score, 1); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="question-nav">
                    <a href="../pages/results.php" class="nav-btn submit-btn">Bekijk Resultaten</a>
                </div>
            </div>

        <?php elseif ($currentPair && $currentStep === 'main'): ?>
            <!-- Main Question -->
            <div class="question-card">
                <div class="question-badge">Vraag <?php echo $currentPairIdx + 1; ?> van <?php echo $totalPairs; ?></div>
                
                <h2 class="question-text">
                    <?php echo htmlspecialchars($currentPair['main']['question_text']); ?>
                </h2>
                
                <div class="answer-section">
                    <div class="button-group">
                        <button type="button" class="btn-yes <?php echo (isset($answers[$currentPair['main']['id']]) && $answers[$currentPair['main']['id']] === 'Ja') ? 'selected' : ''; ?>" onclick="answerMain('Ja', <?php echo $currentPair['main']['id']; ?>)">Ja</button>
                        <button type="button" class="btn-no <?php echo (isset($answers[$currentPair['main']['id']]) && $answers[$currentPair['main']['id']] === 'Nee') ? 'selected' : ''; ?>" onclick="answerMain('Nee', <?php echo $currentPair['main']['id']; ?>)">Nee</button>
                    </div>
                </div>

                <div class="question-nav">
                    <?php if ($currentPairIdx > 0): ?>
                    <button type="button" class="nav-btn prev-btn" onclick="goPrevious('main')">← Vorige</button>
                    <?php else: ?>
                    <span></span>
                    <?php endif; ?>
                    <span></span>
                </div>
            </div>

        <?php elseif ($currentPair && $currentStep === 'secondary'): ?>
            <!-- Secondary Question -->
            <div class="question-card">
                <div class="question-badge">Vraag <?php echo $currentPairIdx + 1; ?> van <?php echo $totalPairs; ?> (deel 2)</div>
                
                <h2 class="question-text">
                    <?php echo htmlspecialchars($currentPair['secondary']['question_text']); ?>
                </h2>
                
                <div class="answer-section">
                    <input 
                        type="number" 
                        id="secondaryAnswer" 
                        class="form-input-number" 
                        placeholder="Voer getal in"
                        value="<?php echo isset($answers[$currentPair['secondary']['id']]) ? htmlspecialchars($answers[$currentPair['secondary']['id']]) : ''; ?>"
                        min="0"
                    >
                </div>

                <div class="question-nav">
                    <button type="button" class="nav-btn prev-btn" onclick="goPrevious('secondary')">← Vorige</button>
                    <button type="button" class="nav-btn next-btn" onclick="answerSecondary(<?php echo $currentPair['secondary']['id']; ?>)">Volgende →</button>
                </div>
            </div>

        <?php else: ?>
            <!-- Completion -->
            <div class="completion-card">
                <h2>Alle vragen beantwoord!</h2>
                <p>Dank je wel voor het invullen van vandaag's vragenlijst.</p>
                
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
                        'X-Requested-With': 'XMLHttpRequest'  // IMPORTANT: Tell PHP this is AJAX
                    },
                    body: new URLSearchParams(data)
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }

                const text = await response.text();
                console.log("Response text:", text);
                
                const result = JSON.parse(text);
                console.log("Parsed result:", result);
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
            if (errorDiv) {
                errorDiv.textContent = message;
                errorDiv.style.display = 'block';
                setTimeout(() => {
                    errorDiv.style.display = 'none';
                }, 5000);
            }
        }

        // Answer Main Question
        async function answerMain(answer, questionId) {
            console.log("answerMain called with answer:", answer, "questionId:", questionId);
            const button = event.target;
            button.disabled = true;

            const result = await sendAjax({
                action: 'answer_main',
                question_id: questionId,
                answer: answer
            });

            console.log("answerMain result:", result);
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

            console.log("answerSecondary called with questionId:", questionId, "answer:", answer);

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

            console.log("answerSecondary result:", result);
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
