<?php

require_once __DIR__ . '/../config/database.php';

class AdminDashboardStats
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Get total active users
     */
    public function getTotalUsers(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0 AND is_active = 1");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get total questions answered across all users
     */
    public function getTotalAnswers(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM answers");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get overall average score (0-100 scale)
     * Uses user_health_scores which contains calculated overall scores
     */
    public function getAverageScore(): int
    {
        $stmt = $this->pdo->query("
            SELECT AVG(overall_score) 
            FROM user_health_scores 
            WHERE overall_score IS NOT NULL
        ");
        $avgScore = $stmt->fetchColumn();

        if ($avgScore === false || $avgScore === null) {
            return 0;
        }

        // Return as integer rounded (0-100 scale)
        return (int) round((float) $avgScore);
    }

    /**
     * Get count of active users in a specific period
     */
    public function getActiveInPeriod(string $period = 'week'): int
    {
        $interval = match($period) {
            'month' => 30,
            'year' => 365,
            default => 7
        };
        
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM daily_entries 
             WHERE entry_date >= CURDATE() - INTERVAL $interval DAY"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
    
    /**
     * Get total answers in a specific period
     */
    public function getTotalAnswersInPeriod(string $period = 'week'): int
    {
        $interval = match($period) {
            'month' => 30,
            'year' => 365,
            default => 7
        };
        
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM answers a
             INNER JOIN daily_entries de ON a.entry_id = de.id
             WHERE de.entry_date >= CURDATE() - INTERVAL $interval DAY"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
    
    /**
     * Get average score for a specific period
     */
    public function getAverageScoreInPeriod(string $period = 'week'): int
    {
        $interval = match($period) {
            'month' => 30,
            'year' => 365,
            default => 7
        };
        
        $stmt = $this->pdo->prepare("
            SELECT AVG(overall_score) 
            FROM user_health_scores 
            WHERE overall_score IS NOT NULL
              AND score_date >= CURDATE() - INTERVAL $interval DAY
        ");
        $stmt->execute();
        $avgScore = $stmt->fetchColumn();

        if ($avgScore === false || $avgScore === null) {
            return 0;
        }

        return (int) round((float) $avgScore);
    }
    
    /**
     * Backward compatibility
     */
    public function getActiveThisWeek(): int
    {
        return $this->getActiveInPeriod('week');
    }

    /**
     * Get weekly activity data for THIS week (Monday to Sunday)
     * Returns array with 7 days (Monday to Sunday)
     * Each day contains submitted and incomplete counts
     */
    public function getWeeklyActivity(): array
    {
        // Get current week's Monday
        $today = new \DateTime();
        $mondayOfWeek = (clone $today)->modify('Monday this week');
        
        $weeklyData = [];
        $dayNames = ['Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag', 'Zondag'];
        
        // Get 7 days starting from Monday
        for ($i = 0; $i < 7; $i++) {
            $currentDate = (clone $mondayOfWeek)->modify("+$i days");
            $dateString = $currentDate->format('Y-m-d');
            
            // Count submitted entries for this day
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM daily_entries 
                 WHERE entry_date = ? AND submitted_at IS NOT NULL"
            );
            $stmt->execute([$dateString]);
            $submitted = (int) $stmt->fetchColumn();
            
            // Count incomplete entries for this day
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM daily_entries 
                 WHERE entry_date = ? AND submitted_at IS NULL"
            );
            $stmt->execute([$dateString]);
            $incomplete = (int) $stmt->fetchColumn();
            
            $weeklyData[] = [
                'day' => $dayNames[$i],
                'date' => $dateString,
                'submitted' => $submitted,
                'incomplete' => $incomplete,
                'total' => $submitted + $incomplete
            ];
        }
        
        return $weeklyData;
    }

    /**
     * Get trend data for the last 14 days (daily average scores)
     * Returns array of dates with their average scores
     */
    public function getTrendData(): array
    {
        // Get data for last 14 days
        $stmt = $this->pdo->prepare(
            "SELECT 
                de.entry_date,
                AVG(a.score) as avg_score,
                COUNT(DISTINCT a.id) as answer_count
             FROM daily_entries de
             LEFT JOIN answers a ON a.entry_id = de.id
             WHERE de.entry_date >= CURDATE() - INTERVAL 14 DAY
             GROUP BY de.entry_date
             ORDER BY de.entry_date ASC"
        );
        $stmt->execute();
        
        $trendData = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $avgScore = $row['avg_score'] !== null ? (float) $row['avg_score'] : 0;
            
            $trendData[] = [
                'date' => $row['entry_date'],
                'score' => round($avgScore, 1),
                'answer_count' => (int) $row['answer_count']
            ];
        }
        
        return $trendData;
    }

    /**
     * Convert bar counts to pixel heights for visualization
     * Max bar height is around 120px, scale accordingly
     */
    public function getWeeklyActivityWithHeights(): array
    {
        $weeklyData = $this->getWeeklyActivity();
        
        // Find max value to scale properly
        $maxValue = 0;
        foreach ($weeklyData as $day) {
            if ($day['submitted'] > $maxValue) {
                $maxValue = $day['submitted'];
            }
            if ($day['incomplete'] > $maxValue) {
                $maxValue = $day['incomplete'];
            }
        }
        
        // If no data, set default max
        if ($maxValue === 0) {
            $maxValue = 1;
        }
        
        // Scale to pixels (max 120px)
        $maxPixels = 120;
        foreach ($weeklyData as &$day) {
            $day['submitted_height'] = (int) round(($day['submitted'] / $maxValue) * $maxPixels);
            $day['incomplete_height'] = (int) round(($day['incomplete'] / $maxValue) * $maxPixels);
        }
        
        return $weeklyData;
    }

    /**
     * Get trend data with SVG coordinate points for the trend chart
     * Converts scores to Y-axis coordinates for SVG rendering
     */
    public function getTrendDataWithCoordinates(): array
    {
        $trendData = $this->getTrendData();
        
        if (empty($trendData)) {
            return [];
        }
        
        // SVG dimensions
        $svgWidth = 260;
        $svgHeight = 100;
        $leftPadding = 10;
        $bottomPadding = 15;
        $topPadding = 10;
        
        $availableWidth = $svgWidth - $leftPadding - 20;
        $availableHeight = $svgHeight - $topPadding - $bottomPadding;
        
        // Find min and max scores for scaling
        $scores = array_column($trendData, 'score');
        $minScore = min($scores);
        $maxScore = max($scores);
        
        // Add some padding to the range
        if ($minScore === $maxScore) {
            $minScore = max(0, $minScore - 1);
            $maxScore = $maxScore + 1;
        }
        
        $scoreRange = $maxScore - $minScore;
        
        // Calculate points
        $pointCount = count($trendData);
        $xStep = $availableWidth / max(1, $pointCount - 1);
        
        $points = [];
        $coordinates = [];
        
        foreach ($trendData as $index => $data) {
            // Calculate X coordinate
            $x = $leftPadding + ($index * $xStep);
            
            // Calculate Y coordinate (invert because SVG Y increases downward)
            $normalizedScore = ($data['score'] - $minScore) / $scoreRange;
            $y = $svgHeight - $bottomPadding - ($normalizedScore * $availableHeight);
            
            $points[] = "$x,$y";
            $coordinates[] = [
                'x' => round($x, 2),
                'y' => round($y, 2),
                'date' => $data['date'],
                'score' => $data['score']
            ];
        }
        
        return [
            'points' => implode(' ', $points),
            'coordinates' => $coordinates,
            'minScore' => $minScore,
            'maxScore' => $maxScore
        ];
    }

    /**
     * Get admin actions from the last 7 days with admin user details
     * Returns array of admin actions with user info and formatted text
     */
    public function getRecentAdminActions(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT 
                aa.id,
                aa.admin_user_id,
                aa.action_type,
                aa.target_table,
                aa.target_id,
                aa.details,
                aa.created_at,
                u.display_name,
                u.username
             FROM admin_actions aa
             JOIN users u ON u.id = aa.admin_user_id
             WHERE aa.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             ORDER BY aa.created_at DESC
             LIMIT 50"
        );
        $stmt->execute();
        
        $actions = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $adminName = $row['display_name'] ?? $row['username'] ?? 'Onbekende Admin';
            $actionText = $this->formatActionText($row['action_type'], $row['target_table'], $row['target_id'], $row['details']);
            
            $actions[] = [
                'id' => $row['id'],
                'admin_name' => $adminName,
                'admin_id' => $row['admin_user_id'],
                'action_type' => $row['action_type'],
                'action_text' => $actionText,
                'created_at' => $row['created_at'],
                'time_ago' => $this->getTimeAgo($row['created_at'])
            ];
        }
        
        return $actions;
    }

    /**
     * Format action text based on action type and target
     */
    private function formatActionText(?string $actionType, ?string $targetTable, ?string $targetId, ?string $details): string
    {
        $actionType = strtolower($actionType ?? '');
        $targetTable = strtolower($targetTable ?? '');
        
        switch ($actionType) {
            case 'create':
                if ($targetTable === 'users') {
                    return "Heeft nieuwe gebruiker aangemaakt";
                } elseif ($targetTable === 'questions') {
                    return "Heeft nieuwe vraag aangemaakt";
                } elseif ($targetTable === 'challenges') {
                    return "Heeft nieuwe challenge aangemaakt";
                }
                return "Heeft item aangemaakt";
                
            case 'update':
                if ($targetTable === 'users') {
                    return "Heeft gebruiker #$targetId bijgewerkt";
                } elseif ($targetTable === 'questions') {
                    return "Heeft vragen bijgewerkt";
                } elseif ($targetTable === 'challenges') {
                    return "Heeft challenge bijgewerkt";
                }
                return "Heeft item bijgewerkt";
                
            case 'delete':
                if ($targetTable === 'users') {
                    return "Heeft gebruiker #$targetId verwijderd";
                } elseif ($targetTable === 'questions') {
                    return "Heeft vraag verwijderd";
                } elseif ($targetTable === 'challenges') {
                    return "Heeft challenge verwijderd";
                }
                return "Heeft item verwijderd";
                
            case 'block':
                return "Heeft gebruiker #$targetId geblokkeerd";
                
            case 'unblock':
                return "Heeft gebruiker #$targetId gedeblokkeerd";
                
            case 'activate':
                return "Heeft gebruiker #$targetId geactiveerd";
                
            case 'deactivate':
                return "Heeft gebruiker #$targetId gedeactiveerd";
                
            case 'reset':
                return "Heeft gegevens reset";
                
            case 'view':
                return "Heeft analytics bekeken";
                
            default:
                return "Heeft actie uitgevoerd";
        }
    }

    /**
     * Convert timestamp to human-readable "time ago" format
     */
    private function getTimeAgo(string $timestamp): string
    {
        $time = strtotime($timestamp);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return "nu";
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . " " . ($minutes === 1 ? "min" : "min") . " geleden";
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . " " . ($hours === 1 ? "uur" : "uur") . " geleden";
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . " " . ($days === 1 ? "dag" : "dag") . " geleden";
        } else {
            return date('d M', $time);
        }
    }
}
