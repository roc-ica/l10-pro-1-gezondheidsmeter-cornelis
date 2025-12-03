<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../src/views/auth/login.php');
    exit;
}

// Check if user is admin
require_once __DIR__ . '/../../src/models/User.php';
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../classes/AdminActionLogger.php';

$user = \User::findByIdStatic($_SESSION['user_id']);

if (!$user || !$user->is_admin) {
    // Not admin, redirect to normal home
    header('Location: ../../pages/home.php');
    exit;
}

// Initialize action logger
$logger = new AdminActionLogger();
$adminUserId = $_SESSION['user_id'];
$pdo = Database::getConnection();

$username = $_SESSION['username'] ?? 'Admin';
$message = '';
$error = '';

// Handle Form Submissions - Automatically log all user management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        
        // Create new user
        if ($_POST['action'] === 'create_user') {
            $newUsername = trim($_POST['username'] ?? '');
            $newEmail = trim($_POST['email'] ?? '');
            $newPassword = $_POST['password'] ?? '';
            $displayName = trim($_POST['display_name'] ?? '');
            
            if ($newUsername && $newEmail && $newPassword) {
                $result = User::register($newUsername, $newEmail, $newPassword);
                if ($result['success']) {
                    $message = "Gebruiker succesvol aangemaakt!";
                    $newUser = User::findByUsernameStatic($newUsername);
                    if ($newUser) {
                        $logger->logUserCreate($adminUserId, $newUser->id, [
                            'username' => $newUsername,
                            'email' => $newEmail,
                            'display_name' => $displayName
                        ]);
                    }
                } else {
                    $error = $result['message'] ?? 'Fout bij aanmaken gebruiker.';
                }
            } else {
                $error = "Vul alle velden in.";
            }
        }
        
        // Update user
        elseif ($_POST['action'] === 'update_user') {
            $userId = $_POST['user_id'] ?? null;
            $displayName = trim($_POST['display_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
            
            if ($userId) {
                $targetUser = User::findByIdStatic((int)$userId);
                if ($targetUser) {
                    $changes = [];
                    if ($displayName) $changes['display_name'] = $displayName;
                    if ($email) $changes['email'] = $email;
                    if ($isAdmin !== $targetUser->is_admin) $changes['is_admin'] = $isAdmin;
                    
                    $updateResult = $targetUser->update(['display_name' => $displayName, 'email' => $email, 'is_admin' => $isAdmin]);
                    if ($updateResult['success']) {
                        $message = "Gebruiker bijgewerkt!";
                        if (!empty($changes)) {
                            $logger->logUserUpdate($adminUserId, (int)$userId, $changes);
                        }
                    } else {
                        $error = "Fout bij bijwerken gebruiker.";
                    }
                }
            }
        }
        
        // Block user
        elseif ($_POST['action'] === 'block_user') {
            $userId = $_POST['user_id'] ?? null;
            $reason = trim($_POST['reason'] ?? '');
            
            if ($userId) {
                $stmt = $pdo->prepare("UPDATE users SET is_active = 0, block_reason = ? WHERE id = ?");
                if ($stmt->execute([$reason, $userId])) {
                    $message = "Gebruiker geblokkeerd.";
                    $logger->logUserBlock($adminUserId, (int)$userId, $reason);
                } else {
                    $error = "Fout bij blokkeren gebruiker.";
                }
            }
        }
        
        // Unblock user
        elseif ($_POST['action'] === 'unblock_user') {
            $userId = $_POST['user_id'] ?? null;
            if ($userId) {
                $stmt = $pdo->prepare("UPDATE users SET is_active = 1, block_reason = NULL WHERE id = ?");
                if ($stmt->execute([$userId])) {
                    $message = "Gebruiker gedeblokkeerd.";
                    $logger->logUserUnblock($adminUserId, (int)$userId);
                } else {
                    $error = "Fout bij deblokkeren gebruiker.";
                }
            }
        }
        
        // Delete user
        elseif ($_POST['action'] === 'delete_user') {
            $userId = $_POST['user_id'] ?? null;
            if ($userId && $userId != $adminUserId) {
                $targetUser = User::findByIdStatic((int)$userId);
                if ($targetUser) {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    if ($stmt->execute([$userId])) {
                        $message = "Gebruiker verwijderd.";
                        $logger->logUserDelete($adminUserId, (int)$userId, $targetUser->username);
                    } else {
                        $error = "Fout bij verwijderen gebruiker.";
                    }
                }
            } elseif ($userId == $adminUserId) {
                $error = "Je kunt jezelf niet verwijderen.";
            }
        }
        
        // Activate user
        elseif ($_POST['action'] === 'activate_user') {
            $userId = $_POST['user_id'] ?? null;
            if ($userId) {
                $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
                if ($stmt->execute([$userId])) {
                    $message = "Gebruiker geactiveerd.";
                    $logger->logUserActivate($adminUserId, (int)$userId);
                } else {
                    $error = "Fout bij activeren gebruiker.";
                }
            }
        }
        
        // Deactivate user
        elseif ($_POST['action'] === 'deactivate_user') {
            $userId = $_POST['user_id'] ?? null;
            if ($userId && $userId != $adminUserId) {
                $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
                if ($stmt->execute([$userId])) {
                    $message = "Gebruiker gedeactiveerd.";
                    $logger->logUserDeactivate($adminUserId, (int)$userId);
                } else {
                    $error = "Fout bij deactiveren gebruiker.";
                }
            } elseif ($userId == $adminUserId) {
                $error = "Je kunt jezelf niet deactiveren.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Gebruikers - Gezondheidsmeter</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="auth-page">
    <?php include __DIR__ . '/../../components/navbar-admin.php'; ?>
    
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="dashboard-header-left">
                <h1>Gebruikers Beheer</h1>
                <p>Totaal: 5 gebruikers</p>
            </div>
            <div class="dashboard-header-right">
                <a href="./home.php" class="btn-naar-app">Naar App</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message" style="background-color: #dcfce7; color: #166534; border-color: #166534;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message" style="background-color: #fee2e2; color: #991b1b; border-color: #991b1b;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../../components/footer.php'; ?>
</body>
</html>
