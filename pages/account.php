<?php
session_start();


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../src/views/auth/login.php');
    exit;
}

// Get user data from database
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/services/UserProfileService.php';
require_once __DIR__ . '/../src/services/HealthDataService.php';
require_once __DIR__ . '/../src/models/UserHealthHistory.php';

$user = User::findByIdStatic($_SESSION['user_id']);

// Handle POST requests for actions
$action = $_POST['action'] ?? null;
$responseData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'update_profile') {
        // Update profile
        $profileService = new UserProfileService();
        $responseData = $profileService->updateProfile($_SESSION['user_id'], $_POST);
        
        // Handle profile picture upload from modal if provided
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $profileService->uploadProfilePicture($_SESSION['user_id'], $_FILES['profile_picture']);
            if ($uploadResult['success']) {
                $responseData['message'] .= ' en profielfoto bijgewerkt';
            } else {
                $responseData['success'] = false;
                $responseData['message'] .= ' maar foto upload mislukt: ' . $uploadResult['message'];
            }
        }

        // Refresh user data after update
        if ($responseData['success']) {
            $user = User::findByIdStatic($_SESSION['user_id']);
            // Update session data
            $_SESSION['email'] = $user->email;
            $_SESSION['birthdate'] = $user->birthdate;
            $_SESSION['geslacht'] = $user->geslacht;
        }
    } elseif ($action === 'upload_profile_picture') {
        // Upload profile picture
        $profileService = new UserProfileService();
        $responseData = $profileService->uploadProfilePicture($_SESSION['user_id'], $_FILES['profile_picture'] ?? []);
        
        // Refresh user data after upload
        if ($responseData['success']) {
            $user = User::findByIdStatic($_SESSION['user_id']);
        }
    } elseif ($action === 'delete_health_data') {
        // Delete all health data
        $healthService = new HealthDataService();
        $responseData = $healthService->deleteAllHealthData($_SESSION['user_id']);
    } elseif ($action === 'import_health_data') {
        // Import health data
        if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
            $fileContent = file_get_contents($_FILES['import_file']['tmp_name']);
            $healthService = new HealthDataService();
            $responseData = $healthService->importHealthData($_SESSION['user_id'], $fileContent);
        } else {
            $responseData = ['success' => false, 'message' => 'Geen bestand geüpload'];
        }
    } elseif ($action === 'export_health_data') {
        // Export health data - handled separately at the end
        $healthService = new HealthDataService();
        $exportResult = $healthService->exportHealthData($_SESSION['user_id']);
        
        if ($exportResult['success']) {
            // Send as JSON file download
            $exportData = $exportResult['data'];
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="health_export_' . $_SESSION['user_id'] . '_' . date('Ymd') . '.json"');
            echo json_encode($exportData, JSON_PRETTY_PRINT);
            exit;
        } else {
            $responseData = $exportResult;
        }
    }

    // If it's an AJAX request (has action and isn't export), return JSON
    if ($responseData && $action !== 'export_health_data') {
        header('Content-Type: application/json');
        echo json_encode($responseData);
        exit;
    }
}

if (!$user) {
    // User not found, redirect to login
    header('Location: ../src/views/auth/login.php');
    exit;
}

$email = htmlspecialchars($user->email ?? '');
$username = htmlspecialchars($user->username ?? '');
$birthdate = htmlspecialchars($user->birthdate ?? '');
$geslacht = htmlspecialchars($user->geslacht ?? '');
$profilePicture = $user->profile_picture ?? '';
if ($profilePicture) {
    // Ensure path is relative to pages/ directory
    // If it starts with slash, remove it first
    $profilePicture = ltrim($profilePicture, '/');
    $profilePicture = '../' . $profilePicture;
}
// Debug output
// echo "DEBUG: Profile Picture Value: [" . $profilePicture . "]<br>";
$profilePicture = htmlspecialchars($profilePicture);

// Use UserHealthHistory for statistics
$history = new UserHealthHistory($user->id);
$avgScore = round((float)($history->getAverageScore(365) ?? 0)); // Average of last year
$responseRate = $history->getResponseRate();
$currentStreak = $history->getStreak();
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account - Gezondheidsmeter</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
    <link rel="stylesheet" href="../assets/css/popup.css">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/images/icons/gm192x192.png">
</head>

<body class="auth-page">
    <?php include __DIR__ . '/../components/navbar.php'; ?>

    <div class="dashboard-container">
        <div class="identity-bar">
            <h2>Mijn Account</h2>
            <div class="identity-card">
                <div class="identity-info1">
                    <div class="identity-details">
                        <div class="identity-icon" style="position: relative; cursor: pointer; overflow: hidden; <?php if($profilePicture) echo "background-image: url('$profilePicture?v=" . time() . "'); background-size: cover; background-position: center; padding: 0;"; ?>" onclick="document.getElementById('profilePicInput').click()">
                            <?php if (!$profilePicture): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <?php endif; ?>
                            <div class="icon-overlay">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                    <circle cx="12" cy="13" r="4"></circle>
                                </svg>
                            </div>
                        </div>
                        <input type="file" id="profilePicInput" accept="image/*" style="display: none;" onchange="handleProfileUpload(this)">
                        <div class="identity-username">
                            <?= htmlspecialchars($username) ?>
                        </div>
                    </div>
                    <div class="identity-button">
                        <button onclick="openEditModal()">Bewerken</button>
                    </div>
                </div>
                <div class="identity-info2">
                    <p>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        <span><?= htmlspecialchars($email) ?></span>
                    </p>
                    <p>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <span><?= htmlspecialchars($birthdate ?: 'Niet opgegeven') ?></span>
                    </p>
                    <p>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a5 5 0 1 0 5 5 5 5 0 0 0-5-5z"></path><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path></svg>
                        <?php
                            $genderDisplay = 'Niet opgegeven';
                            if ($geslacht === 'man') $genderDisplay = 'Man';
                            elseif ($geslacht === 'vrouw') $genderDisplay = 'Vrouw';
                            elseif ($geslacht === 'anders') $genderDisplay = 'Anders';
                        ?>
                        <span><?= htmlspecialchars($genderDisplay) ?></span>
                    </p>
                </div>
            </div>
        </div>

        <div class="account-summary">
            <div class="summary-header">
                <h3>Gezondheid Samenvatting</h3>
            </div>
            <div class="summary-content">
                <div class="summary-item">
                    <div class="score-label">Gemiddelde Score</div>
                    <div class="score-value"><?= $avgScore ?>/100</div>
                </div>
                <div class="summary-item">
                    <div class="score-label">Invulpercentage</div>
                    <div class="score-value"><?= $responseRate ?>%</div>
                </div>
                <div class="summary-item">
                    <div class="streak-label">Huidige Streak</div>
                    <div class="streak-value"><?= $currentStreak ?> Dag(en)</div>
                </div>
            </div>
        </div>

        <div class="account-inst">
            <div class="inst-header">
                <h3>Instellingen</h3>
            </div>
            <div class="inst-content">
                <div class="inst-item">
                    <div>
                        <div class="score-label"><strong>Mijn gegevens exporteren</strong></div>
                        <div class="score-value">Download een kopie van al je gezondheidsdata (JSON)</div>
                    </div>
                    <div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="export_health_data">
                            <button type="submit" class="btn-export-data">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: text-bottom; margin-right: 4px;">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                                Downloaden
                            </button>
                        </form>
                    </div>
                </div>
                <!-- Import Section -->
                <div class="inst-item">
                    <div>
                        <div class="score-label"><strong>Gegevens importeren</strong></div>
                        <div class="score-value">Herstel data vanuit een eerder gemaakte backup</div>
                    </div>
                    <div>
                        <input type="file" id="importFile" accept=".json" style="display: none;" onchange="handleImport(this)">
                        <button class="btn-export-data" style="background-color: #6366f1;" onclick="document.getElementById('importFile').click()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: text-bottom; margin-right: 4px;">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            Importeren
                        </button>
                    </div>
                </div>
                <div class="inst-item inst-item-danger">
                    <div>
                        <div class="score-label"><strong>Gezondheids gegevens wissen?</strong></div>
                        <div class="score-value">Let op, je gezondheids gegevens kunnen niet meer terug gezet worden
                        </div>
                    </div>
                    <div>
                        <button class="btn-delete-data" id="deleteHealthDataBtn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <polyline points="3 6 5 6 21 6" />
                                <path
                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                            </svg>
                            Wissen
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <button class="account-logout" onclick="window.location.href='logout.php'">Afmelden?</button>
    </div>

    <?php include __DIR__ . '/../components/footer.php'; ?>

    <script src="/js/pwa.js"></script>
    <script src="/js/session-guard.js"></script>
    <script src="../assets/js/popup.js"></script>
    <script>
        // Delete health data handler
        document.getElementById('deleteHealthDataBtn').addEventListener('click', async function () {
            showConfirm(
                'Weet je zeker dat je al je gezondheidsgegevens wilt wissen?\n\nDit omvat:\n- Alle ingevulde vragenlijsten\n- Je antwoorden\n- Je voortgang en streak\n\nDeze actie kan NIET ongedaan worden gemaakt!',
                'Gezondheidsgegevens Wissen',
                async function() {
                    // Double confirmation for safety
                    showConfirm(
                        'LAATSTE WAARSCHUWING!\n\nAlle data wordt permanent verwijderd.\n\nKlik OK om definitief te wissen.',
                        'Definitief Wissen',
                        async function() {
                            await deleteHealthData();
                        }
                    );
                }
            );
        });

        async function deleteHealthData() {

            try {
                const formData = new FormData();
                formData.append('action', 'delete_health_data');

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const text = await response.text();
                
                // Check if response is JSON (success) or redirect
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showSuccess('Je gezondheidsgegevens zijn succesvol gewist.', 'Gelukt!', () => {
                            window.location.reload();
                        });
                    } else {
                        showError('Er is een fout opgetreden: ' + (data.message || 'Onbekende fout'));
                    }
                } catch (e) {
                    // If not JSON, it might be a redirect, just reload
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error deleting health data:', error);
                showError('Er is een fout opgetreden bij het wissen van je gegevens.');
            }
        }

        // Handle Import
        async function handleImport(input) {
            if (!input.files || input.files.length === 0) return;

            const file = input.files[0];
            
            showConfirm(
                `Weet je zeker dat je "${file.name}" wilt importeren?\nDit voegt gegevens toe aan je huidige data.`,
                'Gegevens Importeren',
                async function() {
                    await performImport(file);
                },
                function() {
                    input.value = ''; // Reset input on cancel
                }
            );
        }

        async function performImport(file) {
            const input = document.getElementById('importFile');

            try {
                const formData = new FormData();
                formData.append('action', 'import_health_data');
                formData.append('import_file', file);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showSuccess(result.message, 'Import Gelukt!', () => {
                        window.location.reload();
                    });
                } else {
                    showError('Import fout: ' + (result.message || 'Onbekende fout'));
                }
            } catch (error) {
                console.error('Import error:', error);
                showError('Er is een technische fout opgetreden tijdens het importeren.');
            } finally {
                input.value = ''; // Reset for next use
            }
        }

        // Handle Profile Picture Upload
        async function handleProfileUpload(input) {
            if (!input.files || input.files.length === 0) return;

            const file = input.files[0];

            try {
                // Show some loading indication if desired
                const iconDiv = document.querySelector('.identity-icon');
                const originalOpacity = iconDiv.style.opacity;
                iconDiv.style.opacity = '0.5';

                const formData = new FormData();
                formData.append('action', 'upload_profile_picture');
                formData.append('profile_picture', file);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Refresh page to show new image
                    location.reload();
                } else {
                    showError('Upload mislukt: ' + (result.message || 'Onbekende fout'));
                    iconDiv.style.opacity = originalOpacity;
                }
            } catch (error) {
                console.error('Error uploading profile picture:', error);
                showError('Er is een technische fout opgetreden.');
                document.querySelector('.identity-icon').style.opacity = '1';
            } finally {
                input.value = ''; // Reset input
            }
        }
    </script>

    <!-- Edit Modal -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Gegevens Bewerken</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form id="editForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-group" style="text-align: center; margin-bottom: 20px;">
                    <div class="profile-preview-container" style="position: relative; width: 100px; height: 100px; margin: 0 auto 10px; border-radius: 50%; overflow: hidden; border: 2px solid #e2e8f0; cursor: pointer;" onclick="document.getElementById('edit_profile_picture').click()">
                        <img id="modal_pfp_preview" src="<?= $profilePicture ?: '../assets/images/default-avatar.png' ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <div style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.5); color: white; font-size: 10px; padding: 2px 0;">Wijzigen</div>
                    </div>
                    <input type="file" id="edit_profile_picture" name="profile_picture" accept="image/*" style="display: none;" onchange="previewImage(this)">
                </div>
                <div class="form-group">
                    <label for="edit_email">E-mailadres</label>
                    <input type="email" id="edit_email" name="email" value="<?= htmlspecialchars($user->email ?? '') ?>"
                        required>
                </div>
                <div class="form-group">
                    <label for="edit_birthdate">Geboortedatum</label>
                    <input type="date" id="edit_birthdate" name="birthdate"
                        value="<?= htmlspecialchars($user->birthdate ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="edit_geslacht">Geslacht</label>
                    <select id="edit_geslacht" name="geslacht">
                        <option value="">-- Selecteer --</option>
                        <option value="man" <?= $user->geslacht === 'man' ? 'selected' : '' ?>>Man</option>
                        <option value="vrouw" <?= $user->geslacht === 'vrouw' ? 'selected' : '' ?>>Vrouw</option>
                        <option value="anders" <?= $user->geslacht === 'anders' ? 'selected' : '' ?>>Anders</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn-save">Opslaan</button>
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Annuleren</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/js/session-guard.js"></script>
    <script>
        function openEditModal() {
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('modal_pfp_preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Close modal when clicking outside of it
        window.onclick = function (event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Handle form submission
        document.getElementById('editForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const form = document.getElementById('editForm');
            const formData = new FormData(form);

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                // Try to parse as JSON
                let result;
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    result = await response.json();
                    if (result.success) {
                        showSuccess('Profiel succesvol bijgewerkt!', 'Gelukt!', () => {
                            closeEditModal();
                            location.reload();
                        });
                    } else {
                        showError('Fout: ' + (result.message || 'Onbekende fout'));
                    }
                } else {
                    // If not JSON, assume success and reload
                    showSuccess('Profiel succesvol bijgewerkt!', 'Gelukt!', () => {
                        closeEditModal();
                        location.reload();
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Er is een fout opgetreden.');
            }
        });
    </script>
</body>

</html>