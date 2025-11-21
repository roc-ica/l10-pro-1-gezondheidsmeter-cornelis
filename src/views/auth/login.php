<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    require_once __DIR__ . '/../../controllers/authcontroller.php';
    $ctrl = new AuthController();
    $res = $ctrl->login($_POST);
    $message = $res['message'] ?? null;
    // controller returns structured result; errors key is optional
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
            <button type="submit" name="action" value="login">Inloggen</button>
        </div>
    </form>

    <p>Geen account? <a href="register.php">Registreer hier</a></p>
</div>
