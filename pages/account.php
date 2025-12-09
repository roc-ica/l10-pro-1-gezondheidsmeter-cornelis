<?php
session_start();


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../src/views/auth/login.php');
    exit;
}

// Get user data from database
require_once __DIR__ . '/../src/models/User.php';
$user = User::findByIdStatic($_SESSION['user_id']);

if (!$user) {
    // User not found, redirect to login
    header('Location: ../src/views/auth/login.php');
    exit;
}

$email = htmlspecialchars($user->email);
$username = htmlspecialchars($user->username);
$birthdate = htmlspecialchars($user->birthdate ?? '');
$gender = htmlspecialchars($user->gender ?? '');
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account - Gezondheidsmeter</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                        <div class="identity-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
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
                    <p><?= htmlspecialchars($birthdate) ?></p>
                    <p><?= htmlspecialchars($gender) ?></p>
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
                    <div class="score-value">85%</div>
                </div>
                <div class="summary-item">
                    <div class="score-label">Beantwoorde Vragen</div>
                    <div class="score-value">85%</div>
                </div>
                <div class="summary-item">
                    <div class="streak-label">Huidige Streak</div>
                    <div class="streak-value">7 Dagen</div>
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
                        <div class="score-label"><strong>Dagelijkse Herinneringen</strong></div>
                        <div class="score-value">Ontvang herinneringen om vragen in te vullen</div>
                    </div>
                    <div>
                        <label for="notifications"></label>
                        <input type="checkbox" id="notifications" name="notifications" checked>
                    </div>
                </div>
                <div class="inst-item">
                    <div>
                        <div class="score-label"><strong>Email rapportages</strong></div>
                        <div class="score-value">Ontvang wekelijkse gezondheids rapportages</div>
                    </div>
                    <div>
                        <label for="email-reports"></label>
                        <input type="checkbox" id="email-reports" name="email-reports" checked>
                    </div>
                </div>
                <div class="inst-item inst-item-danger">
                    <div>
                        <div class="score-label"><strong>Gezondheids gegevens wissen?</strong></div>
                        <div class="score-value">Let op, je gezondheids gegevens kunnen niet meer terug gezet worden</div>
                    </div>
                    <div>
                        <button class="btn-delete-data" id="deleteHealthDataBtn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
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
        document.getElementById('deleteHealthDataBtn').addEventListener('click', async function() {
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
                const response = await fetch('../api/delete-health-data.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Je gezondheidsgegevens zijn succesvol gewist.');
                    window.location.reload();
                } else {
                    alert('Er is een fout opgetreden: ' + (data.message || 'Onbekende fout'));
                }
            } catch (error) {
                console.error('Error deleting health data:', error);
                alert('Er is een fout opgetreden bij het wissen van je gegevens.');
            }
        });
    </script>

    <!-- Edit Modal -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Gegevens Bewerken</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form id="editForm" method="POST">
                <div class="form-group">
                    <label for="edit_email">E-mailadres</label>
                    <input type="email" id="edit_email" name="email" value="<?= htmlspecialchars($user->email) ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit_birthdate">Geboortedatum</label>
                    <input type="date" id="edit_birthdate" name="birthdate" value="<?= htmlspecialchars($user->birthdate) ?>">
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
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Handle form submission
        document.getElementById('editForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(document.getElementById('editForm'));
            const data = {
                email: formData.get('email'),
                birthdate: formData.get('birthdate'),
                gender: formData.get('gender')
            };

            try {
                const response = await fetch('/api/update-profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    alert('Profiel succesvol bijgewerkt!');
                    closeEditModal();
                    location.reload();
                } else {
                    alert('Fout: ' + (result.message || 'Onbekende fout'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Er is een fout opgetreden.');
            }
        });
    </script>
</body>

</html>