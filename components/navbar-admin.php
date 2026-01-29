<?php
// Check if user is logged in by checking session
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['user']);

// Get current page
$currentPage = basename($_SERVER['PHP_SELF']);

// Function to add active class
function isActive($page, $current)
{
    return $page === $current ? 'active' : '';
}
?>
<nav class="navbar">
    <div class="navbar-container">
        <div class="navbar-brand">
            <span class="brand-text">Admin <span class="brand-meter">CMS</span></span>
        </div>
        <?php if ($isLoggedIn): ?>
            <!-- Hamburger Menu Button (Mobile Only) -->
            <button class="navbar-toggle" aria-label="Toggle navigation"
                onclick="this.classList.toggle('active'); document.querySelector('.navbar-links').classList.toggle('active');">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <!-- Navigation Links -->
            <div class="navbar-links">
                <a href="home.php" class="nav-link <?= isActive('home.php', $currentPage) ?>">Dashboard</a>
                <a href="vragen.php" class="nav-link <?= isActive('vragen.php', $currentPage) ?>">Vragen</a>
                <a href="categorieen.php" class="nav-link <?= isActive('categorieen.php', $currentPage) ?>">Categorieën</a>
                <a href="gebruikers.php" class="nav-link <?= isActive('gebruikers.php', $currentPage) ?>">Gebruikers</a>
                <a href="analytics.php" class="nav-link <?= isActive('analytics.php', $currentPage) ?>">Analytics</a>
                <a href="../../pages/logout.php" class="nav-link">Uitloggen</a>
            </div>
        <?php endif; ?>
    </div>
</nav>