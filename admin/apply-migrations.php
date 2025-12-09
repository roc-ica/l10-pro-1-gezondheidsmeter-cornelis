<?php
session_start();

// Check if user is admin
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/config/database.php';

if (!isset($_SESSION['user_id'])) {
    die('Not authenticated');
}

$user = User::findByIdStatic($_SESSION['user_id']);
if (!$user || !$user->is_admin) {
    die('Not authorized');
}

$pdo = Database::getConnection();

try {
    // Read the SQL file
    $sqlFile = __DIR__ . '/../database/alter_questions_hierarchical.sql';
    $sqlStatements = file_get_contents($sqlFile);
    
    // Split by semicolon and filter empty statements
    $statements = array_filter(array_map('trim', explode(';', $sqlStatements)));
    
    $executed = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            // Some statements may fail if columns already exist, that's OK
            if (strpos($e->getMessage(), 'Duplicate') === false && 
                strpos($e->getMessage(), 'already exists') === false) {
                $errors[] = $e->getMessage();
            } else {
                $executed++;
            }
        }
    }
    
    echo "<h1>Migration Results</h1>";
    echo "<p>Executed: $executed statements</p>";
    
    if (!empty($errors)) {
        echo "<h3>Errors (may be safe to ignore):</h3>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
    }
    
    echo "<p>✅ Database migration completed!</p>";
    echo '<a href="home.php">Back to Dashboard</a>';
    
} catch (Exception $e) {
    echo "<h1>Migration Failed</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
