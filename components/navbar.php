<?php
// Check if user is logged in by checking session
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['user']);
?>
<nav class="navbar">
    <div class="navbar-container">
        <div class="navbar-brand">
            <span class="brand-text">Gezondheids<span class="brand-meter">Meter</span></span>
        </div>
        <?php if ($isLoggedIn): ?>
        <div class="navbar-links">
            <a href="#" class="nav-link">Dashboard</a>
            <a href="#" class="nav-link">Vragen</a>
            <a href="#" class="nav-link">Geschiedenis</a>
            <a href="#" class="nav-link">Instellingen</a>
            <a href="#" class="nav-link">Account</a>
        </div>
        <?php endif; ?>
    </div>
</nav>
