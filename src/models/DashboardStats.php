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

    private function toPercent(float $avgScore): int
    {
        if (self::MAX_SCORE <= 0) {
            return 0;
        }

        $percent = ($avgScore / self::MAX_SCORE) * 100;

        return (int) max(0, min(100, round($percent)));
    }
}

