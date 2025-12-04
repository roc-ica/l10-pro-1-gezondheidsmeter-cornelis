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
    <style>
/* ---------- USER CARD DESIGN ---------- */
:root {
    --color-success: #22c55e;
    --color-danger: #ff6c6c;
    --color-border: #e5e7eb;
    --color-primary-text: #1f2937;
    --color-secondary-text: #4b5563;
    --card-bg: #ffffff;
}

/* Container of all cards */
.user-list-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    max-width: 80%;
    margin: 2% 12%;
}

/* Card */
.user-card {
    background-color: var(--card-bg);
    border: 1px solid var(--color-border);
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 6px 15px rgba(0,0,0,0.12);
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: 0.25s;
}

.user-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 15px 25px rgba(0,0,0,0.18);
}

/* Left side */
.user-info-wrapper {
    display: flex;
    align-items: flex-start;
    gap: 1.5rem;
}

.user-details {
    flex-grow: 1;
}

.user-name {
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--color-primary-text);
    margin: 0 0 0.5rem 0;
}

.user-contact-info {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    font-size: 0.9rem;
    color: var(--color-secondary-text);
}

.contact-item {
    display: flex;
    align-items: center;
}

.contact-icon {
    width: 1rem;
    height: 1rem;
    margin-right: 0.5rem;
    color: #6b7280;
}

/* Right side */
.user-actions-wrapper {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.7rem;
}

.score-display {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-success);
}

/* Buttons */
.btn-action {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    border: 1px solid var(--color-border);
    font-size: 0.85rem;
    cursor: pointer;
    width: 7.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.btn-edit {
    background: #f9fafb;
    color: #374151;
}

.btn-edit:hover {
    background: #f3f4f6;
}

.btn-delete {
    background: #fff;
    border-color: var(--color-danger);
    color: var(--color-danger);
}

.btn-delete:hover {
    background: #fff1f1;
}
.reset-btn {
    background-color: #ff4d4d;
    color: white;
}

.reset-btn:hover {
    background-color: #e60000;
}
</style>
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
<div class="user-list-container">

    <!-- User Card 1 -->
    <div class="user-card">
        <div class="user-info-wrapper">
            <div class="user-details">
                <h2 class="user-name">Jan de Vries</h2>
                <div class="user-contact-info">
                    <div class="contact-item">
                        <i data-lucide="mail" class="contact-icon"></i>
                        <span>jan@example.com</span>
                    </div>
                    <div class="contact-item">
                        <i data-lucide="phone" class="contact-icon"></i>
                        <span>+31 6 12345678</span>
                    </div>
                    <div class="contact-item">
                        <i data-lucide="map-pin" class="contact-icon"></i>
                        <span>Amsterdam</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="user-actions-wrapper">
            <div class="score-display">82</div>
            <button class="btn-action btn-edit">
                <i data-lucide="pencil"></i> Bewerken
            </button>
            <button class="btn-action btn-delete">
                <i data-lucide="trash-2"></i> Verwijderen
            </button>
            <button class="btn-action reset-btn">Reset Activity</button>
        </div>
    </div>

    <!-- User Card 2 -->
    <div class="user-card">
        <div class="user-info-wrapper">
            <div class="user-details">
                <h2 class="user-name">Sarah Jansen</h2>
                <div class="user-contact-info">
                    <div class="contact-item">
                        <i data-lucide="mail" class="contact-icon"></i>
                        <span>sarah.jansen@mail.nl</span>
                    </div>
                    <div class="contact-item">
                        <i data-lucide="phone" class="contact-icon"></i>
                        <span>+31 6 98765432</span>
                    </div>
                    <div class="contact-item">
                        <i data-lucide="map-pin" class="contact-icon"></i>
                        <span>Utrecht</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="user-actions-wrapper">
            <div class="score-display">91</div>
            <button class="btn-action btn-edit">
                <i data-lucide="pencil"></i> Bewerken
            </button>
            <button class="btn-action btn-delete">
                <i data-lucide="trash-2"></i> Verwijderen
            </button>
            <button class="btn-action reset-btn">Reset Activity</button>
        </div>
    </div>

    <!-- User Card 3 -->
    <div class="user-card">
        <div class="user-info-wrapper">
            <div class="user-details">
                <h2 class="user-name">Mohamed Ali</h2>
                <div class="user-contact-info">
                    <div class="contact-item">
                        <i data-lucide="mail" class="contact-icon"></i>
                        <span>mohamed.ali@mail.com</span>
                    </div>
                    <div class="contact-item">
                        <i data-lucide="phone" class="contact-icon"></i>
                        <span>+31 6 11223344</span>
                    </div>
                    <div class="contact-item">
                        <i data-lucide="map-pin" class="contact-icon"></i>
                        <span>Rotterdam</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="user-actions-wrapper">
            <div class="score-display">76</div>
            <button class="btn-action btn-edit">
                <i data-lucide="pencil"></i> Bewerken
            </button>
            <button class="btn-action btn-delete">
                <i data-lucide="trash-2"></i> Verwijderen
            </button>
            <button class="btn-action reset-btn">Reset Activity</button>
        </div>
    </div>

</div>

    <?php include __DIR__ . '/../../components/footer.php'; ?>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
