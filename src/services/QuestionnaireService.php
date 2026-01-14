<?php

require_once __DIR__ . '/../config/database.php';

class QuestionnaireService
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Save a single answer to a question
     * @param int $userId User ID
     * @param int $questionId Question ID
     * @param string $answer Answer text
     * @return array Success/error response
     */
    public function saveAnswer(int $userId, int $questionId, string $answer): array
    {
        try {
            // Start transaction
            $this->pdo->beginTransaction();

            // Get or create today's daily entry
            $today = date('Y-m-d');

            $stmt = $this->pdo->prepare("
                SELECT id FROM daily_entries 
                WHERE user_id = ? AND entry_date = ?
            ");
            $stmt->execute([$userId, $today]);
            $entry = $stmt->fetch();

            if ($entry) {
                $entryId = $entry['id'];
            } else {
                // Create new daily entry
                $stmt = $this->pdo->prepare("
                    INSERT INTO daily_entries (user_id, entry_date, submitted_at) 
                    VALUES (?, ?, NULL)
                ");
                $stmt->execute([$userId, $today]);
                $entryId = $this->pdo->lastInsertId();
            }

            // Check if answer already exists for this question
            $stmt = $this->pdo->prepare("
                SELECT id FROM answers 
                WHERE entry_id = ? AND question_id = ?
            ");
            $stmt->execute([$entryId, $questionId]);
            $existingAnswer = $stmt->fetch();

            if ($existingAnswer) {
                // Update existing answer
                $stmt = $this->pdo->prepare("
                    UPDATE answers 
                    SET answer_text = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$answer, $existingAnswer['id']]);
            } else {
                // Insert new answer
                $stmt = $this->pdo->prepare("
                    INSERT INTO answers (entry_id, question_id, answer_text, score) 
                    VALUES (?, ?, ?, NULL)
                ");
                $stmt->execute([$entryId, $questionId, $answer]);
            }

            // Get total MAIN questions count (each pair = 1 question)
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM questions WHERE active = 1");
            $stmt->execute();
            $totalQuestions = $stmt->fetchColumn();

            // Count answered question PAIRS for today's entry
            // A pair is complete when both main and sub_question are answered
            $stmt = $this->pdo->prepare("
                SELECT COUNT(DISTINCT q.id) as count
                FROM questions q
                WHERE q.active = 1
                AND EXISTS (
                    SELECT 1 FROM answers a 
                    WHERE a.entry_id = ? AND a.question_id = q.id
                )
                AND EXISTS (
                    SELECT 1 FROM answers a 
                    INNER JOIN sub_questions sq ON a.sub_question_id = sq.id
                    WHERE a.entry_id = ? AND sq.parent_question_id = q.id
                )
            ");
            $stmt->execute([$entryId, $entryId]);
            $result = $stmt->fetch();
            $answeredQuestions = $result['count'];

            // Commit transaction
            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Antwoord opgeslagen',
                'entry_id' => $entryId,
                'answered_count' => $answeredQuestions,
                'total_count' => $totalQuestions,
                'progress_percentage' => $totalQuestions > 0 ? round(($answeredQuestions / $totalQuestions) * 100) : 0
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return [
                'success' => false,
                'message' => 'Fout bij het opslaan van antwoord: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Submit/complete today's questionnaire
     * @param int $userId User ID
     * @return array Success/error response
     */
    public function submitQuestionnaire(int $userId): array
    {
        try {
            // Get today's entry
            $today = date('Y-m-d');

            $stmt = $this->pdo->prepare("
                SELECT id FROM daily_entries 
                WHERE user_id = ? AND entry_date = ?
            ");
            $stmt->execute([$userId, $today]);
            $entry = $stmt->fetch();

            if (!$entry) {
                return [
                    'success' => false,
                    'message' => 'Geen vragen beantwoord vandaag.'
                ];
            }

            $entryId = $entry['id'];

            // Update the entry to mark it as submitted
            $stmt = $this->pdo->prepare("
                UPDATE daily_entries 
                SET submitted_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$entryId]);

            return [
                'success' => true,
                'message' => 'Vragenlijst succesvol voltooid!',
                'entry_id' => $entryId
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Fout bij het indienen van vragenlijst: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get today's entry ID and answer progress
     * @param int $userId User ID
     * @return array Entry data or empty array if no entry
     */
    public function getTodayEntry(int $userId): array
    {
        $today = date('Y-m-d');

        $stmt = $this->pdo->prepare("
            SELECT id, submitted_at FROM daily_entries 
            WHERE user_id = ? AND entry_date = ?
        ");
        $stmt->execute([$userId, $today]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$entry) {
            return [];
        }

        // Get answered questions for this entry
        $stmt = $this->pdo->prepare("
            SELECT question_id, answer_text, score 
            FROM answers 
            WHERE entry_id = ?
            ORDER BY question_id ASC
        ");
        $stmt->execute([$entry['id']]);
        $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $entry['answers'] = $answers;
        $entry['answered_count'] = count($answers);

        return $entry;
    }

    /**
     * Reset today's entry (delete answers and unsubmit)
     * @param int $userId User ID
     * @return array Success/error response
     */
    public function getQuestionPairs(): array
    {
        // 1. Fetch all active main questions
        $stmt = $this->pdo->prepare("
            SELECT q.*, p.name as pillar_name, p.color as pillar_color
            FROM questions q
            JOIN pillars p ON q.pillar_id = p.id
            WHERE q.active = 1
            ORDER BY q.id ASC
        ");
        $stmt->execute();
        $mainQuestions = $stmt->fetchAll();

        // 2. Build question pairs (main + secondary)
        $questionPairs = [];
        foreach ($mainQuestions as $main) {
            $stmt = $this->pdo->prepare("
                SELECT * FROM sub_questions 
                WHERE active = 1 AND parent_question_id = ?
                LIMIT 1
            ");
            $stmt->execute([$main['id']]);
            $secondary = $stmt->fetch();
            
            if ($secondary) {
                $questionPairs[] = [
                    'main' => $main,
                    'secondary' => $secondary
                ];
            }
        }
        return $questionPairs;
    }

    /**
     * Handle the AJAX answer saving logic previously in vragen.php
     */
    public function handleAjaxRequest(int $userId, array $postData): array
    {
        $action = $postData['action'] ?? null;
        
        if ($action === 'answer_main' || $action === 'answer_secondary') {
            $questionId = (int)($postData['question_id'] ?? 0);
            $subQuestionId = (int)($postData['sub_question_id'] ?? 0);
            $answer = $postData['answer'] ?? null;
            
            if (!$questionId || $answer === null) {
                return ['success' => false, 'message' => 'Ongeldige parameters'];
            }

            try {
                // Get or create today's daily entry
                $today = date('Y-m-d');
                $stmt = $this->pdo->prepare("SELECT id FROM daily_entries WHERE user_id = ? AND entry_date = ?");
                $stmt->execute([$userId, $today]);
                $entry = $stmt->fetch();
                
                if ($entry) {
                    $entryId = $entry['id'];
                } else {
                    $stmt = $this->pdo->prepare("INSERT INTO daily_entries (user_id, entry_date) VALUES (?, ?)");
                    $stmt->execute([$userId, $today]);
                    $entryId = $this->pdo->lastInsertId();
                }
                
                // For main question, save answer to answers table
                if ($action === 'answer_main') {
                    $stmt = $this->pdo->prepare("SELECT id FROM answers WHERE entry_id = ? AND question_id = ?");
                    $stmt->execute([$entryId, $questionId]);
                    $existingAnswer = $stmt->fetch();
                    
                    if ($existingAnswer) {
                        $stmt = $this->pdo->prepare("UPDATE answers SET answer_text = ? WHERE id = ?");
                        $stmt->execute([$answer, $existingAnswer['id']]);
                    } else {
                        $stmt = $this->pdo->prepare("INSERT INTO answers (entry_id, question_id, answer_text) VALUES (?, ?, ?)");
                        $stmt->execute([$entryId, $questionId, $answer]);
                    }
                    
                    // If answer is "Nee", automatically set sub-question to 0 if it exists
                    if ($answer === 'Nee' && $subQuestionId) {
                        $stmt = $this->pdo->prepare("SELECT id FROM answers WHERE entry_id = ? AND sub_question_id = ?");
                        $stmt->execute([$entryId, $subQuestionId]);
                        $existingSubAnswer = $stmt->fetch();
                        
                        if ($existingSubAnswer) {
                            $stmt = $this->pdo->prepare("UPDATE answers SET answer_text = ? WHERE id = ?");
                            $stmt->execute(['0', $existingSubAnswer['id']]);
                        } else {
                            $stmt = $this->pdo->prepare("INSERT INTO answers (entry_id, question_id, sub_question_id, answer_text) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$entryId, $questionId, $subQuestionId, '0']);
                        }
                    }
                }
                // For sub_question, save answer with sub_question_id
                elseif ($action === 'answer_secondary' && $subQuestionId) {
                    $stmt = $this->pdo->prepare("SELECT id FROM answers WHERE entry_id = ? AND sub_question_id = ?");
                    $stmt->execute([$entryId, $subQuestionId]);
                    $existingAnswer = $stmt->fetch();
                    
                    if ($existingAnswer) {
                        $stmt = $this->pdo->prepare("UPDATE answers SET answer_text = ? WHERE id = ?");
                        $stmt->execute([$answer, $existingAnswer['id']]);
                    } else {
                        $stmt = $this->pdo->prepare("INSERT INTO answers (entry_id, question_id, sub_question_id, answer_text) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$entryId, $questionId, $subQuestionId, $answer]);
                    }
                }

                // Update Progress State (Session management is done in the Page/Controller)
                return [
                    'success' => true, 
                    'message' => 'Antwoord opgeslagen',
                    'action' => $action,
                    'answer' => $answer
                ];

            } catch (Exception $e) {
                return ['success' => false, 'message' => 'Database fout: ' . $e->getMessage()];
            }
        }
        
        return ['success' => false, 'message' => 'Onbekende actie'];
    }

    public function resetTodayEntry(int $userId): array
    {
        try {
            $today = date('Y-m-d');

            // Get today's entry ID
            $stmt = $this->pdo->prepare("SELECT id FROM daily_entries WHERE user_id = ? AND entry_date = ?");
            $stmt->execute([$userId, $today]);
            $entry = $stmt->fetch();

            if (!$entry) {
                return ['success' => true, 'message' => 'Geen entry om te resetten'];
            }

            $entryId = $entry['id'];

            // Start transaction
            $this->pdo->beginTransaction();

            // Delete existing answers
            $stmt = $this->pdo->prepare("DELETE FROM answers WHERE entry_id = ?");
            $stmt->execute([$entryId]);

            // Reset submission status
            $stmt = $this->pdo->prepare("UPDATE daily_entries SET submitted_at = NULL WHERE id = ?");
            $stmt->execute([$entryId]);

            // Clear calculated score for today
            $stmt = $this->pdo->prepare("DELETE FROM user_health_scores WHERE user_id = ? AND score_date = ?");
            $stmt->execute([$userId, $today]);

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Vragen gereset'
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return [
                'success' => false,
                'message' => 'Fout bij het resetten: ' . $e->getMessage()
            ];
        }
    }
}
?>
