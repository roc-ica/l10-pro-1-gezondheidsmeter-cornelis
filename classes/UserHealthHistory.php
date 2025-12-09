<?php

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/HealthScoreCalculator.php';

class UserHealthHistory
{
    private $pdo;
    private $userId;
    private $calculator;
    
    public function __construct(int $userId)
    {
        $this->pdo = Database::getConnection();
        $this->userId = $userId;
        $this->calculator = new HealthScoreCalculator($userId);
    }
    
    /**
     * Get today's health score
     */
    public function getTodayScore(): ?array
    {
        return $this->getScoreByDate(date('Y-m-d'));
    }
    
    /**
     * Get health score for specific date by calculating from answers
     */
    public function getScoreByDate(string $date): ?array
    {
        // Check if user has a daily entry for this date
        $stmt = $this->pdo->prepare("
            SELECT id FROM daily_entries 
            WHERE user_id = ? AND entry_date = ? AND submitted_at IS NOT NULL
            LIMIT 1
        ");
        $stmt->execute([$this->userId, $date]);
        $entry = $stmt->fetch();
        
        if (!$entry) {
            return null;
        }
        
        // Calculate score using HealthScoreCalculator
        $result = $this->calculator->calculateScore($date);
        
        if (!$result['success']) {
            return null;
        }
        
        // Format response to match expected structure
        return [
            'score_date' => $date,
            'overall_score' => $result['score'],
            'pillar_scores' => json_encode($result['pillar_scores'] ?? []),
            'calculation_details' => json_encode($result['calculation_details'] ?? [])
        ];
    }
    
    /**
     * Get health scores for last N days
     */
    public function getScoresLastDays(int $days = 7): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT entry_date FROM daily_entries 
            WHERE user_id = ? 
            AND entry_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            AND submitted_at IS NOT NULL
            ORDER BY entry_date DESC
        ");
        $stmt->execute([$this->userId, $days]);
        $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $scores = [];
        foreach ($dates as $date) {
            $score = $this->getScoreByDate($date);
            if ($score) {
                $scores[] = $score;
            }
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
        if (!$score || !$score['pillar_scores']) {
            return null;
        }
        return json_decode($score['pillar_scores'], true);
    }
    
    /**
     * Get calculation details for specific date (transparency)
     */
    public function getCalculationDetails(string $date): ?array
    {
        $score = $this->getScoreByDate($date);
        if (!$score || !$score['calculation_details']) {
            return null;
        }
        return json_decode($score['calculation_details'], true);
    }
    
    /**
     * Get health trend data for chart
     */
    public function getTrendData(int $days = 30): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT entry_date FROM daily_entries 
            WHERE user_id = ? 
            AND entry_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            AND submitted_at IS NOT NULL
            ORDER BY entry_date ASC
        ");
        $stmt->execute([$this->userId, $days]);
        $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $trendData = [];
        foreach ($dates as $date) {
            $score = $this->getScoreByDate($date);
            if ($score) {
                $trendData[] = [
                    'score_date' => $date,
                    'overall_score' => $score['overall_score']
                ];
            }
        }
        
        return $trendData;
    }
}
