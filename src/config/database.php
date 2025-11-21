<?php
$host = getenv('DB_HOST') ?: 'db';
$db   = getenv('DB_NAME') ?: 'gezondheidsmeter';
$user = getenv('DB_USER') ?: 'gezond_user';
$pass = getenv('DB_PASSWORD') ?: 'gezond_pass';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "DB connection successful!";
} catch (\PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}
