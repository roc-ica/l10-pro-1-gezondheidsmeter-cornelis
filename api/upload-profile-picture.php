<?php
session_start();
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/User.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Niet ingelogd']);
    exit;
}

if (!isset($_FILES['profile_picture'])) {
    echo json_encode(['success' => false, 'message' => 'Geen bestand geüpload']);
    exit;
}

$file = $_FILES['profile_picture'];
$userId = $_SESSION['user_id'];

// Validate file
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Ongeldig bestandstype. Alleen JPG, PNG, GIF en WEBP zijn toegestaan.']);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'Bestand is te groot. Maximaal 5MB.']);
    exit;
}

// Generate filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'user_' . $userId . '_' . time() . '.' . $extension;
$uploadDir = __DIR__ . '/../assets/uploads/profile_pictures/';
$targetPath = $uploadDir . $filename;

// Create dir if not exists (redundant but safe)
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    try {
        $pdo = Database::getConnection();
        
        $relativePath = 'assets/uploads/profile_pictures/' . $filename;
        
        // Attempt Update
        try {
            $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->execute([$relativePath, $userId]);
        } catch (PDOException $e) {
            // Check for "Column not found" error (Code 42S22 or 1054)
            if ($e->getCode() == '42S22' || $e->errorInfo[1] == 1054) {
                // Self-correction: Add column
                $pdo->exec("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL AFTER gender");
                
                // Retry Update
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->execute([$relativePath, $userId]);
            } else {
                throw $e; // Re-throw other errors
            }
        }
        
        // Update session user object if it exists
        if (isset($_SESSION['user'])) {
             // Re-fetch user to be sure
             $user = User::findByIdStatic($userId);
             $_SESSION['user'] = $user; 
        }

        echo json_encode(['success' => true, 'message' => 'Profielfoto succesvol geüpload', 'path' => $relativePath]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database fout: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Fout bij het opslaan van het bestand']);
}
