<?php

require_once __DIR__ . '/../config/database.php';

class DashboardStats
{
    private const MAX_SCORE = 3;

    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function getOverview(?int $userId = null): array
    {
        $hero = $this->getHeroData($userId);
        $pillars = $this->getPillarScores($userId);

        return [
            'hero' => $hero,
            'pillars' => $pillars,
            'mini_chart' => $this->buildMiniChart($pillars),
        ];
    }

    private function getHeroData(?int $userId): array
    {
        $scorePercent = $this->getAverageScorePercent($userId);

        return [
            'score' => $scorePercent,
            'message' => $this->scoreMessage($scorePercent),
            'questions' => $this->getQuestionProgress($userId),
        ];
    }

    private function getAverageScorePercent(?int $userId): int
    {
        $params = [];
        $userFilter = '';

        if ($userId) {
            $userFilter = ' AND de.user_id = ?';
            $params[] = $userId;
        }

        $stmt = $this->pdo->prepare(
            "SELECT AVG(a.score) as avg_score
             FROM answers a
             JOIN daily_entries de ON a.entry_id = de.id
             WHERE a.score IS NOT NULL{$userFilter}"
        );
        $stmt->execute($params);

        $avgScore = $stmt->fetchColumn();

        if ($avgScore === false || $avgScore === null) {
            return 0;
        }

        return $this->toPercent((float) $avgScore);
    }

    private function scoreMessage(int $scorePercent): string
    {
        if ($scorePercent >= 80) {
            return 'Fantastisch bezig! Hou dit ritme vast.';
        }

        if ($scorePercent >= 50) {
            return 'Goed bezig! Blijf zo door gaan.';
        }

        return 'Je bent op weg. Kleine stappen maken het verschil.';
    }

    private function getQuestionProgress(?int $userId): array
    {
        $totalQuestions = (int) $this->pdo
            ->query("SELECT COUNT(*) FROM questions WHERE active = 1")
            ->fetchColumn();

        $answeredToday = 0;

        if ($userId) {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(DISTINCT a.question_id) 
                 FROM answers a
                 JOIN daily_entries de ON a.entry_id = de.id
                 WHERE de.user_id = ? AND de.entry_date = CURDATE()"
            );
            $stmt->execute([$userId]);
            $answeredToday = (int) $stmt->fetchColumn();
        }

        return [
            'answered' => $answeredToday,
            'total' => $totalQuestions,
            'remaining' => max($totalQuestions - $answeredToday, 0),
        ];
    }

    private function getPillarScores(?int $userId): array
    {
        $userFilter = $userId ? ' AND de.user_id = ?' : '';
        $params = $userId ? [$userId] : [];

        $stmt = $this->pdo->prepare(
            "SELECT p.id, p.name, p.color, AVG(a.score) AS avg_score
             FROM pillars p
             LEFT JOIN questions q ON q.pillar_id = p.id AND q.active = 1
             LEFT JOIN answers a ON a.question_id = q.id
             LEFT JOIN daily_entries de ON a.entry_id = de.id{$userFilter}
             GROUP BY p.id
             ORDER BY p.id"
        );
        $stmt->execute($params);

        $pillars = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $pillars[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'color' => $row['color'],
                'percentage' => $row['avg_score'] !== null
                    ? $this->toPercent((float) $row['avg_score'])
                    : 0,
            ];
        }

        return $pillars;
    }

    private function buildMiniChart(array $pillars): array
    {
        if (empty($pillars)) {
            return [];
        }

        $sorted = $pillars;
        usort($sorted, fn($a, $b) => $b['percentage'] <=> $a['percentage']);

        return array_slice($sorted, 0, 3);
    }

    public function getTotalCheckins(int $userId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM daily_entries WHERE user_id = ? AND submitted_at IS NOT NULL");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function getStreak(int $userId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT entry_date FROM daily_entries 
            WHERE user_id = ? AND submitted_at IS NOT NULL
            ORDER BY entry_date DESC
        ");
        $stmt->execute([$userId]);
        $allEntries = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $currentStreak = 0;
        $checkDate = new \DateTime();
        
        foreach ($allEntries as $entryDate) {
            $entry = new \DateTime($entryDate);
            if ($entry->format('Y-m-d') === $checkDate->format('Y-m-d')) {
                $currentStreak++;
                $checkDate->modify('-1 day');
            } else {
                break;
            }
        }
        return $currentStreak;
    }

    public function getWeeklyProgress(int $userId): array
    {
        $weekAgo = date('Y-m-d', strtotime('-7 days'));
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM daily_entries 
            WHERE user_id = ? AND entry_date >= ? AND submitted_at IS NOT NULL
        ");
        $stmt->execute([$userId, $weekAgo]);
        $completed = (int)$stmt->fetchColumn();
        
        return [
            'completed' => $completed,
            'total' => 7,
            'percentage' => round(($completed / 7) * 100)
        ];
    }

    public function getRecentActivity(int $userId, int $limit = 4): array
    {
        $stmt = $this->pdo->prepare("
            SELECT de.entry_date, de.submitted_at, COUNT(a.id) as answer_count
            FROM daily_entries de
            LEFT JOIN answers a ON de.id = a.entry_id
            WHERE de.user_id = ? AND de.submitted_at IS NOT NULL
            GROUP BY de.id
            ORDER BY de.submitted_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getHealthScorePercentage(int $userId): int
    {
        $weekAgo = date('Y-m-d', strtotime('-7 days'));
        $stmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT a.question_id) as answered,
                (SELECT COUNT(*) FROM questions WHERE active = 1) as total_questions
            FROM daily_entries de
            LEFT JOIN answers a ON de.id = a.entry_id
            WHERE de.user_id = ? AND de.entry_date >= ?
            GROUP BY de.user_id
        ");
        $stmt->execute([$userId, $weekAgo]);
        $scoreData = $stmt->fetch();

        if ($scoreData && $scoreData['total_questions'] > 0) {
            $healthScore = round(($scoreData['answered'] / ($scoreData['total_questions'] * 7)) * 100);
            return (int)min(100, $healthScore);
        }
        return 0;
    }

    private function toPercent(float $avgScore): int
    {
        if (self::MAX_SCORE <= 0) {
            return 0;
        }

        $percent = ($avgScore / self::MAX_SCORE) * 100;

        return (int) max(0, min(100, round($percent)));
    }

    public function getAverageScore(int $userId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT AVG(overall_score) as avg_score
            FROM user_health_scores
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $avgScore = $stmt->fetchColumn();
        return $avgScore ? (int)round($avgScore) : 0;
    }

    public function getWeeklyCheckinData(int $userId): array
    {
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM daily_entries 
                WHERE user_id = ? AND entry_date = ? AND submitted_at IS NOT NULL
            ");
            $stmt->execute([$userId, $date]);
            $count = $stmt->fetch()['count'];
            $chartData[] = [
                'date' => $date,
                'count' => $count
            ];
        }
        return $chartData;
    }
}

