<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/HealthScoreCalculator.php';

class UserHealthHistory
{
    private $pdo;
    private $userId;

    public function __construct(int $userId)
    {
        $this->pdo = Database::getConnection();
        $this->userId = $userId;
    }

    /**
     * Get today's health score
     */
    public function getTodayScore(): ?array
    {
        return $this->getScoreByDate(date('Y-m-d'));
    }

    /**
     * Get health score for specific date
     * Tries to fetch from database first, calculates if missing
     */
    public function getScoreByDate(string $date): ?array
    {
        // 1. Try to fetch existing score from table
        $stmt = $this->pdo->prepare("
            SELECT * FROM user_health_scores 
            WHERE user_id = ? AND score_date = ?
            LIMIT 1
        ");
        $stmt->execute([$this->userId, $date]);
        $existingScore = $stmt->fetch();

        if ($existingScore) {
            return [
                'success' => true,
                'score_date' => $existingScore['score_date'],
                'overall_score' => (float) $existingScore['overall_score'],
                'pillar_scores' => $existingScore['pillar_scores'], // Already JSON string or auto-decoded by PDO depending on config, but usually string
                'calculation_details' => $existingScore['calculation_details']
            ];
        }

        // 2. If not found, check if we have answers to calculate it
        $stmt = $this->pdo->prepare("
            SELECT id FROM daily_entries 
            WHERE user_id = ? AND entry_date = ? AND submitted_at IS NOT NULL
            LIMIT 1
        ");
        $stmt->execute([$this->userId, $date]);
        $entry = $stmt->fetch();

        if (!$entry) {
            return null; // No data to calculate from
        }

        // 3. Calculate and store (lazy load)
        // Instantiate calculator for THIS specific date
        $calculator = new HealthScoreCalculator($this->userId, $date);
        $result = $calculator->calculateScore();

        if (!$result['success']) {
            return null;
        }

        // Return in consistent format
        return [
            'success' => true,
            'score_date' => $date,
            'overall_score' => $result['score'],
            'pillar_scores' => json_encode($result['pillar_scores']),
            'calculation_details' => json_encode($result['calculation_details'])
        ];
    }

    /**
     * Get health scores for last N days
     */
    public function getScoresLastDays(int $days = 7): array
    {
        $startDate = date('Y-m-d', strtotime("-$days days"));

        // Fetch all available pre-calculated scores in one go for performance
        $stmt = $this->pdo->prepare("
            SELECT * FROM user_health_scores 
            WHERE user_id = ? AND score_date >= ?
            ORDER BY score_date DESC
        ");
        $stmt->execute([$this->userId, $startDate]);
        $rows = $stmt->fetchAll();

        $scores = [];
        foreach ($rows as $row) {
            $scores[] = [
                'score_date' => $row['score_date'],
                'overall_score' => (float) $row['overall_score'],
                'pillar_scores' => $row['pillar_scores'],
                'calculation_details' => $row['calculation_details']
            ];
        }

        return $scores;
    }

    /**
     * Get average health score for period
     */
    public function getAverageScore(int $days = 7): ?float
    {
        $scores = $this->getScoresLastDays($days);

        if (empty($scores)) {
            return null;
        }

        $total = 0;
        foreach ($scores as $score) {
            $total += $score['overall_score'];
        }

        return $total / count($scores);
    }

    /**
     * Get pillar scores breakdown for specific date
     */
    public function getPillarScores(string $date): ?array
    {
        $score = $this->getScoreByDate($date);

        if (!$score || empty($score['pillar_scores'])) {
            return null;
        }

        // Handle potential double-encoding or already decoded
        if (is_array($score['pillar_scores'])) {
            return $score['pillar_scores'];
        }

        return json_decode($score['pillar_scores'], true);
    }

    /**
     * Get health trend data for chart
     */
    public function getTrendData(int $days = 30): array
    {
        $scores = $this->getScoresLastDays($days);

        // Sort by date ASC for chart
        usort($scores, function ($a, $b) {
            return strtotime($a['score_date']) - strtotime($b['score_date']);
        });

        return $scores;
    }

    /**
     * Get all answers for a specific date
     */
    public function getAnswersByDate(string $date): array
    {
        $stmt = $this->pdo->prepare("
            SELECT a.*, q.pillar_id, p.name as pillar_name, p.color as pillar_color, q.question_text
            FROM answers a
            JOIN questions q ON a.question_id = q.id
            JOIN pillars p ON q.pillar_id = p.id
            JOIN daily_entries de ON a.entry_id = de.id
            WHERE de.user_id = ? AND de.entry_date = ?
            ORDER BY q.pillar_id ASC
        ");
        $stmt->execute([$this->userId, $date]);
        return $stmt->fetchAll();
    }

    /**
     * Get answers grouped by pillar for a specific date
     */
    public function getGroupedAnswers(string $date): array
    {
        $answers = $this->getAnswersByDate($date);
        $grouped = [];

        foreach ($answers as $answer) {
            $pillarId = $answer['pillar_id'];
            if (!isset($grouped[$pillarId])) {
                $grouped[$pillarId] = [
                    'name' => $answer['pillar_name'],
                    'color' => $answer['pillar_color'],
                    'answers' => []
                ];
            }
            $grouped[$pillarId]['answers'][] = $answer;
        }

        return $grouped;
    }

    /**
     * Get total number of submitted entries
     */
    public function getTotalSubmittedEntries(): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM daily_entries 
            WHERE user_id = ? AND submitted_at IS NOT NULL
        ");
        $stmt->execute([$this->userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get response rate (Submitted Days / Days since Signup)
     */
    public function getResponseRate(): int
    {
        // Get signup date
        $stmt = $this->pdo->prepare("SELECT created_at FROM users WHERE id = ?");
        $stmt->execute([$this->userId]);
        $createdAtStr = $stmt->fetchColumn();
        
        if (!$createdAtStr) return 0;

        $createdAt = new \DateTime($createdAtStr);
        $now = new \DateTime();
        $daysSinceSignup = max(1, $now->diff($createdAt)->days + 1);

        $submittedEntries = $this->getTotalSubmittedEntries();

        return (int)min(100, round(($submittedEntries / $daysSinceSignup) * 100));
    }

    /**
     * Get current streak
     * Handles the case where today is not yet done but yesterday was.
     */
    public function getStreak(): int
    {
        $stmt = $this->pdo->prepare("
            SELECT entry_date FROM daily_entries 
            WHERE user_id = ? AND submitted_at IS NOT NULL
            ORDER BY entry_date DESC
        ");
        $stmt->execute([$this->userId]);
        $allEntries = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $currentStreak = 0;
        $checkDate = new \DateTime(); // Start checking from TODAY
        
        $todayStr = (new \DateTime())->format('Y-m-d');
        $yesterdayStr = (new \DateTime('-1 day'))->format('Y-m-d');
        
        $hasToday = false;
        $hasYesterday = false;
        
        foreach ($allEntries as $entryDate) {
            if ($entryDate === $todayStr) $hasToday = true;
            if ($entryDate === $yesterdayStr) $hasYesterday = true;
        }

        // If today not done and yesterday not done, streak is 0
        if (!$hasToday && !$hasYesterday) return 0;
        
        // If today not done but yesterday is, we start counting from yesterday
        if (!$hasToday && $hasYesterday) {
            $checkDate->modify('-1 day');
        }

        foreach ($allEntries as $entryDate) {
            $entry = new \DateTime($entryDate);
            if ($entry->format('Y-m-d') === $checkDate->format('Y-m-d')) {
                $currentStreak++;
                $checkDate->modify('-1 day');
            } elseif ($entry->format('Y-m-d') < $checkDate->format('Y-m-d')) {
                // Gap found
                break;
            }
        }
        return $currentStreak;
    }
}
