<?php
// Base URL van jouw project (BELANGRIJK!)
$baseUrl = "/l10-pro-1-gezondheidsmeter-cornelis";

// Check if user is logged in by checking session
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['user']);

// Get current page
$currentPage = basename($_SERVER['PHP_SELF']);

// Function to add active class
if (!function_exists('isActive')) {
    function isActive($page, $current) {
        return $page === $current ? 'active' : '';
    }
}
?>
<nav class="navbar">
    <div class="navbar-container">
        <div class="navbar-brand">
            <a href="<?= $baseUrl ?>/pages/index.php" class="brand-link">
                <span class="brand-text">Gezondheids<span class="brand-meter">Meter</span></span>
            </a>
        </div>

        <div class="navbar-links">
            <a href="<?= $baseUrl ?>/pages/index.php" class="nav-link <?= isActive('index.php', $currentPage) ?>">Dashboard</a>
            <a href="<?= $baseUrl ?>/pages/vragen.php" class="nav-link <?= isActive('vragen.php', $currentPage) ?>">Vragen</a>
            <a href="<?= $baseUrl ?>/pages/geschiedenis.php" class="nav-link <?= isActive('geschiedenis.php', $currentPage) ?>">Geschiedenis</a>
            <a href="<?= $baseUrl ?>/pages/instellingen.php" class="nav-link <?= isActive('instellingen.php', $currentPage) ?>">Instellingen</a>
        </div>

        <?php if ($isLoggedIn): ?>
        <div class="navbar-actions">
            <a href="<?= $baseUrl ?>/pages/account.php" class="nav-link nav-link-cta <?= isActive('account.php', $currentPage) ?>">Account</a>
        </div>
        <?php endif; ?>
    </div>
</nav>
