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

// Get all users from database using User model
$users = User::getAllUsers('created_at DESC');
$totalUsers = count($users);

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
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body class="auth-page">
    <?php include __DIR__ . '/../../components/navbar-admin.php'; ?>
    
    <div class="dashboard-container1">
        <div class="dashboard-header">
            <div class="dashboard-header-left">
                <h1>Gebruikers Beheer</h1>
                <p>Totaal: <?php echo $totalUsers; ?> gebruiker<?php echo $totalUsers !== 1 ? 's' : ''; ?></p>
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

    <?php foreach ($users as $user): ?>
    <!-- User Card for <?php echo htmlspecialchars($user['username']); ?> -->
    <div class="user-card">
        <div class="user-info-wrapper">
            <div class="user-details">
                <h2 class="user-name"><?php echo htmlspecialchars($user['display_name'] ?? $user['username']); ?></h2>
                <div class="user-contact-info">
                    <div class="contact-item">
                        <i data-lucide="mail" class="contact-icon"></i>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="contact-item">
                        <i data-lucide="user" class="contact-icon"></i>
                        <span>@<?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div class="contact-item">
                        <i data-lucide="calendar" class="contact-icon"></i>
                        <span><?php echo $user['birthdate'] ? htmlspecialchars(date('d-m-Y', strtotime($user['birthdate']))) : 'Niet ingevuld'; ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="user-actions-wrapper">
            <div class="score-display"><?php echo htmlspecialchars($user['is_admin'] ? 'ADMIN' : 'USER'); ?></div>
            <button class="btn-action btn-edit" onclick="openEditUserModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['email']); ?>', '<?php echo htmlspecialchars($user['birthdate'] ?? ''); ?>', '<?php echo htmlspecialchars($user['gender'] ?? ''); ?>')">
                <i data-lucide="pencil"></i> Bewerken
            </button>
            <button class="btn-action btn-delete" onclick="if(confirm('Weet je zeker dat je deze gebruiker wilt verwijderen?')) { deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>'); }">
                <i data-lucide="trash-2"></i> Verwijderen
            </button>
            <button class="btn-action reset-btn" onclick="if(confirm('Weet je zeker dat je de activiteit van deze gebruiker wilt resetten?')) { resetUserActivity(<?php echo $user['id']; ?>); }">Reset Activity</button>
        </div>
    </div>
    <?php endforeach; ?>

</div>

    <?php include __DIR__ . '/../../components/footer.php'; ?>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Gebruiker Bewerken</h2>
                <span class="close" onclick="closeEditUserModal()">&times;</span>
            </div>
            <form id="editUserForm" method="POST">
                <input type="hidden" id="edit_user_id" name="user_id">
                <div class="form-group">
                    <label for="edit_user_email">E-mailadres</label>
                    <input type="email" id="edit_user_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="edit_user_birthdate">Geboortedatum</label>
                    <input type="date" id="edit_user_birthdate" name="birthdate">
                </div>
                <div class="form-group">
                    <label for="edit_user_gender">Geslacht</label>
                    <select id="edit_user_gender" name="gender">
                        <option value="">-- Selecteer --</option>
                        <option value="Male">Man</option>
                        <option value="Female">Vrouw</option>
                        <option value="Other">Anders</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn-save">Opslaan</button>
                    <button type="button" class="btn-cancel" onclick="closeEditUserModal()">Annuleren</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>lucide.createIcons();</script>
    <script src="/js/session-guard.js"></script>
    <script>
        function openEditUserModal(userId, email, birthdate, gender) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_user_email').value = email;
            document.getElementById('edit_user_birthdate').value = birthdate;
            document.getElementById('edit_user_gender').value = gender;
            document.getElementById('editUserModal').style.display = 'block';
        }

        function closeEditUserModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editUserModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Handle form submission
        document.getElementById('editUserForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const userId = document.getElementById('edit_user_id').value;
            const formData = new FormData(document.getElementById('editUserForm'));
            const data = {
                user_id: userId,
                email: formData.get('email'),
                birthdate: formData.get('birthdate'),
                gender: formData.get('gender')
            };

            try {
                const response = await fetch('/api/admin-update-user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    alert('Gebruiker succesvol bijgewerkt!');
                    closeEditUserModal();
                    location.reload();
                } else {
                    alert('Fout: ' + (result.message || 'Onbekende fout'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Er is een fout opgetreden.');
            }
        });

        // Delete user function
        async function deleteUser(userId, username) {
            const formData = new FormData();
            formData.append('action', 'delete_user');
            formData.append('user_id', userId);

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                // Reload page after successful deletion
                setTimeout(() => location.reload(), 500);
            } catch (error) {
                console.error('Error:', error);
                alert('Er is een fout opgetreden bij het verwijderen.');
            }
        }

        // Reset user activity function
        async function resetUserActivity(userId) {
            const formData = new FormData();
            formData.append('action', 'reset_activity');
            formData.append('user_id', userId);

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                // Reload page after successful reset
                setTimeout(() => location.reload(), 500);
            } catch (error) {
                console.error('Error:', error);
                alert('Er is een fout opgetreden bij het resetten.');
            }
        }
    </script>
</body>
</html>
