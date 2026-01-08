<?php

require_once __DIR__ . '/../config/database.php';

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
                    'Stress' => 0, // Mentaal mapped to Stress
                    'Mentaal' => 0
                ]
            ];
        }

        // Query user_health_scores for the last 7 days
        $sql = "
            SELECT score_date, pillar_scores
            FROM user_health_scores
            WHERE user_id = :user_id
            AND score_date BETWEEN :start_date AND :end_date
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':start_date' => $dates[0],
            ':end_date' => end($dates)
        ]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fill in the stats
        foreach ($results as $row) {
            $date = $row['score_date'];
            $pillarScores = json_decode($row['pillar_scores'], true);

            if (isset($stats[$date]) && is_array($pillarScores)) {
                // Map pillar IDs to names
                // 1=Voeding, 2=Beweging, 3=Slaap, 6=Mentaal
                // Note: The IDs are keys in the JSON object
                
                if (isset($pillarScores[1])) $stats[$date]['scores']['Voeding'] = $pillarScores[1];
                if (isset($pillarScores[2])) $stats[$date]['scores']['Beweging'] = $pillarScores[2];
                if (isset($pillarScores[3])) $stats[$date]['scores']['Slaap'] = $pillarScores[3];
                if (isset($pillarScores[6])) {
                    $stats[$date]['scores']['Mentaal'] = $pillarScores[6];
                    $stats[$date]['scores']['Stress'] = $pillarScores[6]; // Map Mentaal to Stress for chart
                }
            }
        }

        return array_values($stats);
    }

    /**
     * Get summary statistics (Average, Best Day, Trend, Streak)
     */
    public function getSummaryStats(int $userId): array
    {
        // 1. Average Score (Overall average of calculated scores)
        $stmt = $this->pdo->prepare("
            SELECT AVG(overall_score) 
            FROM user_health_scores 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $avgScore = round((float)$stmt->fetchColumn());

        // 2. Best Day (Day of week with highest average)
        $stmt = $this->pdo->prepare("
            SELECT DAYNAME(score_date) as day_name, AVG(overall_score) as daily_avg
            FROM user_health_scores
            WHERE user_id = ?
            GROUP BY DAYNAME(score_date)
            ORDER BY daily_avg DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $bestDayRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $bestDay = $bestDayRow ? $this->translateDay($bestDayRow['day_name']) : '-';

        // 3. Streak
        $streak = $this->calculateStreak($userId);

        // 4. Trend (Compare avg of last 7 days vs previous 7 days)
        $trend = $this->calculateTrend($userId);

        return [
            'average_score' => $avgScore . '%', // Add % sign
            'best_day' => $bestDay,
            'streak' => $streak,
            'trend' => $trend
        ];
    }

    private function calculateStreak(int $userId): int
    {
        // Use daily_entries for streak as it represents "checking in" even if score wasn't calculated?
        // Actually, consistency usually implies submission.
        $stmt = $this->pdo->prepare("
            SELECT entry_date 
            FROM daily_entries 
            WHERE user_id = ? AND submitted_at IS NOT NULL
            ORDER BY entry_date DESC
        ");
        $stmt->execute([$userId]);
        $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($dates)) return 0;

        $streak = 0;
        $today = new DateTime();
        $checkDate = new DateTime();
        
        // Loop through dates to find consecutive sequence
        foreach ($dates as $dateStr) {
            $date = new DateTime($dateStr);
            
            // If the date matches our check date, increment streak and move check date back
            if ($date->format('Y-m-d') === $checkDate->format('Y-m-d')) {
                $streak++;
                $checkDate->modify('-1 day');
            } 
            // If we haven't started a streak yet (streak 0), we allow starting from yesterday
            elseif ($streak === 0 && $date->format('Y-m-d') === (new DateTime('-1 day'))->format('Y-m-d')) {
                $streak++;
                $checkDate = new DateTime('-2 days'); // Next check should be day before yesterday
            }
            // If gap found after streak started, stop
            elseif ($streak > 0) {
                break;
            }
        }

        return $streak;
    }

    private function calculateTrend(int $userId): string
    {
        $today = date('Y-m-d');
        $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
        $fourteenDaysAgo = date('Y-m-d', strtotime('-14 days'));

        // Cmd for this week avg
        $stmt = $this->pdo->prepare("
            SELECT AVG(overall_score) 
            FROM user_health_scores 
            WHERE user_id = ? AND score_date > ? AND score_date <= ?
        ");
        $stmt->execute([$userId, $sevenDaysAgo, $today]);
        $thisWeekAvg = (float)$stmt->fetchColumn();

        // Cmd for last week avg
        $stmt->execute([$userId, $fourteenDaysAgo, $sevenDaysAgo]);
        $lastWeekAvg = (float)$stmt->fetchColumn();

        if ($lastWeekAvg == 0) {
            return $thisWeekAvg > 0 ? "+100%" : "0%";
        }

        $diff = $thisWeekAvg - $lastWeekAvg;
        $percent = ($diff / $lastWeekAvg) * 100;
        
        $sign = $percent >= 0 ? '+' : '';
        return $sign . round($percent) . '%';
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
