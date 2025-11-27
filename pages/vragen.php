<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../src/views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../classes/Question.php';

$username = $_SESSION['username'] ?? 'Gebruiker';

// Use Question class
$questionModel = new Question();
$questions = $questionModel->getAllActive();
$totalQuestions = count($questions);
$currentQuestion = $questionModel->getCurrentQuestionNumber($totalQuestions);
$answeredCount = $questionModel->getAnsweredCount();
$currentQuestionData = $questionModel->getQuestionForDisplay($questions, $currentQuestion);
$choices = $currentQuestionData ? $currentQuestionData['parsed_choices'] : [];
$progress = $questionModel->calculateProgress($answeredCount, $totalQuestions);

// Check if current question was already answered
$currentAnswer = null;
if ($currentQuestionData && isset($_SESSION['answered_questions'][$currentQuestionData['id']])) {
    $currentAnswer = $_SESSION['answered_questions'][$currentQuestionData['id']];
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
    
    <div class="questions-container">
        <div class="questions-header">
            <h1>Vandaag's vragen</h1>
            <p>Beantwoord de vragen om je gezondheid beter te kunnen volgen.</p>
        </div>

        <!-- Progress Card -->
        <div class="progress-card">
            <div class="progress-label">Voortgang</div>
            <div class="progress-count"><?php echo $answeredCount; ?> van <?php echo $totalQuestions; ?> beantwoord</div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%"></div>
            </div>
        </div>

        <?php if ($currentQuestionData): ?>
        <!-- Question Card -->
        <div class="question-card">
            <div class="question-badge">Vraag <?php echo $currentQuestion; ?> van <?php echo $totalQuestions; ?></div>
            <h2 class="question-text"><?php echo htmlspecialchars($currentQuestionData['question_text']); ?></h2>
            
            <div class="answer-section">
                <p class="answer-label">Selecteer je antwoord:</p>
                <div class="answer-grid">
                    <?php foreach ($choices as $choice): ?>
                    <button class="answer-btn<?php echo ($currentAnswer === $choice) ? ' selected' : ''; ?>" data-question-id="<?php echo $currentQuestionData['id']; ?>" data-answer="<?php echo htmlspecialchars($choice); ?>">
                        <?php echo htmlspecialchars($choice); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Navigation -->
            <div class="question-nav">
                <?php if ($currentQuestion > 1): ?>
                <a href="?q=<?php echo $currentQuestion - 1; ?>" class="nav-btn prev-btn">← Vorige</a>
                <?php else: ?>
                <span></span>
                <?php endif; ?>
                
                <?php if ($currentQuestion < $totalQuestions): ?>
                <a href="?q=<?php echo $currentQuestion + 1; ?>" class="nav-btn next-btn">Volgende →</a>
                <?php else: ?>
                <button class="nav-btn submit-btn">Voltooien</button>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="question-card">
            <p class="no-questions">Er zijn momenteel geen vragen beschikbaar.</p>
        </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../components/footer.php'; ?>

    <script>
    document.querySelectorAll('.answer-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const questionId = this.dataset.questionId;
            const answer = this.dataset.answer;
            
            // Visual feedback
            document.querySelectorAll('.answer-btn').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
            
            // Save answer to server
            try {
                const response = await fetch('../api/save-answer.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ question_id: questionId, answer: answer })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Update progress display
                    document.querySelector('.progress-count').textContent = 
                        data.answered_count + ' van ' + data.total_questions + ' beantwoord';
                    document.querySelector('.progress-bar-fill').style.width = data.progress + '%';
                }
            } catch (error) {
                console.error('Error saving answer:', error);
            }
        });
    });
    </script>
    <script src="/js/pwa.js"></script>
</body>
</html>