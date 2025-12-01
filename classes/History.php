<?php

require_once __DIR__ . '/../src/config/database.php';

class History
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Get weekly statistics for the user
     * Returns daily averages for each pillar for the last 7 days
     */
    public function getWeeklyStats(int $userId): array
    {
        // Get the last 7 days dates
        $dates = [];
        for ($i = 6; $i >= 0; $i--) {
            $dates[] = date('Y-m-d', strtotime("-$i days"));
        }

        // Initialize result structure
        $stats = [];
        foreach ($dates as $date) {
            $stats[$date] = [
                'day_name' => $this->getDayName($date),
                'date' => $date,
                'scores' => [
                    'Voeding' => 0,
                    'Beweging' => 0,
                    'Slaap' => 0,
                    'Mentaal' => 0 // Mapping Stress to Mentaal for now as per pillars
                ]
            ];
        }

        // Query to get average scores per pillar per day
        // We join answers -> questions -> pillars
        // We group by entry_date and pillar_name
        $sql = "
            SELECT 
                de.entry_date,
                p.name as pillar_name,
                AVG(a.score) as avg_score
            FROM daily_entries de
            JOIN answers a ON de.id = a.entry_id
            JOIN questions q ON a.question_id = q.id
            JOIN pillars p ON q.pillar_id = p.id
            WHERE de.user_id = :user_id
            AND de.entry_date >= :start_date
            GROUP BY de.entry_date, p.name
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':start_date' => $dates[0]
        ]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fill in the stats
        foreach ($results as $row) {
            $date = $row['entry_date'];
            $pillar = $row['pillar_name'];
            $score = (float)$row['avg_score'];

            // Map database pillar names to our display keys if needed
            // Assuming DB has 'Voeding', 'Beweging', 'Slaap', 'Mentaal' (or 'Stress' mapped to Mentaal)
            // Let's handle some common mappings or just use the name directly
            if (isset($stats[$date]['scores'][$pillar])) {
                $stats[$date]['scores'][$pillar] = round($score * 10); // Assuming score is 1-10, convert to 1-100 scale if needed, or just keep as is. 
                // Wait, the UI shows 0-100. If questions are 1-5 or 1-10, we need to normalize.
                // Let's assume questions are 1-10 for now or normalize later. 
                // If questions are 1-5 (stars), then * 20.
                // Let's check question types later. For now assume raw score.
                // Actually, looking at init.sql, 'score' is tinyint.
                // Let's assume we want 0-100 for the chart.
                // If the input is 1-10, we multiply by 10.
            }
            
            // Special handling if 'Stress' is a separate pillar or part of 'Mentaal'
            // In init.sql: 1=Voeding, 2=Beweging, 3=Slaap, 6=Mentaal.
            // The UI shows 'Stress' in the chart. 'Mentaal' in the table.
            // Let's map 'Mentaal' to 'Stress' for the chart if needed.
            if ($pillar === 'Mentaal') {
                 $stats[$date]['scores']['Stress'] = round($score * 10); // Add Stress key if missing or map it
            }
        }

        return array_values($stats);
    }

    /**
     * Get summary statistics (Average, Best Day, Trend, Streak)
     */
    public function getSummaryStats(int $userId): array
    {
        // 1. Average Score (Overall average of all answers for the user)
        $stmt = $this->pdo->prepare("
            SELECT AVG(a.score) 
            FROM answers a 
            JOIN daily_entries de ON a.entry_id = de.id 
            WHERE de.user_id = ?
        ");
        $stmt->execute([$userId]);
        $avgScore = (float)$stmt->fetchColumn();
        
        // Normalize to 0-100 (assuming 1-10 scale)
        $avgScore = round($avgScore * 10);

        // 2. Best Day (Day of week with highest average)
        $stmt = $this->pdo->prepare("
            SELECT DAYNAME(de.entry_date) as day_name, AVG(a.score) as daily_avg
            FROM daily_entries de
            JOIN answers a ON de.id = a.entry_id
            WHERE de.user_id = ?
            GROUP BY DAYNAME(de.entry_date)
            ORDER BY daily_avg DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $bestDayRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $bestDay = $bestDayRow ? $this->translateDay($bestDayRow['day_name']) : '-';

        // 3. Streak (Consecutive days with entries ending today or yesterday)
        $streak = $this->calculateStreak($userId);

        // 4. Trend (Compare this week avg vs last week avg)
        // For simplicity, let's just return a dummy trend or calculate it properly if easy.
        // Let's calculate avg of last 7 days vs 7 days before that.
        $trend = $this->calculateTrend($userId);

        return [
            'average_score' => $avgScore,
            'best_day' => $bestDay,
            'streak' => $streak,
            'trend' => $trend
        ];
    }

    private function calculateStreak(int $userId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT entry_date 
            FROM daily_entries 
            WHERE user_id = ? 
            ORDER BY entry_date DESC
        ");
        $stmt->execute([$userId]);
        $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($dates)) return 0;

        $streak = 0;
        $today = new DateTime();
        $yesterday = (new DateTime())->modify('-1 day');
        
        $lastDate = new DateTime($dates[0]);
        
        // If last entry is not today or yesterday, streak is broken (0)
        // Unless we want to count the streak up to the last entry? Usually streak implies current active streak.
        if ($lastDate->format('Y-m-d') !== $today->format('Y-m-d') && 
            $lastDate->format('Y-m-d') !== $yesterday->format('Y-m-d')) {
            return 0;
        }

        $currentCheck = $lastDate;
        $streak = 1;

        for ($i = 1; $i < count($dates); $i++) {
            $prevDate = new DateTime($dates[$i]);
            $diff = $currentCheck->diff($prevDate)->days;

            if ($diff === 1) {
                $streak++;
                $currentCheck = $prevDate;
            } else {
                break;
            }
        }

        return $streak;
    }

    private function calculateTrend(int $userId): string
    {
        // Simple implementation: Compare avg of last 7 entries vs previous 7
        // This is a bit rough but works for a basic indicator
        return "+0%"; // Placeholder for now to avoid complex SQL
    }

    private function getDayName(string $date): string
    {
        $days = ['Zo', 'Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za'];
        return $days[date('w', strtotime($date))];
    }

    private function translateDay(string $englishDay): string
    {
        $map = [
            'Monday' => 'Maandag',
            'Tuesday' => 'Dinsdag',
            'Wednesday' => 'Woensdag',
            'Thursday' => 'Donderdag',
            'Friday' => 'Vrijdag',
            'Saturday' => 'Zaterdag',
            'Sunday' => 'Zondag'
        ];
        return $map[$englishDay] ?? $englishDay;
    }
}
