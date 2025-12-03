<?php
/**
 * Admin Action Logger Demo/Test Script
 * 
 * This script demonstrates how to use the AdminActionLogger class.
 * It logs various admin actions to populate the admin_actions table.
 * 
 * Run this from the command line:
 * php classes/admin-action-demo.php
 * 
 * Or access via browser if needed (though CLI is preferred)
 */

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/AdminActionLogger.php';

// For security, only run if there's a session or from CLI
if (php_sapi_name() !== 'cli' && !isset($_SESSION)) {
    die('This script should only be run from command line or with admin session.');
}

try {
    $logger = new AdminActionLogger();
    $pdo = Database::getConnection();

    // Get the first admin user (ID = 1 usually)
    $stmt = $pdo->query("SELECT id FROM users WHERE is_admin = 1 LIMIT 1");
    $adminRow = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    if (!$adminRow) {
        echo "No admin user found in database.\n";
        exit(1);
    }

    $adminUserId = $adminRow['id'];
    echo "Using admin user ID: $adminUserId\n\n";

    // Log various sample admin actions
    $actions = [
        // User actions from last 7 days
        [
            'timestamp' => strtotime('-6 days'),
            'action' => fn() => $logger->logUserCreate($adminUserId, 999, ['username' => 'testuser1', 'email' => 'test1@example.com']),
            'description' => 'Created test user 1'
        ],
        [
            'timestamp' => strtotime('-5 days'),
            'action' => fn() => $logger->logUserUpdate($adminUserId, 2, ['status' => 'active']),
            'description' => 'Updated user 2'
        ],
        [
            'timestamp' => strtotime('-4 days'),
            'action' => fn() => $logger->logUserBlock($adminUserId, 3, 'Inappropriate behavior'),
            'description' => 'Blocked user 3'
        ],
        [
            'timestamp' => strtotime('-3 days'),
            'action' => fn() => $logger->logQuestionCreate($adminUserId, 100, ['pillar_id' => 1, 'question_text' => 'How much water did you drink?']),
            'description' => 'Created new question'
        ],
        [
            'timestamp' => strtotime('-2 days'),
            'action' => fn() => $logger->logChallengeCreate($adminUserId, 50, ['name' => 'Weekly Wellness Challenge']),
            'description' => 'Created new challenge'
        ],
        [
            'timestamp' => strtotime('-1 days'),
            'action' => fn() => $logger->logUserUnblock($adminUserId, 3),
            'description' => 'Unblocked user 3'
        ],
        [
            'timestamp' => strtotime('now'),
            'action' => fn() => $logger->logAnalyticsView($adminUserId),
            'description' => 'Viewed analytics'
        ],
    ];

    echo "Logging sample admin actions...\n";
    foreach ($actions as $item) {
        $result = $item['action']();
        $status = $result ? '✓' : '✗';
        echo "$status {$item['description']}\n";
    }

    echo "\nDone! Admin actions have been logged to the database.\n";
    echo "Check http://localhost:8080/admin/pages/home.php to see the activity log.\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
