<?php
// Check if user is logged in by checking session
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['user']);

// Get current page
$currentPage = basename($_SERVER['PHP_SELF']);

// Determine base path for links
$navBasePath = defined('APP_BASE_PATH')
    ? APP_BASE_PATH
    : rtrim(dirname($_SERVER['SCRIPT_NAME'], 1), '/\\');
$navBasePath = ($navBasePath === '/' || $navBasePath === '\\') ? '' : $navBasePath;

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
            <a href="<?= htmlspecialchars($navBasePath); ?>/pages/index.php" class="brand-link">
                <span class="brand-text">Gezondheids<span class="brand-meter">Meter</span></span>
            </a>
        </div>

        <div class="navbar-links">
            <a href="<?= htmlspecialchars($navBasePath); ?>/pages/index.php" class="nav-link <?= isActive('index.php', $currentPage) ?>">Dashboard</a>
            <a href="<?= htmlspecialchars($navBasePath); ?>/pages/vragen.php" class="nav-link <?= isActive('vragen.php', $currentPage) ?>">Vragen</a>
            <a href="<?= htmlspecialchars($navBasePath); ?>/pages/geschiedenis.php" class="nav-link <?= isActive('geschiedenis.php', $currentPage) ?>">Geschiedenis</a>
            <a href="<?= htmlspecialchars($navBasePath); ?>/pages/instellingen.php" class="nav-link <?= isActive('instellingen.php', $currentPage) ?>">Instellingen</a>
        </div>

        <?php if ($isLoggedIn): ?>
        <div class="navbar-actions">
                <a href="<?= htmlspecialchars($navBasePath); ?>/account.php" class="nav-link nav-link-cta <?= isActive('account.php', $currentPage) ?>">Account</a>
        </div>
        <?php endif; ?>
    </div>
</nav>
