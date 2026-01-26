<?php
/**
 * Script om te controleren welke gebruikers er in de database staan
 * XAMPP versie (gebruikt localhost in plaats van 'db')
 */

// Direct database connectie voor XAMPP
$host = 'localhost';
$db = 'gezondheidsmeter';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    echo "==============================================\n";
    echo "ALLE GEBRUIKERS IN DE DATABASE\n";
    echo "==============================================\n\n";
    
    $stmt = $pdo->query("SELECT id, username, email, display_name, is_admin, is_active, created_at FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "⚠️  GEEN GEBRUIKERS GEVONDEN!\n";
        echo "De database lijkt leeg te zijn.\n";
        echo "\nMogelijke oplossingen:\n";
        echo "1. Voer eerst de database init.sql uit\n";
        echo "2. Voer test_data.sql uit om testgebruikers aan te maken\n";
    } else {
        echo "Gevonden gebruikers: " . count($users) . "\n\n";
        
        foreach ($users as $user) {
            echo "ID: {$user['id']}\n";
            echo "  Username: {$user['username']}\n";
            echo "  Email: {$user['email']}\n";
            echo "  Display Name: " . ($user['display_name'] ?? 'N/A') . "\n";
            echo "  Is Admin: " . ($user['is_admin'] ? 'Ja' : 'Nee') . "\n";
            echo "  Is Active: " . ($user['is_active'] ? 'Ja' : 'Nee') . "\n";
            echo "  Created: {$user['created_at']}\n";
            echo "\n";
        }
        
        // Test wachtwoord verificatie
        echo "==============================================\n";
        echo "WACHTWOORD TEST\n";
        echo "==============================================\n\n";
        
        $testPassword = 'Test123!';
        $expectedHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        
        echo "Test wachtwoord: $testPassword\n";
        echo "Verwachte hash: $expectedHash\n\n";
        
        // Test of de hash klopt
        if (password_verify($testPassword, $expectedHash)) {
            echo "✓ Wachtwoord hash verificatie: SUCCESS\n\n";
        } else {
            echo "✗ Wachtwoord hash verificatie: FAILED\n\n";
        }
        
        // Controleer wachtwoorden van gebruikers
        echo "Wachtwoord check per gebruiker:\n\n";
        foreach ($users as $user) {
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $row = $stmt->fetch();
            
            if ($row && password_verify($testPassword, $row['password_hash'])) {
                echo "✓ {$user['username']}: Wachtwoord 'Test123!' werkt\n";
            } else {
                echo "✗ {$user['username']}: Wachtwoord 'Test123!' werkt NIET\n";
                if ($row) {
                    echo "  Hash in DB: {$row['password_hash']}\n";
                }
            }
        }
    }
    
    echo "\n==============================================\n";
    echo "\nOPLOSSINGEN als je niet kunt inloggen:\n";
    echo "1. Voer test_data.sql uit in phpMyAdmin om testgebruikers aan te maken\n";
    echo "2. Of gebruik het script generate_year_data_all_users.sql\n";
    echo "3. Controleer of MySQL draait in XAMPP Control Panel\n";
    echo "==============================================\n";
    
} catch (PDOException $e) {
    echo "DATABASE VERBINDING FOUT!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "Controleer of:\n";
    echo "1. MySQL draait in XAMPP Control Panel\n";
    echo "2. De database 'gezondheidsmeter' bestaat\n";
    echo "3. Je kunt inloggen via phpMyAdmin (http://localhost/phpmyadmin)\n";
}
