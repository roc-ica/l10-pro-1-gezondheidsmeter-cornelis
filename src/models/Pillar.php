<?php

require_once __DIR__ . '/../config/database.php';

class Pillar
{
    public $id;
    public $name;
    public $description;
    public $color;

    public function __construct($data = [])
    {
        if ($data) {
            $this->id = $data['id'] ?? null;
            $this->name = $data['name'] ?? null;
            $this->description = $data['description'] ?? null;
            $this->color = $data['color'] ?? null;
        }
    }

    public static function getAll(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM pillars ORDER BY id');
        $rows = $stmt->fetchAll();
        $results = [];
        foreach ($rows as $r) {
            $results[] = new self($r);
        }
        return $results;
    }
}
