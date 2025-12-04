<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../src/views/auth/login.php');
    exit;
}

// Check if user is admin
require_once __DIR__ . '/../../src/models/User.php';
$user = \User::findByIdStatic($_SESSION['user_id']);

if (!$user || !$user->is_admin) {
    // Not admin, redirect to normal home
    header('Location: ../../pages/home.php');
    exit;
}

$username = $_SESSION['username'] ?? 'Admin';
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
                <p>Totaal: 5 gebruikers</p>
            </div>
            <div class="dashboard-header-right">
                <a href="../pages/home.php" class="btn-naar-app">Naar App</a>
            </div>
        </div>
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
