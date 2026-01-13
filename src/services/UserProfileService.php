<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class UserProfileService
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Update user profile (email, birthdate, gender)
     */
    public function updateProfile(int $userId, array $data): array
    {
        // Validate email
        $email = trim($data['email'] ?? '');
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Ongeldig e-mailadres'];
        }

        // Check if email is already taken by another user
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Dit e-mailadres is al in gebruik'];
        }

        // Get user
        $user = User::findByIdStatic($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'Gebruiker niet gevonden'];
        }

        // Prepare update data
        $updateData = [
            'email' => $email,
            'birthdate' => !empty($data['birthdate']) ? trim($data['birthdate']) : null,
            'geslacht' => !empty($data['geslacht']) ? trim($data['geslacht']) : (!empty($data['gender']) ? trim($data['gender']) : null)
        ];

        // Update user
        $result = $user->update($updateData);

        return $result;
    }

    /**
     * Upload profile picture for user
     */
    public function uploadProfilePicture(int $userId, array $fileData): array
    {
        // Validate file exists
        if (!isset($fileData['tmp_name']) || !isset($fileData['error'])) {
            return ['success' => false, 'message' => 'Geen bestand geüpload'];
        }

        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Fout bij bestandsupload'];
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($fileData['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Ongeldig bestandstype. Alleen JPG, PNG, GIF en WEBP zijn toegestaan.'];
        }

        // Validate file size (5MB max)
        $maxSize = 5 * 1024 * 1024;
        if ($fileData['size'] > $maxSize) {
            return ['success' => false, 'message' => 'Bestand is te groot. Maximaal 5MB.'];
        }

        // Generate filename
        $extension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $userId . '_' . time() . '.' . $extension;
        $uploadDir = __DIR__ . '/../../assets/uploads/profile_pictures/';
        $targetPath = $uploadDir . $filename;

        // Create dir if not exists
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Move uploaded file
        if (!move_uploaded_file($fileData['tmp_name'], $targetPath)) {
            return ['success' => false, 'message' => 'Fout bij het opslaan van het bestand'];
        }

        try {
            $relativePath = 'assets/uploads/profile_pictures/' . $filename;

            // Update user with new profile picture
            $stmt = $this->pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->execute([$relativePath, $userId]);

            return ['success' => true, 'message' => 'Profielfoto succesvol geüpload', 'path' => $relativePath];
        } catch (PDOException $e) {
            // Cleanup the uploaded file on database error
            unlink($targetPath);
            return ['success' => false, 'message' => 'Database fout: ' . $e->getMessage()];
        }
    }
}
?>
