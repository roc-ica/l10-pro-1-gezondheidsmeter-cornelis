<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../src/views/auth/login.php');
    exit;
}

// Admins are allowed to visit but not fill out questions
$isAdmin = !empty($_SESSION['is_admin']);

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/HealthScoreCalculator.php';
require_once __DIR__ . '/../src/services/QuestionnaireService.php';

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Gebruiker';
$questionnaireService = new QuestionnaireService();

// Initialize session state on first visit
if (!isset($_SESSION['questionnaire_state'])) {
    $_SESSION['questionnaire_state'] = [
        'answers' => [],
        'current_pair_idx' => 0,
        'current_step' => 'main' 
    ];
}

// Handle AJAX Request via Service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? null;
    $result = ['success' => false];

    if ($action === 'answer_main' || $action === 'answer_secondary') {
        $result = $questionnaireService->handleAjaxRequest($userId, $_POST);
        
        if ($result['success']) {
            // Update session for immediate UI feedback
            $qId = (int)$_POST['question_id'];
            $_SESSION['questionnaire_state']['answers'][$qId] = $_POST['answer'];
            
            if ($action === 'answer_main') {
                $questionPairs = $questionnaireService->getQuestionPairs();
                $currentPairIdx = $_SESSION['questionnaire_state']['current_pair_idx'];
                $currentPair = $questionPairs[$currentPairIdx] ?? null;

                // Move to next if: answer is "Nee" OR no secondary question exists OR main question is numerical
                if ($_POST['answer'] === 'Nee' || !$currentPair['secondary'] || $currentPair['main']['input_type'] === 'number') {
                    $_SESSION['questionnaire_state']['current_pair_idx']++;
                    $_SESSION['questionnaire_state']['current_step'] = 'main';
                } else {
                    $_SESSION['questionnaire_state']['current_step'] = 'secondary';
                }
            } else {
                $_SESSION['questionnaire_state']['current_pair_idx']++;
                $_SESSION['questionnaire_state']['current_step'] = 'main';
            }
        }
    } elseif ($action === 'go_back') {
        $currentStep = $_POST['current_step'] ?? 'main';
        if ($currentStep === 'secondary') {
            $_SESSION['questionnaire_state']['current_step'] = 'main';
        } elseif ($currentStep === 'main' && $_SESSION['questionnaire_state']['current_pair_idx'] > 0) {
            $questionPairs = $questionnaireService->getQuestionPairs();
            $_SESSION['questionnaire_state']['current_pair_idx']--;
            
            $prevPair = $questionPairs[$_SESSION['questionnaire_state']['current_pair_idx']] ?? null;
            if ($prevPair && isset($prevPair['secondary']) && $prevPair['secondary'] && $prevPair['main']['input_type'] !== 'number') {
                $_SESSION['questionnaire_state']['current_step'] = 'secondary';
            } else {
                $_SESSION['questionnaire_state']['current_step'] = 'main';
            }
        }
        $result = ['success' => true];
    } elseif ($action === 'reset') {
        $result = $questionnaireService->resetTodayEntry($userId);
        unset($_SESSION['questionnaire_state']);
    }

    echo json_encode($result);
    exit;
}

// Handle Reset via GET (legacy/fallback)
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    $questionnaireService->resetTodayEntry($userId);
    unset($_SESSION['questionnaire_state']);
    header('Location: vragen.php');
    exit;
}

// Get Data for View
$questionPairs = $questionnaireService->getQuestionPairs();
$todayEntry = $questionnaireService->getTodayEntry($userId);

// Sync database answers with session context
if (!empty($todayEntry['answers'])) {
    foreach ($todayEntry['answers'] as $ans) {
        if ($ans['question_id']) $_SESSION['questionnaire_state']['answers'][$ans['question_id']] = $ans['answer_text'];
        // Sub-questions are handled via parent question logic in UI/State
    }
}

$currentPairIdx = $_SESSION['questionnaire_state']['current_pair_idx'] ?? 0;
$currentStep = $_SESSION['questionnaire_state']['current_step'] ?? 'main';
$answers = $_SESSION['questionnaire_state']['answers'] ?? [];

$currentPair = $questionPairs[$currentPairIdx] ?? null;
$totalPairs = count($questionPairs);

// Count progress
$answeredPairs = 0;
foreach ($questionPairs as $pair) {
    if (isset($answers[$pair['main']['id']])) {
        if ($answers[$pair['main']['id']] === 'Nee' || isset($todayEntry['answers'])) { // Simple check
             $answeredPairs++; // This is a rough estimation for UI
        }
    }
}
$allAnswered = ($currentPairIdx >= $totalPairs);
$progress = $totalPairs > 0 ? min(100, round(($currentPairIdx / $totalPairs) * 100)) : 0;

// Final Health Score Calculation if done
$healthScore = null;
if ($allAnswered) {
    $questionnaireService->submitQuestionnaire($userId);
    $calculator = new HealthScoreCalculator($userId, date('Y-m-d'));
    $scoreResult = $calculator->calculateScore();
    if ($scoreResult['success']) $healthScore = $scoreResult;
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
    <link rel="stylesheet" href="../assets/css/vragen.css">
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
                            <p class="pillar-label"><?php echo htmlspecialchars($healthScore['pillar_names'][$pillarId] ?? "Pilaar $pillarId"); ?></p>
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

        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; text-align: center; border: 1px solid #ffeeba;">
                👀 Bekijk-modus: Als beheerder kun je de vragen zien, maar niet beantwoorden.
            </div>
        <?php endif; ?>

        <?php if ($currentPair && $currentStep === 'main'): ?>
            <!-- Main Question -->
            <div class="question-card">
                <div class="question-badge">
                    <?php echo htmlspecialchars($currentPair['main']['pillar_name']); ?> - 
                    Vraag <?php echo $currentPairIdx + 1; ?> van <?php echo $totalPairs; ?>
                </div>
                
                <h2 class="question-text">
                    <?php echo htmlspecialchars($currentPair['main']['question_text']); ?>
                </h2>
                
                <div class="answer-section">
                    <?php if ($currentPair['main']['input_type'] === 'number'): ?>
                        <input 
                            type="number" 
                            id="mainAnswerNumber" 
                            class="form-input-number" 
                            placeholder="Voer getal in"
                            value="<?php echo isset($answers[$currentPair['main']['id']]) ? htmlspecialchars($answers[$currentPair['main']['id']]) : ''; ?>"
                            min="0"
                            <?php echo $isAdmin ? 'disabled' : ''; ?>
                        >
                    <?php else: ?>
                        <div class="button-group">
                            <button type="button" class="btn-yes <?php echo (isset($answers[$currentPair['main']['id']]) && $answers[$currentPair['main']['id']] === 'Ja') ? 'selected' : ''; ?>" onclick="answerMain('Ja', <?php echo $currentPair['main']['id']; ?>)" <?php echo $isAdmin ? 'disabled' : ''; ?>>Ja</button>
                            <button type="button" class="btn-no <?php echo (isset($answers[$currentPair['main']['id']]) && $answers[$currentPair['main']['id']] === 'Nee') ? 'selected' : ''; ?>" onclick="answerMain('Nee', <?php echo $currentPair['main']['id']; ?>)" <?php echo $isAdmin ? 'disabled' : ''; ?>>Nee</button>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="question-nav">
                    <?php if ($currentPairIdx > 0): ?>
                    <button type="button" class="nav-btn prev-btn" onclick="goPrevious('main')">← Vorige</button>
                    <?php else: ?>
                    <span></span>
                    <?php endif; ?>
                    
                    <?php if ($currentPair['main']['input_type'] === 'number'): ?>
                    <button type="button" class="nav-btn next-btn" onclick="answerMainNumerical(<?php echo $currentPair['main']['id']; ?>)" <?php echo $isAdmin ? 'disabled' : ''; ?>>Volgende →</button>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($currentPair && $currentStep === 'secondary' && $currentPair['secondary']): ?>
            <!-- Secondary Question -->
            <div class="question-card">
                <div class="question-badge">
                    <?php echo htmlspecialchars($currentPair['main']['pillar_name'] ?? ''); ?> - 
                    Vraag <?php echo $currentPairIdx + 1; ?> van <?php echo $totalPairs; ?> (deel 2)
                </div>
                
                <h2 class="question-text">
                    <?php echo htmlspecialchars($currentPair['secondary']['question_text'] ?? ''); ?>
                </h2>
                
                <div class="answer-section">
                    <?php if (isset($currentPair['secondary']['input_type']) && $currentPair['secondary']['input_type'] === 'number'): ?>
                        <input 
                            type="number" 
                            id="secondaryAnswer" 
                            class="form-input-number" 
                            placeholder="Voer getal in"
                            value="<?php echo isset($answers['sub_' . $currentPair['secondary']['id']]) ? htmlspecialchars($answers['sub_' . $currentPair['secondary']['id']]) : ''; ?>"
                            min="0"
                            <?php echo $isAdmin ? 'disabled' : ''; ?>
                        >
                    <?php elseif (isset($currentPair['secondary']['input_type']) && $currentPair['secondary']['input_type'] === 'text'): ?>
                        <input 
                            type="text" 
                            id="secondaryAnswerText" 
                            class="form-input-number" 
                            placeholder="Type je antwoord..."
                            value="<?php echo isset($answers['sub_' . $currentPair['secondary']['id']]) ? htmlspecialchars($answers['sub_' . $currentPair['secondary']['id']]) : ''; ?>"
                            <?php echo $isAdmin ? 'disabled' : ''; ?>
                        >
                    <?php endif; ?>
                </div>

                <div class="question-nav">
                    <button type="button" class="nav-btn prev-btn" onclick="goPrevious('secondary')">← Vorige</button>
                    <button type="button" class="nav-btn next-btn" onclick="answerSecondary(<?php echo $currentPair['main']['id']; ?>, <?php echo $currentPair['secondary']['id']; ?>)" <?php echo $isAdmin ? 'disabled' : ''; ?>>Volgende →</button>
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

        // Answer Main Question (Choice)
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

        // Answer Main Question (Numerical)
        async function answerMainNumerical(questionId) {
            const input = document.getElementById('mainAnswerNumber');
            const answer = input.value.trim();

            if (answer === '' || isNaN(answer)) {
                showError('Voer een geldig getal in');
                return;
            }

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
        async function answerSecondary(questionId, subQuestionId) {
            let input = document.getElementById('secondaryAnswer');
            // Check if it's the text input instead
            if (!input) input = document.getElementById('secondaryAnswerText');
            
            const answer = input.value.trim();

            console.log("answerSecondary called with questionId:", questionId, "subQuestionId:", subQuestionId, "answer:", answer);

            if (answer === '') {
                showError('Voer een antwoord in');
                return;
            }

            // Simple validation for number type only
            if (input.type === 'number' && isNaN(answer)) {
                showError('Voer een geldig getal in');
                return;
            }

            const button = event.target;
            button.disabled = true;

            const result = await sendAjax({
                action: 'answer_secondary',
                question_id: questionId,
                sub_question_id: subQuestionId,
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
