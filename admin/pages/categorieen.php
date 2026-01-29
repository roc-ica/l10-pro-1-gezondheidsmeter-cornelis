<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../src/views/auth/login.php');
    exit;
}

// Check if user is admin
require_once __DIR__ . '/../../src/models/User.php';
require_once __DIR__ . '/../../src/models/Pillar.php';
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

$message = '';
$error = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $pdo = Database::getConnection();

        if ($_POST['action'] === 'add_category') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $color = $_POST['color'] ?? '#000000';

            if ($name && $description) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO pillars (name, description, color) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $description, $color]);

                    // Removed specific logging for now as AdminActionLogger might not have category methods yet
                    // $logger->logCategoryCreate($adminUserId, $pdo->lastInsertId(), $name);

                    header('Location: categorieen.php?success=1');
                    exit;
                } catch (Exception $e) {
                    $error = "Fout bij toevoegen categorie: " . $e->getMessage();
                }
            } else {
                $error = "Vul alle verplichte velden in.";
            }

        } elseif ($_POST['action'] === 'delete_category') {
            $category_id = $_POST['category_id'] ?? null;

            if ($category_id) {
                try {
                    // Check if category is used
                    $check = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE pillar_id = ?");
                    $check->execute([$category_id]);
                    if ($check->fetchColumn() > 0) {
                        $error = "Kan categorie niet verwijderen omdat er vragen aan gekoppeld zijn.";
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM pillars WHERE id = ?");
                        $stmt->execute([$category_id]);

                        // $logger->logCategoryDelete($adminUserId, $category_id);

                        header('Location: categorieen.php?deleted=1');
                        exit;
                    }
                } catch (Exception $e) {
                    $error = "Fout bij verwijderen: " . $e->getMessage();
                }
            }

        } elseif ($_POST['action'] === 'edit_category') {
            $category_id = $_POST['category_id'] ?? null;
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $color = $_POST['color'] ?? '#000000';

            if ($category_id && $name && $description) {
                try {
                    $stmt = $pdo->prepare("UPDATE pillars SET name = ?, description = ?, color = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $color, $category_id]);

                    // $logger->logCategoryUpdate($adminUserId, $category_id, $name);

                    header('Location: categorieen.php?updated=1');
                    exit;
                } catch (Exception $e) {
                    $error = "Fout bij bijwerken: " . $e->getMessage();
                }
            } else {
                $error = "Vul alle velden in.";
            }
        }
    }
}

// Fetch Categories
$categories = Pillar::getAll();

// Check for parameters
if (isset($_GET['success']))
    $message = "Categorie succesvol toegevoegd!";
if (isset($_GET['deleted']))
    $message = "Categorie verwijderd.";
if (isset($_GET['updated']))
    $message = "Categorie bijgewerkt.";

?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Categorieën - Gezondheidsmeter</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        /* Extra styles specific to category management */
        .color-preview {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            display: inline-block;
            vertical-align: middle;
            margin-right: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .color-input-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        input[type="color"] {
            -webkit-appearance: none;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            padding: 0;
            background: none;
        }

        input[type="color"]::-webkit-color-swatch-wrapper {
            padding: 0;
        }

        input[type="color"]::-webkit-color-swatch {
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
        }
    </style>
</head>

<body class="auth-page">
    <?php include __DIR__ . '/../../components/navbar-admin.php'; ?>

    <div class="dashboard-container">
        <div class="vragen-container"> <!-- Reusing existing container class for layout -->
            <div class="dashboard-header">
                <div class="dashboard-header-left">
                    <h1>Categorieën Beheer</h1>
                    <p>Beheer de pijlers/categorieën van de gezondheidsmeter</p>
                </div>
                <div class="dashboard-header-right">
                    <a href="vragen.php" class="btn-naar-app" style="margin-right: 10px;">&larr; Terug</a>
                    <a href="../../pages/home.php" class="btn-naar-app">Naar App</a>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="message message-success">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Add Category Form -->
            <div class="card">
                <h2 class="card-title">Nieuwe categorie toevoegen</h2>
                <form method="POST" action="" class="add-form">
                    <input type="hidden" name="action" value="add_category">

                    <div class="form-group">
                        <label for="name" class="form-label">Naam:</label>
                        <input type="text" name="name" id="name" class="form-input" placeholder="Bijv: Voeding"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Beschrijving:</label>
                        <textarea name="description" id="description" class="form-input" rows="3"
                            placeholder="Beschrijving van deze categorie..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="color" class="form-label">Kleur:</label>
                        <div class="color-input-container">
                            <input type="color" name="color" id="color" value="#3b82f6">
                            <span class="text-sm opacity-70">Kies een kleur voor deze categorie</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-add">Categorie toevoegen</button>
                </form>
            </div>

            <!-- Categories List -->
            <div class="card">
                <h2 class="card-title">Bestaande Categorieën</h2>
                <div class="categories-list">
                    <?php foreach ($categories as $cat): ?>
                        <div class="question-pair-item" style="margin-bottom: 15px;">
                            <div class="question-pair-header">
                                <span class="pillar-badge"
                                    style="background-color: <?php echo htmlspecialchars($cat->color); ?>;">
                                    <?php echo htmlspecialchars($cat->name); ?>
                                </span>
                            </div>

                            <div class="question-pair-part">
                                <div class="question-part-label">Beschrijving</div>
                                <span class="question-text">
                                    <?php echo htmlspecialchars($cat->description); ?>
                                </span>
                            </div>

                            <div class="action-buttons">
                                <button class="btn-icon"
                                    onclick="openEditModal(<?php echo $cat->id; ?>, '<?php echo addslashes(htmlspecialchars($cat->name)); ?>', '<?php echo addslashes(htmlspecialchars($cat->description)); ?>', '<?php echo htmlspecialchars($cat->color); ?>')">
                                    <svg class="icon-edit" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>
                                <button class="btn-icon" onclick="openDeleteModal(<?php echo $cat->id; ?>)">
                                    <svg class="icon-delete" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path
                                            d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Categorie bewerken</h2>
            <form method="POST" action="" class="modal-form">
                <input type="hidden" name="action" value="edit_category">
                <input type="hidden" name="category_id" id="edit_category_id">

                <div class="form-group">
                    <label for="edit_name" class="form-label">Naam:</label>
                    <input type="text" name="name" id="edit_name" class="form-input" required>
                </div>

                <div class="form-group">
                    <label for="edit_description" class="form-label">Beschrijving:</label>
                    <textarea name="description" id="edit_description" class="form-input" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_color" class="form-label">Kleur:</label>
                    <div class="color-input-container">
                        <input type="color" name="color" id="edit_color">
                    </div>
                </div>

                <button type="submit" class="btn-add">Opslaan</button>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDeleteModal()">&times;</span>
            <h2>Categorie verwijderen</h2>
            <p>Weet je zeker dat je deze categorie wilt verwijderen? Dit kan alleen als er geen vragen meer aan
                gekoppeld zijn.</p>
            <form method="POST" action="" class="delete-form">
                <input type="hidden" name="action" value="delete_category">
                <input type="hidden" name="category_id" id="delete_category_id">
                <div class="delete-buttons">
                    <button type="button" onclick="closeDeleteModal()" class="btn-cancel">Annuleren</button>
                    <button type="submit" class="btn-add">Verwijderen</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, name, description, color) {
            document.getElementById('editModal').style.display = "block";
            document.getElementById('edit_category_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_color').value = color;
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = "none";
        }

        function openDeleteModal(id) {
            document.getElementById('deleteModal').style.display = "block";
            document.getElementById('delete_category_id').value = id;
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = "none";
        }

        window.onclick = function (event) {
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
            if (event.target == document.getElementById('deleteModal')) {
                closeDeleteModal();
            }
        }
    </script>
</body>

</html>