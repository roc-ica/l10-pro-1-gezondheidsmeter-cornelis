<?php

require_once __DIR__ . '/../src/config/database.php';

class Question
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Get all active questions with pillar info
     */
    public function getAllActive(): array
    {
        $stmt = $this->pdo->query(
            "SELECT q.*, p.name as pillar_name 
             FROM questions q 
             LEFT JOIN pillars p ON q.pillar_id = p.id 
             WHERE q.active = 1 
             ORDER BY q.id ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a single question by ID
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT q.*, p.name as pillar_name 
             FROM questions q 
             LEFT JOIN pillars p ON q.pillar_id = p.id 
             WHERE q.id = ?"
        );
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get total count of active questions
     */
    public function getTotalCount(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM questions WHERE active = 1");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get question data for display (includes parsed choices)
     */
    public function getQuestionForDisplay(array $questions, int $questionNumber): ?array
    {
        if (empty($questions) || $questionNumber < 1 || $questionNumber > count($questions)) {
            return null;
        }

        $question = $questions[$questionNumber - 1];
        $question['parsed_choices'] = $this->parseChoices($question['choices']);
        
        return $question;
    }

    /**
     * Parse choices from JSON, with default fallback
     */
    public function parseChoices(?string $choicesJson): array
    {
        $defaultChoices = ['Nee / Laag', 'Neutraal', 'Goed', 'Zeer Goed'];

        if (empty($choicesJson)) {
            return $defaultChoices;
        }

        $choices = json_decode($choicesJson, true);
        
        return is_array($choices) && !empty($choices) ? $choices : $defaultChoices;
    }

    /**
     * Calculate progress percentage
     */
    public function calculateProgress(int $answered, int $total): float
    {
        if ($total === 0) {
            return 0;
        }
        return ($answered / $total) * 100;
    }

    /**
     * Get current question number from request, bounded to valid range
     */
    public function getCurrentQuestionNumber(int $totalQuestions): int
    {
        $requested = isset($_GET['q']) ? (int) $_GET['q'] : 1;
        return max(1, min($requested, $totalQuestions));
    }

    /**
     * Get answered count from session
     */
    public function getAnsweredCount(): int
    {
        return isset($_SESSION['answered_questions']) ? count($_SESSION['answered_questions']) : 0;
    }
}
