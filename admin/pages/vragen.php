<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../src/views/auth/login.php');
    exit;
}

// Check if user is admin
require_once __DIR__ . '/../../src/models/User.php';
require_once __DIR__ . '/../../src/models/Question.php';
require_once __DIR__ . '/../../src/models/Pillar.php';

$user = \User::findByIdStatic($_SESSION['user_id']);

if (!$user || !$user->is_admin) {
    // Not admin, redirect to normal home
    header('Location: ../../pages/home.php');
    exit;
}

$username = $_SESSION['username'] ?? 'Admin';
$message = '';
$error = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_question') {
            $pillar_id = $_POST['pillar_id'] ?? null;
            $question_text = trim($_POST['question_text'] ?? '');

            if ($pillar_id && $question_text) {
                if (Question::add((int) $pillar_id, $question_text)) {
                    $message = "Vraag succesvol toegevoegd!";
                } else {
                    $error = "Fout bij toevoegen vraag.";
                }
            } else {
                $error = "Vul alle velden in.";
            }
        } elseif ($_POST['action'] === 'delete_question') {
            $question_id = $_POST['question_id'] ?? null;
            if ($question_id) {
                if (Question::delete((int) $question_id)) {
                    $message = "Vraag verwijderd.";
                } else {
                    $error = "Fout bij verwijderen.";
                }
            }
        } elseif ($_POST['action'] === 'edit_question') {
            $question_id = $_POST['question_id'] ?? null;
            $question_text = trim($_POST['question_text'] ?? '');
            if ($question_id && $question_text) {
                if (Question::update((int) $question_id, $question_text)) {
                    $message = "Vraag bijgewerkt.";
                } else {
                    $error = "Fout bij bijwerken.";
                }
            }
        }
    }
}

// Fetch Pillars for Dropdown
$pillars = Pillar::getAll();

// Fetch Questions
$questions = Question::getAllWithPillars();

?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Vragen - Gezondheidsmeter</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>

<body class="auth-page">
    <?php include __DIR__ . '/../../components/navbar-admin.php'; ?>

    <div class="dashboard-container">
        <div class="vragen-container">
            <h1 class="page-title">Vragen Beheer</h1>
            <p class="page-subtitle">Beheer de dagelijkse gezondheids vragen</p>

            <?php if ($message): ?>
                <div class="message" style="background-color: #dcfce7; color: #166534; border-color: #166534;">
                    <?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Add Question Form -->
            <div class="card">
                <h2 class="card-title">Nieuwe vraag toevoegen?</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_question">
                    <div class="add-form">
                        <select name="pillar_id" class="form-select" required>
                            <option value="" disabled selected>Catogorie</option>
                            <?php foreach ($pillars as $pillar): ?>
                                <option value="<?php echo $pillar->id; ?>"><?php echo htmlspecialchars($pillar->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="question_text" class="form-input" placeholder="Voer hier je vraag in"
                            required>
                    </div>
                    <button type="submit" class="btn-add">Vraag toevoegen</button>
                </form>
            </div>

            <!-- Questions List -->
            <?php foreach ($questions as $q): ?>
                <div class="card">
                    <div class="question-item">
                        <div class="question-content">
                            <span class="pillar-badge"
                                style="background-color: <?php echo htmlspecialchars($q->pillar_color ?? '#008000'); ?>;">
                                <?php echo htmlspecialchars($q->pillar_name); ?>
                            </span>
                            <span class="question-text"><?php echo htmlspecialchars($q->question_text); ?></span>
                        </div>
                        <div class="action-buttons">
                            <button class="btn-icon"
                                onclick="openEditModal(<?php echo $q->id; ?>, '<?php echo addslashes(htmlspecialchars($q->question_text)); ?>')">
                                <!-- Edit Icon (Pencil) -->
                                <svg class="icon-edit" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button class="btn-icon" onclick="openDeleteModal(<?php echo $q->id; ?>)">
                                <!-- Delete Icon (Trash) -->
                                <svg class="icon-delete" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path
                                        d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                    </path>
                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Vraag bewerken</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit_question">
                <input type="hidden" name="question_id" id="edit_question_id">
                <div style="margin-bottom: 15px;">
                    <label for="edit_question_text" style="display:block; margin-bottom:5px;">Vraag:</label>
                    <input type="text" name="question_text" id="edit_question_text" class="form-input"
                        style="width: 100%; box-sizing: border-box;" required>
                </div>
                <button type="submit" class="btn-add">Opslaan</button>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDeleteModal()">&times;</span>
            <h2>Vraag verwijderen</h2>
            <p>Weet je zeker dat je deze vraag wilt verwijderen?</p>
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete_question">
                <input type="hidden" name="question_id" id="delete_question_id">
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="closeDeleteModal()"
                        style="flex: 1; background-color: #666; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer;">Annuleren</button>
                    <button type="submit" class="btn-add" style="flex: 1; margin-top: 0;">Verwijderen</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, text) {
            document.getElementById('editModal').style.display = "block";
            document.getElementById('edit_question_id').value = id;
            document.getElementById('edit_question_text').value = text;
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = "none";
        }

        function openDeleteModal(id) {
            document.getElementById('deleteModal').style.display = "block";
            document.getElementById('delete_question_id').value = id;
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = "none";
        }

        // Close modal if clicked outside
        window.onclick = function (event) {
            var editModal = document.getElementById('editModal');
            var deleteModal = document.getElementById('deleteModal');
            if (event.target == editModal) {
                editModal.style.display = "none";
            }
            if (event.target == deleteModal) {
                deleteModal.style.display = "none";
            }
        }
    </script>

    <?php include __DIR__ . '/../../components/footer.php'; ?>
</body>

</html>