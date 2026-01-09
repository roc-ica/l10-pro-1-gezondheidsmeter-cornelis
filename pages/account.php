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

$user = User::findByIdStatic($_SESSION['user_id']);

// Handle POST requests for actions
$action = $_POST['action'] ?? null;
$responseData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'update_profile') {
        // Update profile
        $profileService = new UserProfileService();
        $responseData = $profileService->updateProfile($_SESSION['user_id'], $_POST);
        
        // Refresh user data after update
        if ($responseData['success']) {
            $user = User::findByIdStatic($_SESSION['user_id']);
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
}

if (!$user) {
    // User not found, redirect to login
    header('Location: ../src/views/auth/login.php');
    exit;
}

$email = htmlspecialchars($user->email ?? '');
$username = htmlspecialchars($user->username ?? '');
$birthdate = htmlspecialchars($user->birthdate ?? '');
$gender = htmlspecialchars($user->gender ?? '');
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

// Initialize Database connection for stats
$pdo = Database::getConnection();

// 1. Calculate Average Health Score (from user_health_scores)
$stmt = $pdo->prepare("SELECT AVG(overall_score) FROM user_health_scores WHERE user_id = ?");
$stmt->execute([$user->id]);
$avgScore = round((float)$stmt->fetchColumn());

// 2. Calculate Response Rate (Submitted Days / Days since Signup)
$createdAt = new DateTime($user->created_at ?? 'now');
$now = new DateTime();
$daysSinceSignup = max(1, $now->diff($createdAt)->days + 1);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM daily_entries WHERE user_id = ? AND submitted_at IS NOT NULL");
$stmt->execute([$user->id]);
$submittedEntries = $stmt->fetchColumn();

// Calculate percentage, max 100%
$responseRate = min(100, round(($submittedEntries / $daysSinceSignup) * 100));

// 3. Calculate Current Streak
$stmt = $pdo->prepare("
    SELECT entry_date FROM daily_entries 
    WHERE user_id = ? AND submitted_at IS NOT NULL
    ORDER BY entry_date DESC
");
$stmt->execute([$user->id]);
$allEntries = $stmt->fetchAll(PDO::FETCH_COLUMN);

$currentStreak = 0;
$checkDate = new DateTime(); // Today
// Check if most recent entry is today, if not check if it was yesterday (allow 1 day grace for "current" streak display in some contexts, but strict streak means today must be done or yesterday done)
// We will follow strict logic: Streak counts back from TODAY. If today not done but yesterday done, streak is preserved but count might not include today.
// To handle "I haven't done it TODAY yet, but I did yesterday", we usually check if today is done. If not, check yesterday.
// Home.php logic:
// foreach ($allEntries as $entryDate) { ... if ($entry->format('Y-m-d') === $checkDate->format('Y-m-d')) ... }
// We will reuse that for consistency.

$checkDate = new DateTime();
$streakFoundToday = false;
$streakFoundYesterday = false;

// First loop to see if we have valid streak activity
foreach ($allEntries as $entryDate) {
    $entry = new DateTime($entryDate);
    if ($entry->format('Y-m-d') === $checkDate->format('Y-m-d')) {
        $currentStreak++;
        $checkDate->modify('-1 day');
        $streakFoundToday = true;
    } elseif ($currentStreak === 0 && $entry->format('Y-m-d') === (new DateTime('-1 day'))->format('Y-m-d')) {
        // Allow streak to start from yesterday if today is missing
        $currentStreak++;
        $checkDate->modify('-1 day'); // Move check to yesterday
        $checkDate->modify('-1 day'); // Move check to day before yesterday for next loop
        $streakFoundYesterday = true;
    } elseif ($currentStreak > 0 && $entry->format('Y-m-d') === $checkDate->format('Y-m-d')) {
        $currentStreak++;
        $checkDate->modify('-1 day');
    } else {
        // Gap found
        // If we haven't started counting yet, continue searching? No, streak is continuous.
        if ($currentStreak > 0) break;
    }
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account - Gezondheidsmeter</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
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
                    <p><?= htmlspecialchars($email) ?></p>
                    <p><?= htmlspecialchars($birthdate ?: 'Niet opgegeven') ?></p>
                    <p><?= htmlspecialchars($gender ?: 'Niet opgegeven') ?></p>
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
                    <div class="streak-value"><?= $currentStreak ?> Dagen</div>
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
    <script>
        // Delete health data handler
        document.getElementById('deleteHealthDataBtn').addEventListener('click', async function () {
            const confirmed = confirm('Weet je zeker dat je al je gezondheidsgegevens wilt wissen?\n\nDit omvat:\n- Alle ingevulde vragenlijsten\n- Je antwoorden\n- Je voortgang en streak\n\nDeze actie kan NIET ongedaan worden gemaakt!');

            if (!confirmed) {
                return;
            }

            // Double confirmation for safety
            const doubleConfirm = confirm('LAATSTE WAARSCHUWING!\n\nAlle data wordt permanent verwijderd.\n\nKlik OK om definitief te wissen.');

            if (!doubleConfirm) {
                return;
            }

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
                        alert('Je gezondheidsgegevens zijn succesvol gewist.');
                        window.location.reload();
                    } else {
                        alert('Er is een fout opgetreden: ' + (data.message || 'Onbekende fout'));
                    }
                } catch (e) {
                    // If not JSON, it might be a redirect, just reload
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error deleting health data:', error);
                alert('Er is een fout opgetreden bij het wissen van je gegevens.');
            }
        });

        // Handle Import
        async function handleImport(input) {
            if (!input.files || input.files.length === 0) return;

            const file = input.files[0];
            const confirmImport = confirm(`Weet je zeker dat je "${file.name}" wilt importeren?\nDit voegt gegevens toe aan je huidige data.`);
            
            if (!confirmImport) {
                input.value = ''; // Reset input
                return;
            }

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
                    alert(result.message);
                    window.location.reload();
                } else {
                    alert('Import fout: ' + (result.message || 'Onbekende fout'));
                }
            } catch (error) {
                console.error('Import error:', error);
                alert('Er is een technische fout opgetreden tijdens het importeren.');
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
                    alert('Upload mislukt: ' + (result.message || 'Onbekende fout'));
                    iconDiv.style.opacity = originalOpacity;
                }
            } catch (error) {
                console.error('Error uploading profile picture:', error);
                alert('Er is een technische fout opgetreden.');
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
            <form id="editForm" method="POST">
                <input type="hidden" name="action" value="update_profile">
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
                    <label for="edit_gender">Geslacht</label>
                    <select id="edit_gender" name="gender">
                        <option value="">-- Selecteer --</option>
                        <option value="Male" <?= $user->gender === 'Male' ? 'selected' : '' ?>>Man</option>
                        <option value="Female" <?= $user->gender === 'Female' ? 'selected' : '' ?>>Vrouw</option>
                        <option value="Other" <?= $user->gender === 'Other' ? 'selected' : '' ?>>Anders</option>
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
                        alert('Profiel succesvol bijgewerkt!');
                        closeEditModal();
                        location.reload();
                    } else {
                        alert('Fout: ' + (result.message || 'Onbekende fout'));
                    }
                } else {
                    // If not JSON, assume success and reload
                    alert('Profiel succesvol bijgewerkt!');
                    closeEditModal();
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Er is een fout opgetreden.');
            }
        });
    </script>
</body>

</html>