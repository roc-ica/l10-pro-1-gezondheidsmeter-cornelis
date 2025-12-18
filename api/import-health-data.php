<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No valid file uploaded']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $content = file_get_contents($_FILES['import_file']['tmp_name']);
    $data = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON format');
    }

    if (!isset($data['health_history']) && !isset($data['calculated_scores'])) {
        throw new Exception('Invalid export file format: missing health_history or calculated_scores');
    }

    require_once __DIR__ . '/../src/config/database.php';
    $pdo = Database::getConnection();

    $pdo->beginTransaction();

    $importedEntries = 0;
    $importedScores = 0;

    // 1. Import Health History (Daily Entries + Answers)
    if (isset($data['health_history']) && is_array($data['health_history'])) {
        foreach ($data['health_history'] as $entry) {
            $date = $entry['date'];
            
            // Validation
            if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) continue;

            // Check if entry already exists
            $stmt = $pdo->prepare("SELECT id FROM daily_entries WHERE user_id = ? AND entry_date = ?");
            $stmt->execute([$userId, $date]);
            $existingId = $stmt->fetchColumn();

            $entryId = $existingId;

            if (!$entryId) {
                // Create new entry
                $stmt = $pdo->prepare("INSERT INTO daily_entries (user_id, entry_date, submitted_at, notes) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $userId, 
                    $date, 
                    $entry['submitted_at'] ?? date('Y-m-d H:i:s'), // Default to now if missing
                    $entry['notes'] ?? null
                ]);
                $entryId = $pdo->lastInsertId();
            } else {
                // Update existing? Optional. Let's update submitted_at if null
                 $stmt = $pdo->prepare("UPDATE daily_entries SET submitted_at = ? WHERE id = ? AND submitted_at IS NULL");
                 $stmt->execute([$entry['submitted_at'] ?? date('Y-m-d H:i:s'), $entryId]);
            }

            $importedEntries++;

            // Import Answers
            if (isset($entry['answers']) && is_array($entry['answers'])) {
                foreach ($entry['answers'] as $ans) {
                    if (empty($ans['question_id']) || !isset($ans['answer'])) continue;

                    // Insert or Update Answer
                    $stmt = $pdo->prepare("
                        INSERT INTO answers (entry_id, question_id, answer_text, score)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE answer_text = VALUES(answer_text), score = VALUES(score)
                    ");
                    $stmt->execute([
                        $entryId,
                        $ans['question_id'],
                        $ans['answer'],
                        $ans['score_value'] ?? null
                    ]);
                }
            }
            
            // Recalculate score for this date to ensure dashboard/history consistency
            require_once __DIR__ . '/../classes/HealthScoreCalculator.php';
            $calculator = new HealthScoreCalculator($userId, $date);
            $calcResult = $calculator->calculateScore();
            
            if ($calcResult['success']) {
                $stmt = $pdo->prepare("
                    INSERT INTO user_health_scores (user_id, score_date, overall_score, pillar_scores, calculation_details)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        overall_score = VALUES(overall_score),
                        pillar_scores = VALUES(pillar_scores),
                        calculation_details = VALUES(calculation_details)
                ");
                
                $stmt->execute([
                    $userId,
                    $date,
                    $calcResult['score'],
                    json_encode($calcResult['pillar_scores']),
                    json_encode($calcResult['calculation_details'])
                ]);
            }
        }
    }

    // 2. Import Calculated Scores
    if (isset($data['calculated_scores']) && is_array($data['calculated_scores'])) {
        foreach ($data['calculated_scores'] as $score) {
            if (empty($score['score_date']) || !isset($score['overall_score'])) continue;

            $stmt = $pdo->prepare("
                INSERT INTO user_health_scores (user_id, score_date, overall_score, pillar_scores, calculation_details)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    overall_score = VALUES(overall_score),
                    pillar_scores = VALUES(pillar_scores),
                    calculation_details = VALUES(calculation_details)
            ");

            $pillarScoresJson = is_array($score['pillar_scores']) ? json_encode($score['pillar_scores']) : $score['pillar_scores'];
            $detailsJson = is_array($score['calculation_details']) ? json_encode($score['calculation_details']) : $score['calculation_details'];

            $stmt->execute([
                $userId,
                $score['score_date'],
                $score['overall_score'],
                $pillarScoresJson,
                $detailsJson
            ]);

            $importedScores++;
        }
    }

    $pdo->commit();

    // Clear session so next visit to vragen.php reloads fresh data from DB
    unset($_SESSION['answered_questions']);

    echo json_encode([
        'success' => true,
        'message' => "Import succesvol: $importedEntries metingen en $importedScores scores verwerkt."
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
