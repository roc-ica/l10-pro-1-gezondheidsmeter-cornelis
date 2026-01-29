<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Incomplete Entries</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-top: 0;
        }
        .success {
            color: #22c55e;
            font-weight: bold;
        }
        .error {
            color: #dc2626;
            font-weight: bold;
        }
        .info {
            color: #666;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #22c55e;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #16a34a;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Add Incomplete Entries</h1>
        
<?php
// Script to add incomplete daily entries for testing
require_once __DIR__ . '/src/config/database.php';

try {
    $pdo = Database::getConnection();
    
    echo '<p class="info">Adding incomplete daily entries...</p>';
    
    // Get more random user IDs to use (need more to avoid UNIQUE constraint on user_id + entry_date)
    $stmt = $pdo->query("SELECT id FROM users WHERE is_admin = 0 ORDER BY RAND() LIMIT 20");
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($users) < 5) {
        echo '<p class="error">❌ Not enough users found. Need at least 5 non-admin users.</p>';
        exit;
    }
    
    echo '<p class="info">Using ' . count($users) . ' users</p>';
    
    // Prepare insert statement with INSERT IGNORE to skip duplicates
    $insertStmt = $pdo->prepare(
        "INSERT IGNORE INTO daily_entries (user_id, entry_date, created_at, updated_at, submitted_at)
         VALUES (?, ?, ?, ?, NULL)"
    );
    
    // Create incomplete entries - use different users for each entry
    $userIndex = 0;
    $incompleteData = [
        // Day 0 (today) - 2 incomplete entries
        ['user_index' => 0, 'days_ago' => 0],
        ['user_index' => 1, 'days_ago' => 0],
        
        // Day 1 (yesterday) - 3 incomplete entries
        ['user_index' => 2, 'days_ago' => 1],
        ['user_index' => 3, 'days_ago' => 1],
        ['user_index' => 4, 'days_ago' => 1],
        
        // Day 2 - 1 incomplete entry
        ['user_index' => 5, 'days_ago' => 2],
        
        // Day 3 - 3 incomplete entries
        ['user_index' => 6, 'days_ago' => 3],
        ['user_index' => 7, 'days_ago' => 3],
        ['user_index' => 8, 'days_ago' => 3],
        
        // Day 4 - 2 incomplete entries
        ['user_index' => 9, 'days_ago' => 4],
        ['user_index' => 10, 'days_ago' => 4],
        
        // Day 5 - 1 incomplete entry
        ['user_index' => 11, 'days_ago' => 5],
        
        // Day 6 - 3 incomplete entries
        ['user_index' => 12, 'days_ago' => 6],
        ['user_index' => 13, 'days_ago' => 6],
        ['user_index' => 14, 'days_ago' => 6],
    ];
    
    $count = 0;
    $added = 0;
    foreach ($incompleteData as $entry) {
        $userIdx = $entry['user_index'] % count($users); // Wrap around if not enough users
        $userId = $users[$userIdx];
        $date = date('Y-m-d', strtotime("-{$entry['days_ago']} days"));
        $timestamp = date('Y-m-d H:i:s', strtotime("-{$entry['days_ago']} days"));
        
        $insertStmt->execute([
            $userId,
            $date,
            $timestamp,
            $timestamp
        ]);
        
        // Check if row was actually inserted (INSERT IGNORE returns 0 affected rows if duplicate)
        if ($insertStmt->rowCount() > 0) {
            $added++;
        }
        $count++;
    }
    
    echo '<p class="success">✓ Attempted to add ' . $count . ' incomplete entries, successfully added ' . $added . '</p>';
    
    // Show summary
    echo '<h2>Summary of Incomplete Entries</h2>';
    echo '<table>';
    echo '<tr><th>Date</th><th>Day</th><th>Incomplete Count</th></tr>';
    
    $stmt = $pdo->query("
        SELECT 
            entry_date,
            COUNT(*) as incomplete_count
        FROM daily_entries 
        WHERE submitted_at IS NULL 
            AND entry_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY entry_date
        ORDER BY entry_date DESC
    ");
    
    $hasIncomplete = false;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $hasIncomplete = true;
        $dayName = date('D', strtotime($row['entry_date']));
        $formattedDate = date('d-m-Y', strtotime($row['entry_date']));
        echo '<tr>';
        echo '<td>' . htmlspecialchars($formattedDate) . '</td>';
        echo '<td>' . htmlspecialchars($dayName) . '</td>';
        echo '<td style="color: #ff6c6c; font-weight: bold;">' . $row['incomplete_count'] . '</td>';
        echo '</tr>';
    }
    
    if (!$hasIncomplete) {
        echo '<tr><td colspan="3" style="text-align: center; color: #999;">No incomplete entries found in the last 7 days</td></tr>';
    }
    
    echo '</table>';
    
    if ($hasIncomplete) {
        echo '<p class="success">✓ Done! Refresh your admin dashboard to see the pink bars.</p>';
    } else {
        echo '<p class="info">ℹ️ No incomplete entries were added. They may already exist or all users already have entries for these dates.</p>';
    }
    echo '<a href="admin/pages/home.php" class="btn">Go to Admin Dashboard</a>';
    
} catch (Exception $e) {
    echo '<p class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>
    </div>
</body>
</html>
