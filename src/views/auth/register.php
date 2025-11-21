<?php
// Initialize variables
$message = null;
$fieldErrors = [];

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../controllers/authcontroller.php';
    $ctrl = new AuthController();
    $res = $ctrl->register($_POST);

    // Redirect on success
    if (!empty($res['success']) && $res['success'] === true) {
        header('Location: login.php');
        exit;
    }

    // Handle errors
    $message = $res['message'] ?? null;
    if (!empty($res['errors'])) {
        if (!empty($res['errors']['username_exists'])) {
            $fieldErrors['username'] = 'Gebruikersnaam bestaat al.';
        }
        if (!empty($res['errors']['email_exists'])) {
            $fieldErrors['email'] = 'E-mail bestaat al.';
        }
        if (!empty($res['errors']['password_confirm'])) {
            $fieldErrors['password_confirm'] = 'Wachtwoorden komen niet overeen.';
        }
    }
}
?>
<div class="auth-form register-form">
    <h2>Registreren</h2>
    <?php if (!empty($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post" action="" novalidate>
        <div>
            <?php if (!empty($fieldErrors['username'])): ?>
                <div class="field-error"><?= htmlspecialchars($fieldErrors['username']) ?></div>
            <?php endif; ?>
            <label for="username">Gebruikersnaam</label>
            <input id="username" name="username" type="text" required maxlength="100" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" />
        </div>
        <div>
            <?php if (!empty($fieldErrors['email'])): ?>
                <div class="field-error"><?= htmlspecialchars($fieldErrors['email']) ?></div>
            <?php endif; ?>
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" required maxlength="255" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
        </div>
        <div>
            <label for="password">Wachtwoord</label>
            <input id="password" name="password" type="password" required />
        </div>
        <div>
            <?php if (!empty($fieldErrors['password_confirm'])): ?>
                <div class="field-error"><?= htmlspecialchars($fieldErrors['password_confirm']) ?></div>
            <?php endif; ?>
            <label for="password_confirm">Bevestig wachtwoord</label>
            <input id="password_confirm" name="password_confirm" type="password" required />
        </div>
        <div>
            <button type="submit">Registreren</button>
        </div>
    </form>

    <p>Al een account? <a href="login.php">Log hier in</a></p>
</div>
