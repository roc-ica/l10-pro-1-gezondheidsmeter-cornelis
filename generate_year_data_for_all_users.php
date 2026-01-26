<?php

/**
 * Script om een jaar aan data toe te voegen voor alle gebruikers (behalve admins)
 * 
 * Dit script genereert voor elke gebruiker (behalve admins):
 * - 365 dagen aan dagelijkse entries
 * - Antwoorden op alle vragen per dag
 * - Berekende gezondheidsscores
 * 
 * Gebruik: php generate_year_data_for_all_users.php
 */

require_once __DIR__ . '/src/config/database.php';

echo "\n";
echo "=====================================================\n";
echo "DATA GENERATOR: Een jaar aan data voor alle gebruikers\n";
echo "=====================================================\n\n";

try {
    $pdo = Database::getConnection();
    
    // Haal alle niet-admin gebruikers op
    $stmt = $pdo->prepare("SELECT id, username, display_name, email FROM users WHERE is_admin = 0");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "Geen gebruikers gevonden om data voor te genereren.\n";
        exit(0);
    }
    
    echo "Gevonden gebruikers:\n";
    foreach ($users as $user) {
        echo "  - {$user['username']} ({$user['display_name']}) - ID: {$user['id']}\n";
    }
    echo "\n";
    
    // Vraag om bevestiging
    echo "Dit script voegt een jaar aan data toe voor " . count($users) . " gebruiker(s).\n";
    echo "Dit kan enkele minuten duren. Wil je doorgaan? (ja/nee): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    
    if (strtolower($line) !== 'ja') {
        echo "Geannuleerd.\n";
        exit(0);
    }
    
    echo "\n";
    
    // Voor elke gebruiker, genereer data
    foreach ($users as $user) {
        echo "Genereren van data voor {$user['username']}...\n";
        generateYearDataForUser($pdo, $user['id'], $user['username']);
        echo "  ✓ Compleet!\n\n";
    }
    
    echo "=====================================================\n";
    echo "Alle data succesvol gegenereerd!\n";
    echo "=====================================================\n\n";
    
} catch (Exception $e) {
    echo "FOUT: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Genereer een jaar aan data voor een specifieke gebruiker
 */
function generateYearDataForUser($pdo, $userId, $username) {
    // Start een jaar geleden
    $startDate = new DateTime();
    $startDate->modify('-365 days');
    $endDate = new DateTime();
    
    $entriesCreated = 0;
    $answersCreated = 0;
    
    // Loop door het hele jaar - ELKE DAG
    $currentDate = clone $startDate;
    
    while ($currentDate <= $endDate) {
        $dateStr = $currentDate->format('Y-m-d');
        
        // Check of er al een entry bestaat voor deze dag
        $stmt = $pdo->prepare("SELECT id FROM daily_entries WHERE user_id = ? AND entry_date = ?");
        $stmt->execute([$userId, $dateStr]);
        
        if ($stmt->fetch()) {
            // Skip deze dag, er is al data
            $currentDate->modify('+1 day');
            continue;
        }
        
        // Genereer realistische variaties met patronen
        // Weekend heeft andere patronen dan doordeweeks
        $isWeekend = ($currentDate->format('N') >= 6); // 6=zaterdag, 7=zondag
        
        // Water: 4-12 glazen (weekend iets minder)
        $water = $isWeekend ? rand(5, 9) : rand(6, 12);
        
        // Beweging: 10-90 minuten (weekend meer)
        $exercise = $isWeekend ? rand(30, 90) : rand(15, 60);
        
        // Slaap: 5-9 uur (weekend meer)
        $sleep = $isWeekend ? rand(7, 9) : rand(6, 9);
        
        // Sociaal: 0-12 uur (weekend veel meer)
        $social = $isWeekend ? rand(4, 12) : rand(0, 5);
        
        // Alcohol/drugs: weekend vaker (20% kans doordeweeks, 40% weekend)
        $hasDrugs = $isWeekend ? (rand(1, 100) <= 40) : (rand(1, 100) <= 20);
        $drugsCount = $hasDrugs ? rand(1, 3) : 0;
        
        // Varieer de tijden van invullen (7-23 uur)
        $hour = rand(7, 23);
        $minute = rand(0, 59);
        $submittedAt = $dateStr . ' ' . sprintf('%02d:%02d:00', $hour, $minute);
        
        // Maak daily entry
        $stmt = $pdo->prepare("INSERT INTO daily_entries (user_id, entry_date, submitted_at) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $dateStr, $submittedAt]);
        $entryId = $pdo->lastInsertId();
        $entriesCreated++;
        
        // ==========================================
        // VRAAG 1: VOEDING (Water)
        // ==========================================
        $stmt = $pdo->prepare("INSERT INTO answers (entry_id, question_id, sub_question_id, answer_text, answer_sequence, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([$entryId, 1, null, $water >= 6 ? 'Ja' : 'Nee', 1, $submittedAt]);
        $stmt->execute([$entryId, 1, 2, $water, 1, date('Y-m-d H:i:s', strtotime($submittedAt) + 5)]);
        $answersCreated += 2;
        
        // ==========================================
        // VRAAG 3: BEWEGING
        // ==========================================
        $stmt->execute([$entryId, 3, null, $exercise > 20 ? 'Ja' : 'Nee', 1, date('Y-m-d H:i:s', strtotime($submittedAt) + 10)]);
        $stmt->execute([$entryId, 3, 4, $exercise, 1, date('Y-m-d H:i:s', strtotime($submittedAt) + 15)]);
        $answersCreated += 2;
        
        // ==========================================
        // VRAAG 5: SLAAP
        // ==========================================
        $stmt->execute([$entryId, 5, null, $sleep >= 7 ? 'Ja' : 'Nee', 1, date('Y-m-d H:i:s', strtotime($submittedAt) + 20)]);
        $stmt->execute([$entryId, 5, 6, $sleep, 1, date('Y-m-d H:i:s', strtotime($submittedAt) + 25)]);
        $answersCreated += 2;
        
        // ==========================================
        // VRAAG 7: VERSLAVINGEN (Alcohol/Drugs)
        // ==========================================
        if ($hasDrugs) {
            $stmt->execute([$entryId, 7, null, 'Ja', 1, date('Y-m-d H:i:s', strtotime($submittedAt) + 30)]);
            $stmt->execute([$entryId, 7, 8, $drugsCount, 1, date('Y-m-d H:i:s', strtotime($submittedAt) + 35)]);
            $answersCreated += 2;
        } else {
            $stmt->execute([$entryId, 7, null, 'Nee', 1, date('Y-m-d H:i:s', strtotime($submittedAt) + 30)]);
            $answersCreated += 1;
        }
        
        // ==========================================
        // VRAAG 9: SOCIAAL
        // ==========================================
        $stmt->execute([$entryId, 9, null, $social > 2 ? 'Ja' : 'Nee', 1, date('Y-m-d H:i:s', strtotime($submittedAt) + 40)]);
        $stmt->execute([$entryId, 9, 10, $social, 1, date('Y-m-d H:i:s', strtotime($submittedAt) + 45)]);
        $answersCreated += 2;
        
        // ==========================================
        // BEREKEN HEALTH SCORE
        // ==========================================
        $waterScore = min(($water / 8) * 100, 100);
        $exerciseScore = min(($exercise / 60) * 100, 100);
        
        if ($sleep >= 7 && $sleep <= 8) {
            $sleepScore = 100;
        } elseif ($sleep == 6 || $sleep == 9) {
            $sleepScore = 80;
        } else {
            $sleepScore = 60;
        }
        
        $drugsScore = $hasDrugs ? max(100 - ($drugsCount * 20), 30) : 100;
        $socialScore = min(($social / 8) * 100, 100);
        
        $overallScore = ($waterScore + $exerciseScore + $sleepScore + $drugsScore + $socialScore) / 5;
        $overallScore = max(0, min(100, $overallScore));
        
        // Bereken pillar scores
        $pillarScores = [
            '1' => round($waterScore, 2),  // Voeding
            '2' => round($exerciseScore, 2),  // Beweging
            '3' => round($sleepScore, 2),  // Slaap
            '4' => round($drugsScore, 2),  // Verslavingen
            '6' => round($socialScore, 2)   // Mentaal/Sociaal
        ];
        
        // Sla health score op
        $stmt = $pdo->prepare("
            INSERT INTO user_health_scores (user_id, score_date, overall_score, pillar_scores, created_at)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE overall_score = VALUES(overall_score), pillar_scores = VALUES(pillar_scores)
        ");
        $stmt->execute([
            $userId,
            $dateStr,
            round($overallScore, 2),
            json_encode($pillarScores),
            $submittedAt
        ]);
        
        // Volgende dag
        $currentDate->modify('+1 day');
    }
    
    echo "  - {$entriesCreated} dagboek entries aangemaakt\n";
    echo "  - {$answersCreated} antwoorden toegevoegd\n";
}
