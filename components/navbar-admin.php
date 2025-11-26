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
            <span class="brand-text">Admin <span class="brand-meter">CMS</span></span>
        </div>
        <?php if ($isLoggedIn): ?>
        <div class="navbar-links">
            <a href="home.php" class="nav-link <?= isActive('home.php', $currentPage) ?>">Dashboard</a>
            <a href="vragen.php" class="nav-link <?= isActive('vragen.php', $currentPage) ?>">Vragen</a>
            <a href="gebruikers.php" class="nav-link <?= isActive('gebruikers.php', $currentPage) ?>">Gebruikers</a>
            <a href="analytics.php" class="nav-link <?= isActive('analytics.php', $currentPage) ?>">Analytics</a>
            <a href="../../pages/logout.php" class="nav-link">Uitloggen</a>
        </div>
        <?php endif; ?>
    </div>
</nav>
