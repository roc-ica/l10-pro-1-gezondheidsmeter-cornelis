<?php

require_once __DIR__ . '/../src/config/database.php';
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
}
