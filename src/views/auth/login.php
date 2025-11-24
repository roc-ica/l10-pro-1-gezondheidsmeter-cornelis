<?php
// Initialize variables
$message = null;
$errors = null;

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../controllers/authcontroller.php';
    $ctrl = new AuthController();
    $res = $ctrl->login($_POST);

    // Redirect on success
    if (!empty($res['success']) && $res['success'] === true) {
        // Check if user is admin
        $user = $res['user'] ?? null;
        if ($user && !empty($user->is_admin)) {
            // Admin redirect
            header('Location: ../../../admin/pages/home.php');
        } else {
            // Normal user redirect
            header('Location: ../../../pages/home.php');
        }
        exit;
    }

    // Handle errors
    $message = $res['message'] ?? null;
    $errors = $res['errors'] ?? null;
}
?>
<div class="auth-form login-form">
    <h2>Inloggen</h2>
    <?php if (!empty($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors) && is_array($errors)): ?>
        <ul class="errors">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="" novalidate>
        <div>
            <label for="username">Gebruikersnaam of e-mail</label>
            <input id="username" name="username" type="text" required maxlength="255" />
        </div>
        <div>
            <label for="password">Wachtwoord</label>
            <input id="password" name="password" type="password" required />
        </div>
        <div>
            <button type="submit">Inloggen</button>
        </div>
    </form>

    <p>Geen account? <a href="register.php">Registreer hier</a></p>
</div>
