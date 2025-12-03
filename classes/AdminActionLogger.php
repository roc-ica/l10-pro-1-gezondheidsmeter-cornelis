<?php

require_once __DIR__ . '/../src/config/database.php';

class AdminActionLogger
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Log an admin action
     * 
     * @param int $adminUserId The ID of the admin performing the action
     * @param string $actionType The type of action (create, update, delete, block, unblock, activate, deactivate, reset, view)
     * @param string|null $targetTable The table being affected (users, questions, challenges, etc.)
     * @param string|null $targetId The ID of the target being affected
     * @param array|null $details Additional details about the action (stored as JSON)
     */
    public function logAction(
        int $adminUserId,
        string $actionType,
        ?string $targetTable = null,
        ?string $targetId = null,
        ?array $details = null
    ): bool {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO admin_actions (admin_user_id, action_type, target_table, target_id, details, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );

            $detailsJson = $details ? json_encode($details) : null;

            return $stmt->execute([
                $adminUserId,
                $actionType,
                $targetTable,
                $targetId,
                $detailsJson
            ]);
        } catch (\Exception $e) {
            // Log silently to not break admin functionality
            error_log("AdminActionLogger error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log a user creation
     */
    public function logUserCreate(int $adminUserId, int $newUserId, array $userData): bool
    {
        return $this->logAction(
            $adminUserId,
            'create',
            'users',
            (string)$newUserId,
            [
                'username' => $userData['username'] ?? null,
                'email' => $userData['email'] ?? null
            ]
        );
    }

    /**
     * Log a user update
     */
    public function logUserUpdate(int $adminUserId, int $userId, array $changes): bool
    {
        return $this->logAction(
            $adminUserId,
            'update',
            'users',
            (string)$userId,
            ['changes' => $changes]
        );
    }

    /**
     * Log a user deletion
     */
    public function logUserDelete(int $adminUserId, int $userId, string $username = null): bool
    {
        return $this->logAction(
            $adminUserId,
            'delete',
            'users',
            (string)$userId,
            ['username' => $username]
        );
    }

    /**
     * Log a user block
     */
    public function logUserBlock(int $adminUserId, int $userId, string $reason = null): bool
    {
        return $this->logAction(
            $adminUserId,
            'block',
            'users',
            (string)$userId,
            ['reason' => $reason]
        );
    }

    /**
     * Log a user unblock
     */
    public function logUserUnblock(int $adminUserId, int $userId): bool
    {
        return $this->logAction(
            $adminUserId,
            'unblock',
            'users',
            (string)$userId
        );
    }

    /**
     * Log a user activation
     */
    public function logUserActivate(int $adminUserId, int $userId): bool
    {
        return $this->logAction(
            $adminUserId,
            'activate',
            'users',
            (string)$userId
        );
    }

    /**
     * Log a user deactivation
     */
    public function logUserDeactivate(int $adminUserId, int $userId): bool
    {
        return $this->logAction(
            $adminUserId,
            'deactivate',
            'users',
            (string)$userId
        );
    }

    /**
     * Log a question creation
     */
    public function logQuestionCreate(int $adminUserId, int $questionId, array $questionData): bool
    {
        return $this->logAction(
            $adminUserId,
            'create',
            'questions',
            (string)$questionId,
            [
                'pillar_id' => $questionData['pillar_id'] ?? null,
                'question_text' => substr($questionData['question_text'] ?? '', 0, 100)
            ]
        );
    }

    /**
     * Log a question update
     */
    public function logQuestionUpdate(int $adminUserId, int $questionId, array $changes): bool
    {
        return $this->logAction(
            $adminUserId,
            'update',
            'questions',
            (string)$questionId,
            ['changes' => $changes]
        );
    }

    /**
     * Log a question deletion
     */
    public function logQuestionDelete(int $adminUserId, int $questionId): bool
    {
        return $this->logAction(
            $adminUserId,
            'delete',
            'questions',
            (string)$questionId
        );
    }

    /**
     * Log a challenge creation
     */
    public function logChallengeCreate(int $adminUserId, int $challengeId, array $challengeData): bool
    {
        return $this->logAction(
            $adminUserId,
            'create',
            'challenges',
            (string)$challengeId,
            ['name' => $challengeData['name'] ?? null]
        );
    }

    /**
     * Log a challenge update
     */
    public function logChallengeUpdate(int $adminUserId, int $challengeId, array $changes): bool
    {
        return $this->logAction(
            $adminUserId,
            'update',
            'challenges',
            (string)$challengeId,
            ['changes' => $changes]
        );
    }

    /**
     * Log a challenge deletion
     */
    public function logChallengeDelete(int $adminUserId, int $challengeId): bool
    {
        return $this->logAction(
            $adminUserId,
            'delete',
            'challenges',
            (string)$challengeId
        );
    }

    /**
     * Log a data reset
     */
    public function logReset(int $adminUserId, string $scope = 'user', ?int $targetUserId = null): bool
    {
        return $this->logAction(
            $adminUserId,
            'reset',
            null,
            $targetUserId ? (string)$targetUserId : null,
            ['scope' => $scope]
        );
    }

    /**
     * Log an analytics view
     */
    public function logAnalyticsView(int $adminUserId): bool
    {
        return $this->logAction(
            $adminUserId,
            'view',
            null,
            null,
            ['page' => 'analytics']
        );
    }
}
