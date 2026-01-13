<?php

require_once __DIR__ . '/../config/database.php';

class HealthScoreCalculator
{
    private $pdo;
    private $userId;
    private $scoreDate;

    public function __construct(int $userId, ?string $scoreDate = null)
    {
        $this->pdo = Database::getConnection();
        $this->userId = $userId;
        $this->scoreDate = $scoreDate ?? date('Y-m-d');
    }

    /**
     * Calculate overall health score (0-100) for the user on a given date
     */
    public function calculateScore(): array
    {
        $user = $this->getUserProfile();
        if (!$user) {
            return ['success' => false, 'score' => 0, 'message' => 'User not found'];
        }

        $answers = $this->getDailyAnswers();
        if (empty($answers)) {
            return ['success' => false, 'score' => 0, 'message' => 'No answers found for this date'];
        }

        $pillarScores = [];
        $pillarNames = [];
        $details = [];

        // Group answers by pillar
        $answersByPillar = $this->groupAnswersByPillar($answers);

        foreach ($answersByPillar as $pillarId => $pillarAnswers) {
            $pillarScore = $this->calculatePillarScore($pillarId, $pillarAnswers, $details);
            $pillarScores[$pillarId] = $pillarScore;
            $pillarNames[$pillarId] = $this->getPillarName($pillarId);
        }

        // Calculate age adjustment
        $ageAdjustment = $this->calculateAgeAdjustment($user);

        // Sum pillar scores
        $totalPillarScore = array_sum(array_values($pillarScores));
        
        // Calculate average
        // We use count($pillarScores) to be safe against missing pillars.
        $pillarCount = count($pillarScores) > 0 ? count($pillarScores) : 1;
        
        $overallScore = $totalPillarScore / $pillarCount;

        // Apply age adjustment
        $overallScore -= abs($ageAdjustment);
        
        // Normalize to 0-100
        $overallScore = max(0, min(100, $overallScore));

        // Store in database
        $this->storeHealthScore($overallScore, $pillarScores, $details);

        return [
            'success' => true,
            'score' => round($overallScore, 2),
            'pillar_scores' => $pillarScores,
            'pillar_names' => $pillarNames,
            'calculation_details' => $details,
            'age_adjustment' => round($ageAdjustment, 2)
        ];
    }

    /**
     * Calculate score for a single pillar based on answers
     */
    private function calculatePillarScore(int $pillarId, array $answers, &$details): float
    {
        $totalScore = 0;
        $count = 0;
        $pillarDetails = [];

        foreach ($answers as $answer) {
            $qScore = $this->scoreAnswer($answer, $pillarDetails);
            $totalScore += $qScore;
            $count++;
        }

        // Calculate average score for the pillar (0-100)
        // If no questions answered in this pillar (shouldn't happen here), return 0
        $averageScore = $count > 0 ? $totalScore / $count : 0;

        // Normalize: Keep it 0-100
        $normalizedScore = max(0, min(100, $averageScore));

        $details[$pillarId] = [
            'score' => round($normalizedScore, 2),
            'items' => $pillarDetails
        ];

        return $normalizedScore;
    }

    /**
     * Score a single answer based on rules and keywords
     */
    private function scoreAnswer(array $answer, &$details): float
    {
        $questionId = $answer['question_id'];
        $answerValue = $answer['score'] ?? $answer['answer_text'];
        $subQuestionId = $answer['sub_question_id'] ?? null;

        // Get question details
        $stmt = $this->pdo->prepare("
            SELECT q.*, p.name as pillar_name 
            FROM questions q
            JOIN pillars p ON q.pillar_id = p.id
            WHERE q.id = ?
        ");
        $stmt->execute([$questionId]);
        $question = $stmt->fetch();

        if (!$question) {
            return 0;
        }

        // Check if this is a sub-question answer (has both main and sub answer)
        if ($subQuestionId) {
            // Get the main question answer to see if it was "Ja" or "Nee"
            $mainAnswer = $this->getMainQuestionAnswer($answer['entry_id'], $questionId);
            
            // If main answer is "Nee", score should be 0 (they said no to doing the activity)
            if ($mainAnswer && strtolower(trim($mainAnswer)) === 'nee') {
                $score = 0;
                $details[] = [
                    'question' => $question['question_text'] ?? 'Unknown',
                    'answer' => "Nee (ikke gjort)",
                    'score' => round($score, 2)
                ];
                return $score;
            }
            
            // Check if this is a frequency-based addiction question (Addictions pillar with numeric answer)
            // Questions about "how many times" drug/alcohol use should be inverted (higher = worse)
            if ($question['pillar_id'] == 4 && is_numeric($answerValue)) {
                return $this->scoreAddictionFrequency($answerValue, $question, $details);
            }
        }

        // Special handling for drugs/choice-based questions
        // Check if explicitly marked OR if it belongs to Addictions pillar (4)
        $isDrugs = (isset($question['is_drugs_question']) && $question['is_drugs_question']) ||
            ($question['pillar_id'] == 4 && !is_numeric($answerValue));

        if ($isDrugs && !is_numeric($answerValue)) {
            return $this->scoreDrugsAnswer($answerValue, $question, $details);
        }

        // Simple scoring: convert numeric answers to a 0-100 scale
        $score = 0;

        if (is_numeric($answerValue)) {
            $numValue = (float) $answerValue;

            // Different scoring based on pillar type
            switch ($question['pillar_id']) {
                case 1: // Voeding (Nutrition)
                    // Water intake: 8+ glasses = 100
                    $score = min(100, ($numValue / 8) * 100);
                    break;
                case 2: // Beweging (Exercise)
                    // Minutes of exercise: 30+ minutes = 100
                    $score = min(100, ($numValue / 30) * 100);
                    break;
                case 3: // Slaap (Sleep)
                    // Hours of sleep: 7-8 hours = 100
                    if ($numValue >= 7 && $numValue <= 8) {
                        $score = 100;
                    } elseif ($numValue >= 6 && $numValue < 7) {
                        $score = 80;
                    } elseif ($numValue > 8 && $numValue <= 9) {
                        $score = 90;
                    } elseif ($numValue < 6) {
                        $score = max(0, ($numValue / 6) * 60);
                    } else {
                        $score = 60;
                    }
                    break;
                case 4: // Verslavingen (Addictions) - should not reach here if numeric
                    $score = 100; // Default if not drugs
                    break;
                case 5: // Sociaal (Social)
                case 6: // Mentaal (Mental)
                    // Scale 1-10
                    $score = min(100, ($numValue / 10) * 100);
                    break;
                default:
                    $score = min(100, ($numValue / 10) * 100);
            }
        } else {
            // For non-numeric answers (yes/no, choice, etc.)
            $answerLower = strtolower(trim($answerValue));

            // Handle new textual scale
            if ($answerLower === 'nee / laag') {
                $score = 25;
            } elseif ($answerLower === 'neutraal') {
                $score = 50;
            } elseif ($answerLower === 'goed') {
                $score = 75;
            } elseif ($answerLower === 'zeer goed') {
                $score = 100;
            } elseif (in_array($answerLower, ['ja', 'yes', 'true', '1', 'veel'])) {
                $score = 50;
            } elseif (in_array($answerLower, ['nee', 'no', 'false', '0'])) {
                $score = 0;
            } elseif (in_array($answerLower, ['softdrugs', 'softdrug'])) {
                $score = 20;
            } elseif (in_array($answerLower, ['harddrugs', 'harddrug'])) {
                $score = 10;
            } else {
                $score = 50;
            }
        }

        // Cap score at 100
        $score = min(100, max(0, $score));

        $details[] = [
            'question' => $question['question_text'] ?? 'Unknown',
            'answer' => $answerValue,
            'score' => round($score, 2)
        ];

        return $score;
    }

    /**
     * Score addiction frequency questions (inverted: higher frequency = lower score)
     * E.g., "How many times a day do you use drugs?" - 0 times = 100, 5+ times = 0
     */
    private function scoreAddictionFrequency(string $answerValue, array $question, &$details): float
    {
        $numValue = (float) $answerValue;
        $score = 0;

        // Inverted scaling: higher frequency = worse health
        // 0 times = 100 (perfect)
        // 1 time = 80
        // 2-3 times = 50
        // 4+ times = 0 (very bad)
        if ($numValue == 0) {
            $score = 100;
        } elseif ($numValue == 1) {
            $score = 80;
        } elseif ($numValue <= 3) {
            $score = 50;
        } else {
            $score = 0;
        }

        $details[] = [
            'question' => $question['question_text'] ?? 'Unknown',
            'answer' => intval($numValue) . ' keer',
            'score' => round($score, 2)
        ];

        return max(0, min(100, $score));
    }

    /**
     * Get the main question answer (Ja or Nee) for a given entry and question
     */
    private function getMainQuestionAnswer(int $entryId, int $questionId): ?string
    {
        $stmt = $this->pdo->prepare("
            SELECT answer_text 
            FROM answers 
            WHERE entry_id = ? AND question_id = ? AND sub_question_id IS NULL
            LIMIT 1
        ");
        $stmt->execute([$entryId, $questionId]);
        $result = $stmt->fetch();
        return $result ? $result['answer_text'] : null;
    }

    /**
     * Special scoring for drugs question (Softdrugs vs Harddrugs)
     * Both are bad for health, but harddrugs are much worse
     */
    private function scoreDrugsAnswer(string $answerValue, array $question, &$details): float
    {
        $displayStatus = 'Geen druggebruik';

        // Normalize answer to lowercase
        $answer = strtolower(trim($answerValue));

        if (stripos($answer, 'softdrug') !== false || stripos($answer, 'marihuana') !== false) {
            // Softdrugs: Unhealthy but not 0
            $finalScore = 40;
            $displayStatus = 'Softdrugs (Marihuana)';
        } elseif (
            stripos($answer, 'harddrug') !== false || stripos($answer, 'cocaïne') !== false ||
            stripos($answer, 'heroine') !== false || stripos($answer, 'ecstasy') !== false
        ) {
            // Harddrugs: Very unhealthy
            $finalScore = 0;
            $displayStatus = 'Harddrugs (Zeer schadelijk)';
        } else {
            // No drugs or 'nee': Healthy
            $finalScore = 100;
            $displayStatus = 'Geen druggebruik';
        }

        $details[] = [
            'question' => $question['question_text'],
            'pillar_id' => $question['pillar_id'],
            'answer' => $displayStatus,
            'score' => $finalScore,
            'note' => $finalScore < 50 ? '⚠️ Druggebruik heeft ernstige invloed op gezondheid!' : ''
        ];

        return max(0, $finalScore);
    }



    /**
     * Calculate age-based adjustment
     */
    private function calculateAgeAdjustment(array $user): float
    {
        if (!$user['birthdate']) {
            return 0;
        }

        $birthDate = new DateTime($user['birthdate']);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;

        // Optimal age: 25-65
        if ($age >= 25 && $age <= 65) {
            return 0;
        } elseif ($age < 25) {
            return -2; // Young, slight boost
        } elseif ($age > 65) {
            return 5; // Elderly, adjust expectations
        }

        return 0;
    }

    /**
     * Get user profile data
     */
    private function getUserProfile(): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, birthdate, gender 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$this->userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get all answers for user on the given date
     */
    private function getDailyAnswers(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT a.*, q.pillar_id, q.question_text
            FROM answers a
            JOIN daily_entries de ON a.entry_id = de.id
            JOIN questions q ON a.question_id = q.id
            WHERE de.user_id = ? AND de.entry_date = ?
            ORDER BY a.id ASC
        ");
        $stmt->execute([$this->userId, $this->scoreDate]);
        return $stmt->fetchAll();
    }

    /**
     * Group answers by pillar
     */
    private function groupAnswersByPillar(array $answers): array
    {
        $grouped = [];

        foreach ($answers as $answer) {
            $pillarId = $answer['pillar_id'];
            if (!isset($grouped[$pillarId])) {
                $grouped[$pillarId] = [];
            }
            $grouped[$pillarId][] = $answer;
        }

        return $grouped;
    }

    /**
     * Store calculated score in database
     */
    private function storeHealthScore(float $score, array $pillarScores, array $details): void
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_health_scores 
                (user_id, score_date, overall_score, pillar_scores, calculation_details, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                overall_score = VALUES(overall_score),
                pillar_scores = VALUES(pillar_scores),
                calculation_details = VALUES(calculation_details)
            ");

            $stmt->execute([
                $this->userId,
                $this->scoreDate,
                $score,
                json_encode($pillarScores),
                json_encode($details)
            ]);
        } catch (PDOException $e) {
            // Log silently - scoring failure shouldn't break questionnaire
            error_log("Failed to store health score: " . $e->getMessage());
        }
    }


    /**
     * Get pillar name by ID
     */
    private function getPillarName(int $pillarId): string
    {
        $stmt = $this->pdo->prepare("SELECT name FROM pillars WHERE id = ?");
        $stmt->execute([$pillarId]);
        return $stmt->fetchColumn() ?: "Pilaar $pillarId";
    }
}
