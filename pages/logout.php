<?php
session_start();

if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../src/config/database.php';
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('UPDATE users SET remember_token = NULL, remember_expires = NULL WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
}

// Clear cookie
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}

session_destroy();

header('Location: ../src/views/auth/login.php');
exit;
