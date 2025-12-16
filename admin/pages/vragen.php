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

$username = $_SESSION['username'] ?? 'Admin';
$message = '';
$error = '';

// Check for success redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = "Vraag succesvol toegevoegd!";
} elseif (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $message = "Vraag verwijderd.";
} elseif (isset($_GET['updated']) && $_GET['updated'] == '1') {
    $message = "Vraag bijgewerkt.";
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_question') {
            $pillar_id = $_POST['pillar_id'] ?? null;
            $main_question = trim($_POST['main_question'] ?? '');
            $secondary_question = trim($_POST['secondary_question'] ?? '');
            $is_drugs_question = isset($_POST['is_drugs_question']) ? 1 : 0;

            $input_type = $_POST['input_type'] ?? 'number';

            if ($pillar_id && $main_question && $secondary_question) {
                $pdo = Database::getConnection();
                try {
                    // Add main question (no parent)
                    $stmt = $pdo->prepare("
                        INSERT INTO questions 
                        (pillar_id, question_text, input_type, active, is_main_question, is_drugs_question, parent_question_id)
                        VALUES (?, ?, ?, 1, 1, ?, NULL)
                    ");
                    $stmt->execute([
                        (int) $pillar_id,
                        $main_question,
                        $input_type,
                        $is_drugs_question
                    ]);

                    $mainQuestionId = (int) $pdo->lastInsertId();

                    // Add secondary question (linked to main via parent_question_id)
                    $stmt = $pdo->prepare("
                        INSERT INTO questions 
                        (pillar_id, question_text, input_type, active, is_main_question, is_drugs_question, parent_question_id)
                        VALUES (?, ?, ?, 1, 0, 0, ?)
                    ");
                    $stmt->execute([
                        (int) $pillar_id,
                        $secondary_question,
                        $input_type,
                        $mainQuestionId
                    ]);

                    $secondaryQuestionId = (int) $pdo->lastInsertId();

                    // Log the action for both questions
                    $logger->logQuestionCreate($adminUserId, $mainQuestionId, [
                        'pillar_id' => $pillar_id,
                        'question_text' => $main_question,
                        'is_main' => 1,
                        'is_drugs_question' => $is_drugs_question
                    ]);
                    $logger->logQuestionCreate($adminUserId, $secondaryQuestionId, [
                        'pillar_id' => $pillar_id,
                        'question_text' => $secondary_question,
                        'is_main' => 0,
                        'is_drugs_question' => 0
                    ]);

                    header('Location: vragen.php?success=1');
                    exit;
                } catch (Exception $e) {
                    $error = "Fout bij toevoegen vraag: " . $e->getMessage();
                }
            } else {
                $error = "Vul alle velden in (categorie, hoofdvraag en vervolgvraag).";
            }
        } elseif ($_POST['action'] === 'delete_question') {
            $question_id = $_POST['question_id'] ?? null;
            $secondary_question_id = $_POST['secondary_question_id'] ?? null;

            if ($question_id) {
                $pdo = Database::getConnection();
                try {
                    // Delete main question
                    if (Question::delete((int) $question_id)) {
                        $logger->logQuestionDelete($adminUserId, (int) $question_id);
                    }

                    // Delete secondary question if it exists
                    if ($secondary_question_id) {
                        if (Question::delete((int) $secondary_question_id)) {
                            $logger->logQuestionDelete($adminUserId, (int) $secondary_question_id);
                        }
                    }

                    // Redirect to prevent duplicate submission on refresh
                    header('Location: vragen.php?deleted=1');
                    exit;
                } catch (Exception $e) {
                    $error = "Fout bij verwijderen: " . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] === 'edit_question') {
            $question_id = $_POST['question_id'] ?? null;
            $secondary_question_id = $_POST['secondary_question_id'] ?? null;
            $main_question_text = trim($_POST['main_question_text'] ?? '');
            $secondary_question_text = trim($_POST['secondary_question_text'] ?? '');
            $pillar_id = $_POST['pillar_id'] ?? null;

            if ($question_id && $main_question_text && $secondary_question_text && $pillar_id) {
                $pdo = Database::getConnection();
                try {
                    // Update main question
                    $stmt = $pdo->prepare("
                        UPDATE questions 
                        SET question_text = ?, pillar_id = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$main_question_text, (int) $pillar_id, (int) $question_id]);

                    $logger->logQuestionUpdate($adminUserId, (int) $question_id, [
                        'question_text' => $main_question_text,
                        'pillar_id' => $pillar_id
                    ]);

                    // Update secondary question if it exists
                    if ($secondary_question_id) {
                        $stmt = $pdo->prepare("
                            UPDATE questions 
                            SET question_text = ?, pillar_id = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$secondary_question_text, (int) $pillar_id, (int) $secondary_question_id]);

                        $logger->logQuestionUpdate($adminUserId, (int) $secondary_question_id, [
                            'question_text' => $secondary_question_text,
                            'pillar_id' => $pillar_id
                        ]);
                    }

                    header('Location: vragen.php?updated=1');
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

// Fetch Pillars for Dropdown
$pillars = Pillar::getAll();

// Fetch only MAIN questions (those with no parent)
$pdo = Database::getConnection();
$stmt = $pdo->prepare("
    SELECT q.*, p.name as pillar_name, p.color as pillar_color
    FROM questions q
    JOIN pillars p ON q.pillar_id = p.id
    WHERE q.active = 1 AND q.is_main_question = 1 AND q.parent_question_id IS NULL
    ORDER BY q.pillar_id, q.id
");
$stmt->execute();
$mainQuestions = $stmt->fetchAll(PDO::FETCH_OBJ);

// For each main question, fetch its linked secondary question
foreach ($mainQuestions as &$mainQ) {
    $stmtSec = $pdo->prepare("
        SELECT * FROM questions
        WHERE parent_question_id = ? AND is_main_question = 0 AND active = 1
        LIMIT 1
    ");
    $stmtSec->execute([$mainQ->id]);
    $mainQ->secondary = $stmtSec->fetch(PDO::FETCH_OBJ);
}

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
                <div class="message message-success">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Add Question Form -->
            <div class="card">
                <h2 class="card-title">Nieuwe vraag toevoegen?</h2>
                <form method="POST" action="" id="questionForm">
                    <input type="hidden" name="action" value="add_question">

                    <!-- Category Selection -->
                    <div class="form-group">
                        <label for="pillar_select" class="form-label">Categorie:</label>
                        <select name="pillar_id" class="form-select" id="pillar_select" required>
                            <option value="" disabled selected>Categorie selecteren</option>
                            <?php foreach ($pillars as $pillar): ?>
                                <option value="<?php echo $pillar->id; ?>"><?php echo htmlspecialchars($pillar->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Input Type Selection -->
                    <div class="form-group">
                        <label for="input_type_select" class="form-label">Type Invoer:</label>
                        <select name="input_type" class="form-select" id="input_type_select" required>
                            <option value="number" selected>Getal (bijv. 8, 30)</option>
                            <option value="text">Tekst (bijv. Softdrugs/Harddrugs)</option>
                        </select>
                    </div>

                    <!-- Main Question Input -->
                    <div class="form-group">
                        <label for="main_question_input" class="form-label">Hoofdvraag:</label>
                        <input type="text" name="main_question" id="main_question_input"
                            class="form-input form-input-large" placeholder="Voer hier je hoofdvraag in" required>
                    </div>

                    <!-- Secondary Question Input -->
                    <div class="form-group">
                        <label for="secondary_question_input" class="form-label">Vervolgvraag:</label>
                        <input type="text" name="secondary_question" id="secondary_question_input"
                            class="form-input form-input-large" placeholder="Voer hier je vervolgvraag in" required>
                    </div>

                    <!-- Drugs Question Checkbox (Only for Verslavingen) -->
                    <div id="drugs_checkbox" class="drugs-checkbox-container" style="display: none;">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_drugs_question" value="1" class="checkbox-input">
                            <span class="checkbox-text checkbox-text-drugs">⚠️ Dit is de drugs vraag (Wat voor drugs
                                hebt u gebruikt?)</span>
                        </label>
                        <p class="drugs-checkbox-help">Alleen beschikbaar voor Verslavingen categorie</p>
                    </div>

                    <button type="submit" class="btn-add">Vraag toevoegen</button>
                </form>
            </div>

            <!-- Questions List -->
            <?php foreach ($mainQuestions as $q): ?>
                <div class="card">
                    <div class="question-pair-item">
                        <div class="question-pair-header">
                            <span class="pillar-badge"
                                style="background-color: <?php echo htmlspecialchars($q->pillar_color ?? '#008000'); ?>;">
                                <?php echo htmlspecialchars($q->pillar_name); ?>
                            </span>
                        </div>

                        <!-- Main Question Part -->
                        <div class="question-pair-part">
                            <div class="question-part-label">Hoofdvraag</div>
                            <span class="question-text"><?php echo htmlspecialchars($q->question_text); ?></span>
                        </div>

                        <!-- Secondary Question Part -->
                        <?php if ($q->secondary): ?>
                            <div class="question-pair-part">
                                <div class="question-part-label">Vervolgvraag</div>
                                <span class="question-text"><?php echo htmlspecialchars($q->secondary->question_text); ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <button class="btn-icon"
                                onclick="openEditModal(<?php echo $q->id; ?>, '<?php echo addslashes(htmlspecialchars($q->question_text)); ?>', <?php echo $q->pillar_id; ?>, <?php echo $q->secondary ? $q->secondary->id : 'null'; ?>, '<?php echo $q->secondary ? addslashes(htmlspecialchars($q->secondary->question_text)) : ''; ?>')">
                                <!-- Edit Icon (Pencil) -->
                                <svg class="icon-edit" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button class="btn-icon"
                                onclick="openDeleteModal(<?php echo $q->id; ?>, <?php echo $q->secondary ? $q->secondary->id : 'null'; ?>)">
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
            <h2>Vraagpaar bewerken</h2>
            <form method="POST" action="" class="modal-form">
                <input type="hidden" name="action" value="edit_question">
                <input type="hidden" name="question_id" id="edit_question_id">
                <input type="hidden" name="secondary_question_id" id="edit_secondary_question_id">

                <div class="form-group">
                    <label for="edit_pillar_id" class="form-label">Categorie:</label>
                    <select name="pillar_id" id="edit_pillar_id" class="form-select" required>
                        <option value="" disabled>Selecteer categorie</option>
                        <?php foreach ($pillars as $pillar): ?>
                            <option value="<?php echo $pillar->id; ?>"><?php echo htmlspecialchars($pillar->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_main_question_text" class="form-label">Hoofdvraag:</label>
                    <input type="text" name="main_question_text" id="edit_main_question_text"
                        class="form-input form-input-large" required>
                </div>

                <div class="form-group">
                    <label for="edit_secondary_question_text" class="form-label">Vervolgvraag:</label>
                    <input type="text" name="secondary_question_text" id="edit_secondary_question_text"
                        class="form-input form-input-large" required>
                </div>

                <button type="submit" class="btn-add">Opslaan</button>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDeleteModal()">&times;</span>
            <h2>Vraagpaar verwijderen</h2>
            <p>Weet je zeker dat je dit vraagpaar (beide delen) wilt verwijderen? Dit kan niet ongedaan gemaakt worden.
            </p>
            <form method="POST" action="" class="delete-form">
                <input type="hidden" name="action" value="delete_question">
                <input type="hidden" name="question_id" id="delete_question_id">
                <input type="hidden" name="secondary_question_id" id="delete_secondary_question_id">
                <div class="delete-buttons">
                    <button type="button" onclick="closeDeleteModal()" class="btn-cancel">Annuleren</button>
                    <button type="submit" class="btn-add">Verwijderen</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Show/hide drugs question checkbox based on pillar selection
        document.getElementById('pillar_select').addEventListener('change', function () {
            const drugsCheckbox = document.getElementById('drugs_checkbox');
            // Verslavingen = pillar 4
            if (this.value == '4') {
                drugsCheckbox.style.display = 'block';
            } else {
                drugsCheckbox.style.display = 'none';
                document.querySelector('input[name="is_drugs_question"]').checked = false;
            }
        });

        function openEditModal(mainId, mainText, pillarId, secondaryId, secondaryText) {
            document.getElementById('editModal').style.display = "block";
            document.getElementById('edit_question_id').value = mainId;
            document.getElementById('edit_secondary_question_id').value = secondaryId || '';
            document.getElementById('edit_main_question_text').value = mainText;
            document.getElementById('edit_secondary_question_text').value = secondaryText || '';
            document.getElementById('edit_pillar_id').value = pillarId;
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = "none";
        }

        function openDeleteModal(mainId, secondaryId) {
            document.getElementById('deleteModal').style.display = "block";
            document.getElementById('delete_question_id').value = mainId;
            document.getElementById('delete_secondary_question_id').value = secondaryId || '';
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