<?php
require 'database.php';

$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll();

echo "<pre>";
print_r($tables);
echo "</pre>";