<?php

?>
<?php
// Simple register form. If the form is POSTed directly to this view,
// handle the registration here so the view can show messages immediately.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {
    require_once __DIR__ . '/../../controllers/authcontroller.php';
    $ctrl = new AuthController();

    // basic password confirmation check before calling controller
    if (isset($_POST['password'], $_POST['password_confirm']) && $_POST['password'] !== $_POST['password_confirm']) {
        $errors = ['Wachtwoorden komen niet overeen.'];
        $message = null;
    } else {
        $res = $ctrl->register($_POST);
        $message = $res['message'] ?? null;
        $errors = $res['errors'] ?? null;
    }
}
// If $message or $errors are set by the caller they will be used below.
?>
<div class="auth-form register-form">
    <h2>Registreren</h2>
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
            <label for="username">Gebruikersnaam</label>
            <input id="username" name="username" type="text" required maxlength="100" />
        </div>
        <div>
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" required maxlength="255" />
        </div>
        <div>
            <label for="password">Wachtwoord</label>
            <input id="password" name="password" type="password" required />
        </div>
        <div>
            <label for="password_confirm">Bevestig wachtwoord</label>
            <input id="password_confirm" name="password_confirm" type="password" required />
        </div>
        <div>
            <button type="submit" name="action" value="register">Registreren</button>
        </div>
    </form>
</div>
