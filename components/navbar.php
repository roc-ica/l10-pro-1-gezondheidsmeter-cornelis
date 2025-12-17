<?php
// Check if user is logged in by checking session
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['user']);

// Check if user is admin
$isAdmin = isset($_SESSION['user']) && !empty($_SESSION['user']->is_admin);

// Get current page
$currentPage = basename($_SERVER['PHP_SELF']);

// Check if we're on an auth page (login or register)
$isAuthPage = $currentPage === 'login.php' || $currentPage === 'register.php';

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
            <span class="brand-text">Gezondheids<span class="brand-meter">Meter</span></span>
        </div>
        <button class="navbar-toggle" aria-label="Toggle navigation" onclick="this.classList.toggle('active'); document.querySelector('.navbar-links').classList.toggle('active');">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <?php if (!$isAuthPage): ?>
        <div class="navbar-links">
            <a href="<?= $isAdmin ? 'index.php' : 'home.php' ?>" class="nav-link <?= isActive($isAdmin ? 'index.php' : 'home.php', $currentPage) ?>">Dashboard</a>
            <a href="vragen.php" class="nav-link <?= isActive('vragen.php', $currentPage) ?>">Vragen</a>
            <a href="geschiedenis.php" class="nav-link <?= isActive('geschiedenis.php', $currentPage) ?>">Geschiedenis</a>
            <?php if ($isLoggedIn): ?>
            <a href="account.php" class="nav-link <?= isActive('account.php', $currentPage) ?>">Account</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</nav>
