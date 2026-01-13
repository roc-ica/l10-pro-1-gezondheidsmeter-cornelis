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
require_once __DIR__ . '/../../src/models/AdminActionLogger.php';

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
$search = trim($_GET['search'] ?? ''); // get search input

$users = User::getAllUsers(); // get all users

// FILTER users if search is not empty
if ($search !== '') {
    $users = array_filter($users, function ($u) use ($search) {
        return str_contains(strtolower($u['username']), strtolower($search)) ||
            str_contains(strtolower($u['display_name'] ?? ''), strtolower($search));
    });
}

$totalUsers = count($users);

// Pagination 10 per page
$usersPerPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $usersPerPage;
$paginatedUsers = array_slice($users, $offset, $usersPerPage);
$totalPages = ceil($totalUsers / $usersPerPage);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Delete user
        if ($_POST['action'] === 'delete_user') {
            $userId = $_POST['user_id'] ?? null;
            if ($userId) {
                $deleteResult = User::delete((int)$userId, $adminUserId);
                if ($deleteResult['success']) {
                    $message = "Gebruiker permanent verwijderd.";
                } else {
                    $error = "Fout bij verwijderen gebruiker: " . $deleteResult['message'];
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
    <style>
        /*PAGINATION VOOR GEBRUIKERS ADMIN*/
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 40px 0;
            flex-wrap: wrap;
        }

        .page-btn {
            padding: 8px 14px;
            border-radius: 6px;
            background-color: #ffffff;
            color: #0f172a;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .page-btn:hover {
            background-color: #e2e8f0;
        }

        .page-btn.active {
            background-color: #22c55e;
            color: white;
        }
    </style>
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
                <a href="../../pages/home.php" class="btn-naar-app">Naar App</a>
            </div>
        </div>
        <form method="GET" style="margin: 20px 0; text-align: center;">
            <input type="text" name="search" placeholder="Zoek gebruiker..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="padding:8px; width:250px; border-radius:5px; border:1px solid #ccc;">
            <button type="submit" style="padding:8px 12px; border-radius:5px; background:#22c55e; color:white; border:none; cursor:pointer;">Zoeken</button>
        </form>


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

        <?php foreach ($paginatedUsers as $user): ?>
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
                            <!-- <div class="contact-item">
                        <i data-lucide="user" class="contact-icon"></i>
                        <span>@<?php echo htmlspecialchars($user['username']); ?></span>
                    </div> -->
                            <div class="contact-item">
                                <i data-lucide="calendar" class="contact-icon"></i>
                                <span><?php echo $user['birthdate'] ? htmlspecialchars(date('d-m-Y', strtotime($user['birthdate']))) : 'Niet ingevuld'; ?></span>
                            </div>
                            <div class="contact-item">
                                <i data-lucide="smile" class="contact-icon"></i>
                                <span><?php echo htmlspecialchars($user['geslacht'] ?? 'Niet ingevuld'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="user-actions-wrapper">
                    <div class="score-display"><?php echo htmlspecialchars($user['is_admin'] ? 'ADMIN' : 'USER');
                                                echo (' ');
                                                echo htmlspecialchars($user['id']) ?></div>
                    <button class="btn-action btn-delete" style="background-color: #ef4444; color: white; border: none; margin-bottom: 5px;" onclick="openDeleteUserModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                        <i data-lucide="trash-2"></i> Verwijderen
                    </button>
                    <button class="btn-action btn-delete" onclick="<?php echo $user['is_active'] ? "openBlockUserModal(" . $user['id'] . ", '" . htmlspecialchars($user['username']) . "')" : "openUnblockUserModal(" . $user['id'] . ", '" . htmlspecialchars($user['username']) . "')" ?>">
                        <i data-lucide="lock"></i> <?php echo $user['is_active'] ? 'Blokkeren' : 'Deblokkeren'; ?>
                    </button>
                    <button class="btn-action reset-btn" onclick="if(confirm('Weet je zeker dat je de activiteit van deze gebruiker wilt resetten?')) { resetUserActivity(<?php echo $user['id']; ?>); }">Reset Activity</button>
                </div>
            </div>
        <?php endforeach; ?>

    </div>
    <!-- Pagination -->
    <div class="pagination">
        <?php
        $searchParam = isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
        ?>

        <?php if ($page > 1): ?>
            <a class="page-btn" href="?page=<?php echo $page - 1 . $searchParam; ?>">← Vorige</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a
                href="?page=<?php echo $i . $searchParam; ?>"
                class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a class="page-btn" href="?page=<?php echo $page + 1 . $searchParam; ?>">Volgende →</a>
        <?php endif; ?>
    </div>



    <?php include __DIR__ . '/../../components/footer.php'; ?>

    <!-- Block User Modal -->
    <div id="blockUserModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Gebruiker Blokkeren</h2>
                <span class="close" onclick="closeBlockUserModal()">&times;</span>
            </div>
            <form id="blockUserForm" method="POST">
                <input type="hidden" name="action" value="block_user">
                <input type="hidden" id="block_user_id" name="user_id">
                <div class="form-group">
                    <label for="block_reason">Reden (optioneel)</label>
                    <textarea id="block_reason" name="reason" rows="4" placeholder="Voer hier de reden in waarom deze gebruiker wordt geblokkeerd..."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn-save">Blokkeren</button>
                    <button type="button" class="btn-cancel" onclick="closeBlockUserModal()">Annuleren</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Unblock User Modal -->
    <div id="unblockUserModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Gebruiker Deblokkeren</h2>
                <span class="close" onclick="closeUnblockUserModal()">&times;</span>
            </div>
            <form id="unblockUserForm" method="POST">
                <input type="hidden" name="action" value="unblock_user">
                <input type="hidden" id="unblock_user_id" name="user_id">
                <p>Weet je zeker dat je deze gebruiker wilt deblokkeren?</p>
                <div class="modal-actions">
                    <button type="submit" class="btn-save">Ja, Deblokkeren</button>
                    <button type="button" class="btn-cancel" onclick="closeUnblockUserModal()">Annuleren</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div id="deleteUserModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Gebruiker Verwijderen</h2>
                <span class="close" onclick="closeDeleteUserModal()">&times;</span>
            </div>
            <p>Weet je zeker dat je gebruiker <strong id="delete_username_display"></strong> wilt verwijderen? Dit kan niet ongedaan worden gemaakt!</p>
            <form id="deleteUserForm" method="POST">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" id="delete_user_id" name="user_id">
                <div class="modal-actions">
                    <button type="submit" class="btn-delete" style="background-color: #ef4444;">Verwijderen</button>
                    <button type="button" class="btn-cancel" onclick="closeDeleteUserModal()">Annuleren</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
    <script src="/js/session-guard.js"></script>
    <script>
        function openDeleteUserModal(userId, username) {
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('delete_username_display').textContent = username;
            document.getElementById('deleteUserModal').style.display = 'block';
        }

        function closeDeleteUserModal() {
            document.getElementById('deleteUserModal').style.display = 'none';
        }

        function closeEditUserModal() {
            // No longer used, but kept for safety if there are any rogue calls
            const modal = document.getElementById('editUserModal');
            if (modal) modal.style.display = 'none';
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

            const form = document.getElementById('editUserForm');
            const formData = new FormData(form);
            
            // Add action field for server-side processing
            formData.append('action', 'update_user');

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    closeEditUserModal();
                    location.reload();
                } else {
                    console.error('Error:', response.status);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });


        // Block user modal functions
        function openBlockUserModal(userId, username) {
            document.getElementById('block_user_id').value = userId;
            document.getElementById('blockUserModal').style.display = 'block';
        }

        function closeBlockUserModal() {
            document.getElementById('blockUserModal').style.display = 'none';
            document.getElementById('blockUserForm').reset();
        }

        // Handle block user form submission
        document.getElementById('blockUserForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const form = document.getElementById('blockUserForm');
            const formData = new FormData(form);

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const text = await response.text();
                
                if (response.ok) {
                    closeBlockUserModal();
                    setTimeout(() => location.reload(), 500);
                } else {
                    console.error('Error:', response.status);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });

        // Unblock user modal functions
        function openUnblockUserModal(userId, username) {
            document.getElementById('unblock_user_id').value = userId;
            document.getElementById('unblockUserModal').style.display = 'block';
        }

        function closeUnblockUserModal() {
            document.getElementById('unblockUserModal').style.display = 'none';
            document.getElementById('unblockUserForm').reset();
        }

        // Handle unblock user form submission
        document.getElementById('unblockUserForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const form = document.getElementById('unblockUserForm');
            const formData = new FormData(form);

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const text = await response.text();
                
                if (response.ok) {
                    closeUnblockUserModal();
                    setTimeout(() => location.reload(), 500);
                } else {
                    console.error('Error:', response.status);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    </script>
</body>

</html>