<?php

require_once __DIR__ . '/../config/database.php';

class Question
{
    public $id;
    public $pillar_id;
    public $question_text;
    public $input_type;
    public $active;
    public $created_at;

    // Extra properties for display
    public $pillar_name;
    public $pillar_color;

    public function __construct($data = [])
    {
        if ($data) {
            $this->id = $data['id'] ?? null;
            $this->pillar_id = $data['pillar_id'] ?? null;
            $this->question_text = $data['question_text'] ?? null;
            $this->input_type = $data['input_type'] ?? null;
            $this->active = isset($data['active']) ? (int) $data['active'] : null;
            $this->created_at = $data['created_at'] ?? null;

            $this->pillar_name = $data['pillar_name'] ?? null;
            $this->pillar_color = $data['pillar_color'] ?? null;
        }
    }

    public static function getAllWithPillars(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT q.*, p.name as pillar_name, p.color as pillar_color 
            FROM questions q 
            JOIN pillars p ON q.pillar_id = p.id 
            ORDER BY q.id DESC
        ");
        $rows = $stmt->fetchAll();
        $results = [];
        foreach ($rows as $r) {
            $results[] = new self($r);
        }
        return $results;
    }

    public static function add(int $pillar_id, string $text): int|bool
    {
        $pdo = Database::getConnection();
        try {
            $stmt = $pdo->prepare("INSERT INTO questions (pillar_id, question_text, input_type, active) VALUES (?, ?, 'number', 1)");
            if ($stmt->execute([$pillar_id, $text])) {
                return (int) $pdo->lastInsertId();
            }
            return false;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        try {
            $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public static function update(int $id, string $text, int $pillar_id = null): bool
    {
        $pdo = Database::getConnection();
        try {
            if ($pillar_id !== null) {
                $stmt = $pdo->prepare("UPDATE questions SET question_text = ?, pillar_id = ? WHERE id = ?");
                return $stmt->execute([$text, $pillar_id, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE questions SET question_text = ? WHERE id = ?");
                return $stmt->execute([$text, $id]);
            }
        } catch (\PDOException $e) {
            return false;
        }
    }
}
