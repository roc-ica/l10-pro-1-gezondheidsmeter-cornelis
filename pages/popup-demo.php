<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Popup Demo - Gezondheidsmeter</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/popup.css">
    <style>
        body {
            background: #f2f0ef;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .demo-container {
            background: white;
            border-radius: 12px;
            padding: 48px;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .demo-header {
            text-align: center;
            margin-bottom: 48px;
        }

        .demo-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 12px 0;
            letter-spacing: -0.5px;
        }

        .demo-header p {
            color: #6b7280;
            font-size: 1.125rem;
        }

        .demo-section {
            margin-bottom: 32px;
        }

        .demo-section h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #0f172a;
            margin: 0 0 16px 0;
            padding-bottom: 12px;
            border-bottom: 2px solid #e5e7eb;
        }

        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        .demo-btn {
            padding: 14px 24px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            color: white;
            text-align: center;
        }

        .demo-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .demo-btn:active {
            transform: translateY(0);
        }

        .btn-success {
            background: #16a34a;
            box-shadow: 0 2px 6px rgba(22, 163, 74, 0.25);
        }

        .btn-success:hover {
            background: #15803d;
        }

        .btn-error {
            background: #dc2626;
            box-shadow: 0 2px 6px rgba(220, 38, 38, 0.25);
        }

        .btn-error:hover {
            background: #b91c1c;
        }

        .btn-warning {
            background: #f59e0b;
            box-shadow: 0 2px 6px rgba(245, 158, 11, 0.25);
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .btn-info {
            background: #3b82f6;
            box-shadow: 0 2px 6px rgba(59, 130, 246, 0.25);
        }

        .btn-info:hover {
            background: #2563eb;
        }

        .btn-confirm {
            background: #16a34a;
            box-shadow: 0 2px 6px rgba(22, 163, 74, 0.25);
        }

        .btn-confirm:hover {
            background: #15803d;
        }

        .code-example {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            margin-top: 12px;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            color: #374151;
            overflow-x: auto;
        }

        @media (max-width: 640px) {
            .demo-container {
                padding: 32px 24px;
            }

            .demo-header h1 {
                font-size: 2rem;
            }

            .demo-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <div class="demo-header">
            <h1>🎨 Popup Demonstratie</h1>
            <p>Moderne popups voor de Gezondheidsmeter</p>
        </div>

        <div class="demo-section">
            <h2>Basis Popups</h2>
            <div class="demo-grid">
                <button class="demo-btn btn-success" onclick="testSuccess()">
                    ✓ Success Popup
                </button>
                <button class="demo-btn btn-error" onclick="testError()">
                    ✕ Error Popup
                </button>
                <button class="demo-btn btn-warning" onclick="testWarning()">
                    ⚠ Warning Popup
                </button>
                <button class="demo-btn btn-info" onclick="testInfo()">
                    ℹ Info Popup
                </button>
            </div>
            <div class="code-example">
// Voorbeeld gebruik:<br>
showSuccess('Je gegevens zijn opgeslagen!', 'Gelukt!');<br>
showError('Er is iets misgegaan', 'Fout');<br>
showWarning('Let op deze wijziging', 'Waarschuwing');<br>
showInfo('Dit is een informatieve melding', 'Info');
            </div>
        </div>

        <div class="demo-section">
            <h2>Confirm Popup</h2>
            <div class="demo-grid">
                <button class="demo-btn btn-confirm" onclick="testConfirm()">
                    ? Confirm Dialog
                </button>
            </div>
            <div class="code-example">
// Voorbeeld gebruik met callbacks:<br>
showConfirm(<br>
&nbsp;&nbsp;'Weet je het zeker?',<br>
&nbsp;&nbsp;'Bevestigen',<br>
&nbsp;&nbsp;function() { console.log('Bevestigd!'); },<br>
&nbsp;&nbsp;function() { console.log('Geannuleerd'); }<br>
);
            </div>
        </div>

        <div class="demo-section">
            <h2>Praktische Voorbeelden</h2>
            <div class="demo-grid">
                <button class="demo-btn btn-success" onclick="testDataSaved()">
                    Gegevens Opslaan
                </button>
                <button class="demo-btn btn-warning" onclick="testDelete()">
                    Gegevens Wissen
                </button>
                <button class="demo-btn btn-info" onclick="testImport()">
                    Import Gelukt
                </button>
                <button class="demo-btn btn-error" onclick="testUploadFail()">
                    Upload Fout
                </button>
            </div>
        </div>

        <div class="demo-section">
            <h2>Features</h2>
            <ul style="color: #6b7280; line-height: 1.8; font-size: 1rem;">
                <li>🎨 <strong>Passend design</strong> - Perfect afgestemd op de app's groene kleurenschema</li>
                <li>🎯 Verschillende types: Success, Error, Warning, Info, Confirm</li>
                <li>🎭 Soepele animaties en transities</li>
                <li>⌨️ Keyboard support (ESC om te sluiten)</li>
                <li>📱 Volledig responsive voor mobiel</li>
                <li>✨ Kleur-gecodeerde iconen</li>
                <li>🔄 Callback support voor acties</li>
                <li>� Subtiel backdrop blur effect</li>
            </ul>
        </div>

        <div style="text-align: center; margin-top: 48px; padding-top: 32px; border-top: 2px solid #e5e7eb;">
            <a href="home.php" style="color: #16a34a; text-decoration: none; font-weight: 600; font-size: 1.125rem;">
                ← Terug naar Dashboard
            </a>
        </div>
    </div>

    <script src="../assets/js/popup.js"></script>
    <script>
        // Test functions
        function testSuccess() {
            showSuccess('Je profiel is succesvol bijgewerkt!', 'Gelukt!');
        }

        function testError() {
            showError('Er is een fout opgetreden bij het verwerken van je aanvraag.', 'Oeps!');
        }

        function testWarning() {
            showWarning('Deze actie kan niet ongedaan worden gemaakt.', 'Let op');
        }

        function testInfo() {
            showInfo('Je hebt vandaag nog geen check-in gedaan.', 'Herinnering');
        }

        function testConfirm() {
            showConfirm(
                'Weet je zeker dat je deze wijziging wilt doorvoeren?',
                'Bevestigen',
                function() {
                    showSuccess('Je keuze is bevestigd!', 'Bedankt!');
                },
                function() {
                    showInfo('Actie geannuleerd', 'Geannuleerd');
                }
            );
        }

        function testDataSaved() {
            showSuccess('Je gezondheidsgegevens zijn succesvol opgeslagen. Je kunt ze nu bekijken in je dashboard.', 'Data Opgeslagen!', function() {
                console.log('Gebruiker heeft de melding gezien');
            });
        }

        function testDelete() {
            showConfirm(
                'Weet je zeker dat je al je gezondheidsgegevens wilt wissen?\n\nDit omvat:\n- Alle vragenlijsten\n- Je voortgang\n- Je streak\n\nDeze actie kan NIET ongedaan worden!',
                'Gegevens Wissen',
                function() {
                    showConfirm(
                        'LAATSTE WAARSCHUWING!\n\nAlle data wordt permanent verwijderd.',
                        'Definitief Wissen',
                        function() {
                            showSuccess('Je gegevens zijn gewist', 'Voltooid');
                        }
                    );
                }
            );
        }

        function testImport() {
            showSuccess('Je hebt 42 gezondheidsrecords geïmporteerd van je backup bestand.', 'Import Gelukt!');
        }

        function testUploadFail() {
            showError('Het bestand kon niet worden geüpload. Controleer of het bestand kleiner is dan 5MB en probeer het opnieuw.', 'Upload Mislukt');
        }

        // Welcome message
        setTimeout(() => {
            showInfo('Klik op een knop om de verschillende popup types te testen!', 'Welkom bij de Demo', function() {
                console.log('Demo info gelezen');
            });
        }, 500);
    </script>
</body>
</html>
