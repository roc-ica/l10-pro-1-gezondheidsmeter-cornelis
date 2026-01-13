<?php

require_once __DIR__ . '/../config/database.php';

class HealthDataService
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Export all health data for a user as JSON array
     */
    public function exportHealthData(int $userId): array
    {
        // 1. Fetch Profile
        $stmt = $this->pdo->prepare("SELECT username, email, birthdate, geslacht, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profile) {
            return ['success' => false, 'message' => 'Gebruiker niet gevonden'];
        }

        // 2. Fetch Daily Entries and Answers
        $stmt = $this->pdo->prepare("
            SELECT de.entry_date, de.submitted_at, de.notes,
                   q.id as question_id, q.question_text, a.answer_text, p.name as pillar_name,
                   a.score as answer_score
            FROM daily_entries de
            LEFT JOIN answers a ON de.id = a.entry_id
            LEFT JOIN questions q ON a.question_id = q.id
            LEFT JOIN pillars p ON q.pillar_id = p.id
            WHERE de.user_id = ?
            ORDER BY de.entry_date DESC
        ");
        $stmt->execute([$userId]);
        $rawEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group entries by date
        $entries = [];
        foreach ($rawEntries as $row) {
            if (empty($row['entry_date'])) continue;

            $date = $row['entry_date'];
            if (!isset($entries[$date])) {
                $entries[$date] = [
                    'date' => $date,
                    'submitted_at' => $row['submitted_at'],
                    'notes' => $row['notes'],
                    'answers' => []
                ];
            }

            if (!empty($row['question_text'])) {
                $entries[$date]['answers'][] = [
                    'question_id' => $row['question_id'],
                    'question' => $row['question_text'],
                    'answer' => $row['answer_text'],
                    'category' => $row['pillar_name'],
                    'score_value' => $row['answer_score']
                ];
            }
        }

        // 3. Fetch Calculated Health Scores
        $stmt = $this->pdo->prepare("
            SELECT score_date, overall_score, pillar_scores, calculation_details 
            FROM user_health_scores 
            WHERE user_id = ? 
            ORDER BY score_date DESC
        ");
        $stmt->execute([$userId]);
        $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode JSON columns in scores
        foreach ($scores as &$score) {
            if (!empty($score['pillar_scores'])) {
                $score['pillar_scores'] = json_decode($score['pillar_scores'], true);
            }
            if (!empty($score['calculation_details'])) {
                $score['calculation_details'] = json_decode($score['calculation_details'], true);
            }
        }

        // Build Final Export Array
        $exportData = [
            'metadata' => [
                'exported_at' => date('Y-m-d H:i:s'),
                'app_name' => 'Gezondheidsmeter',
                'version' => '1.0'
            ],
            'profile' => $profile,
            'health_history' => array_values($entries),
            'calculated_scores' => $scores
        ];

        return ['success' => true, 'data' => $exportData];
    }

    /**
     * Delete all health data for a user
     */
    public function deleteAllHealthData(int $userId): array
    {
        try {
            // Start transaction
            $this->pdo->beginTransaction();

            // Get all daily entries for this user
            $stmt = $this->pdo->prepare("SELECT id FROM daily_entries WHERE user_id = ?");
            $stmt->execute([$userId]);
            $entries = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $deletedEntries = count($entries);
            $deletedAnswers = 0;

            if ($deletedEntries > 0) {
                // Delete all answers for these entries
                $placeholders = str_repeat('?,', count($entries) - 1) . '?';
                $stmt = $this->pdo->prepare("DELETE FROM answers WHERE entry_id IN ($placeholders)");
                $stmt->execute($entries);
                $deletedAnswers = $stmt->rowCount();

                // Delete all daily entries for this user
                $stmt = $this->pdo->prepare("DELETE FROM daily_entries WHERE user_id = ?");
                $stmt->execute([$userId]);
            }

            // Delete calculated health scores
            $stmt = $this->pdo->prepare("DELETE FROM user_health_scores WHERE user_id = ?");
            $stmt->execute([$userId]);
            $deletedScores = $stmt->rowCount();

            // Commit transaction
            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Alle gezondheidsgegevens zijn succesvol gewist',
                'deleted_entries' => $deletedEntries,
                'deleted_answers' => $deletedAnswers,
                'deleted_scores' => $deletedScores
            ];
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return [
                'success' => false,
                'message' => 'Database fout: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Import health data from JSON export file
     */
    public function importHealthData(int $userId, string $jsonContent): array
    {
        try {
            $data = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['success' => false, 'message' => 'Ongeldig JSON-formaat'];
            }

            if (!isset($data['health_history']) && !isset($data['calculated_scores'])) {
                return ['success' => false, 'message' => 'Ongeldig exportbestandformaat: health_history of calculated_scores ontbreekt'];
            }

            $this->pdo->beginTransaction();

            $importedEntries = 0;
            $importedScores = 0;

            // 1. Import Health History (Daily Entries + Answers)
            if (isset($data['health_history']) && is_array($data['health_history'])) {
                foreach ($data['health_history'] as $entry) {
                    $date = $entry['date'] ?? null;

                    // Validation
                    if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) continue;

                    // Check if entry already exists
                    $stmt = $this->pdo->prepare("SELECT id FROM daily_entries WHERE user_id = ? AND entry_date = ?");
                    $stmt->execute([$userId, $date]);
                    $existingId = $stmt->fetchColumn();

                    $entryId = $existingId;

                    if (!$entryId) {
                        // Create new entry
                        $stmt = $this->pdo->prepare("INSERT INTO daily_entries (user_id, entry_date, submitted_at, notes) VALUES (?, ?, ?, ?)");
                        $stmt->execute([
                            $userId,
                            $date,
                            $entry['submitted_at'] ?? date('Y-m-d H:i:s'),
                            $entry['notes'] ?? null
                        ]);
                        $entryId = $this->pdo->lastInsertId();
                    } else {
                        // Update existing entry's submitted_at if null
                        $stmt = $this->pdo->prepare("UPDATE daily_entries SET submitted_at = ? WHERE id = ? AND submitted_at IS NULL");
                        $stmt->execute([$entry['submitted_at'] ?? date('Y-m-d H:i:s'), $entryId]);
                    }

                    $importedEntries++;

                    // Import Answers
                    if (isset($entry['answers']) && is_array($entry['answers'])) {
                        foreach ($entry['answers'] as $answer) {
                            $questionId = $answer['question_id'] ?? null;
                            $answerText = $answer['answer'] ?? '';
                            $score = $answer['score_value'] ?? null;

                            if (!$questionId) continue;

                            // Check if answer already exists
                            $stmt = $this->pdo->prepare("SELECT id FROM answers WHERE entry_id = ? AND question_id = ?");
                            $stmt->execute([$entryId, $questionId]);
                            $existingAnswerId = $stmt->fetchColumn();

                            if (!$existingAnswerId) {
                                // Insert new answer
                                $stmt = $this->pdo->prepare("INSERT INTO answers (entry_id, question_id, answer_text, score) VALUES (?, ?, ?, ?)");
                                $stmt->execute([$entryId, $questionId, $answerText, $score]);
                            }
                        }
                    }
                }
            }

            // 2. Import Calculated Scores
            if (isset($data['calculated_scores']) && is_array($data['calculated_scores'])) {
                foreach ($data['calculated_scores'] as $score) {
                    $scoreDate = $score['score_date'] ?? null;

                    if (!$scoreDate || !preg_match('/^\d{4}-\d{2}-\d{2}/', $scoreDate)) continue;

                    // Check if score already exists
                    $stmt = $this->pdo->prepare("SELECT id FROM user_health_scores WHERE user_id = ? AND score_date = ?");
                    $stmt->execute([$userId, $scoreDate]);
                    $existingScoreId = $stmt->fetchColumn();

                    if (!$existingScoreId) {
                        $pillarScores = !empty($score['pillar_scores']) ? json_encode($score['pillar_scores']) : null;
                        $calculationDetails = !empty($score['calculation_details']) ? json_encode($score['calculation_details']) : null;

                        $stmt = $this->pdo->prepare("
                            INSERT INTO user_health_scores (user_id, score_date, overall_score, pillar_scores, calculation_details)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $userId,
                            $scoreDate,
                            $score['overall_score'] ?? null,
                            $pillarScores,
                            $calculationDetails
                        ]);
                        $importedScores++;
                    }
                }
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Gezondheidsgegevens succesvol geïmporteerd',
                'imported_entries' => $importedEntries,
                'imported_scores' => $importedScores
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return [
                'success' => false,
                'message' => 'Database fout: ' . $e->getMessage()
            ];
        }
    }
}
?>
