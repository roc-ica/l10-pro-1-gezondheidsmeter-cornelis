<?php
// Check if user is logged in by checking session
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['user']);

// Get current page
$currentPage = basename($_SERVER['PHP_SELF']);

// Function to add active class
function isActive($page, $current) {
    return $page === $current ? 'active' : '';
}
?>
<nav class="navbar">
    <div class="navbar-container">
        <div class="navbar-brand">
            <span class="brand-text">Gezondheids<span class="brand-meter">Meter</span></span>
        </div>
        <?php if ($isLoggedIn): ?>
        <div class="navbar-links">
            <a href="home.php" class="nav-link <?= isActive('home.php', $currentPage) ?>">Dashboard</a>
            <a href="vragen.php" class="nav-link <?= isActive('vragen.php', $currentPage) ?>">Vragen</a>
            <a href="geschiedenis.php" class="nav-link <?= isActive('geschiedenis.php', $currentPage) ?>">Geschiedenis</a>
            <a href="instellingen.php" class="nav-link <?= isActive('instellingen.php', $currentPage) ?>">Instellingen</a>
            <a href="account.php" class="nav-link <?= isActive('account.php', $currentPage) ?>">Account</a>
        </div>
        <?php endif; ?>
    </div>
</nav>
