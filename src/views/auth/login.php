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
        $user = $res['user'] ?? null;

        // Determine project base path dynamically (handles subfolders in XAMPP)
        $basePath = dirname($_SERVER['SCRIPT_NAME'], 3);
        if ($basePath === DIRECTORY_SEPARATOR || $basePath === '.') {
            $basePath = '';
        }
        $basePath = rtrim($basePath, '/\\');

        if (str_ends_with($basePath, '/src')) {
            $basePath = substr($basePath, 0, -4);
        }

        if ($user && !empty($user->is_admin)) {
            header('Location: ' . ($basePath ?: '') . '/admin/pages/home.php');
        } else {
            header('Location: ' . ($basePath ?: '') . '/pages/index.php');
        }
        exit;
    }

    // Handle errors
    $message = $res['message'] ?? null;
    $errors = $res['errors'] ?? null;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gezondheidsmeter - Inloggen</title>
    <link rel="stylesheet" href="../../../assets/css/style.css">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/images/icons/gm192x192.png">
</head>
<body class="auth-page">
    <?php include __DIR__ . '/../../../components/navbar.php'; ?>
    <div class="center-container">
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
                    <label for="username">Gebruikersnaam of E-mail</label>
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
    </div>
    <?php include __DIR__ . '/../../../components/footer.php'; ?>
    <script src="/js/pwa.js"></script>
</body>
</html>

