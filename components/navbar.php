<?php
// Check if user is logged in by checking session
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['user']);

// Get current page
$currentPage = basename($_SERVER['PHP_SELF']);

// Determine base path for links
if (defined('APP_BASE_PATH')) {
    $navBasePath = APP_BASE_PATH;
} else {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    // If we are in the 'pages' directory, go up one level to get the project root
    if (basename($scriptDir) === 'pages') {
        $navBasePath = dirname($scriptDir);
    } else {
        $navBasePath = $scriptDir;
    }
    $navBasePath = rtrim($navBasePath, '/\\');
}
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
            <a href="<?= htmlspecialchars($navBasePath); ?>/index.php" class="brand-link">
                <span class="brand-text">Gezondheids<span class="brand-meter">Meter</span></span>
            </a>
        </div>

        <div class="navbar-links">
            <a href="<?= htmlspecialchars($navBasePath); ?>/pages/index.php" class="nav-link <?= isActive('index.php', $currentPage) ?>">Dashboard</a>
            <a href="<?= htmlspecialchars($navBasePath); ?>/pages/vragen.php" class="nav-link <?= isActive('vragen.php', $currentPage) ?>">Vragen</a>
            <a href="<?= htmlspecialchars($navBasePath); ?>/pages/geschiedenis.php" class="nav-link <?= isActive('geschiedenis.php', $currentPage) ?>">Geschiedenis</a>
        </div>

        <?php if ($isLoggedIn): ?>
        <div class="navbar-actions">
                <a href="<?= htmlspecialchars($navBasePath); ?>/account.php" class="nav-link nav-link-cta <?= isActive('account.php', $currentPage) ?>">Account</a>
        </div>
        <?php endif; ?>
    </div>
</nav>
