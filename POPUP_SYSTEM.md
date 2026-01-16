# 🎨 Popup Systeem - Gezondheidsmeter

Een modern en aantrekkelijk popup/modal systeem ter vervanging van de standaard browser `alert()` en `confirm()` dialogen.

## ✨ Features

- 🎯 **Verschillende Types**: Success, Error, Warning, Info, en Confirm dialogen
- 🎭 **Soepele Animaties**: Moderne CSS animaties en transities
- 📱 **Volledig Responsive**: Werkt perfect op desktop en mobiel
- ⌨️ **Keyboard Support**: ESC om te sluiten, focus management
- 🎨 **Kleur-gecodeerd**: Visueel onderscheid tussen verschillende bericht types
- 🔄 **Callback Support**: Voer acties uit na bevestiging of annulering
- 🎪 **Backdrop Blur**: Modern glasmorfisme effect

## 🚀 Installatie

1. Voeg de CSS toe aan je `<head>`:
```html
<link rel="stylesheet" href="../assets/css/popup.css">
```

2. Voeg de JavaScript toe voor de `</body>` tag:
```html
<script src="../assets/js/popup.js"></script>
```

## 📖 Gebruik

### Success Popup
Toon een succesbericht aan de gebruiker.

```javascript
showSuccess('Je gegevens zijn opgeslagen!', 'Gelukt!');

// Met callback
showSuccess('Profiel bijgewerkt!', 'Gelukt!', function() {
    window.location.reload();
});
```

### Error Popup
Toon een foutmelding aan de gebruiker.

```javascript
showError('Er is iets misgegaan', 'Fout');

// Met callback
showError('Upload mislukt', 'Oeps!', function() {
    console.log('Gebruiker heeft foutmelding gezien');
});
```

### Warning Popup
Toon een waarschuwing aan de gebruiker.

```javascript
showWarning('Deze actie kan niet ongedaan worden', 'Let op');
```

### Info Popup
Toon een informatief bericht aan de gebruiker.

```javascript
showInfo('Je hebt vandaag nog geen check-in gedaan', 'Herinnering');
```

### Confirm Dialog
Vraag bevestiging van de gebruiker met twee knoppen.

```javascript
showConfirm(
    'Weet je zeker dat je wilt doorgaan?',
    'Bevestigen',
    function() {
        // Bevestigd - doe iets
        console.log('Gebruiker heeft bevestigd');
    },
    function() {
        // Geannuleerd - optioneel
        console.log('Gebruiker heeft geannuleerd');
    }
);
```

## 🔧 API Referentie

### `showSuccess(message, title, onConfirm)`
- **message** (string): Het bericht om te tonen
- **title** (string, optioneel): De titel van de popup (default: "Gelukt!")
- **onConfirm** (function, optioneel): Callback functie bij klikken op OK

### `showError(message, title, onConfirm)`
- **message** (string): Het foutbericht om te tonen
- **title** (string, optioneel): De titel van de popup (default: "Fout")
- **onConfirm** (function, optioneel): Callback functie bij klikken op OK

### `showWarning(message, title, onConfirm)`
- **message** (string): De waarschuwing om te tonen
- **title** (string, optioneel): De titel van de popup (default: "Let op")
- **onConfirm** (function, optioneel): Callback functie bij klikken op OK

### `showInfo(message, title, onConfirm)`
- **message** (string): De informatie om te tonen
- **title** (string, optioneel): De titel van de popup (default: "Info")
- **onConfirm** (function, optioneel): Callback functie bij klikken op OK

### `showConfirm(message, title, onConfirm, onCancel)`
- **message** (string): Het bericht om te tonen
- **title** (string, optioneel): De titel van de popup (default: "Bevestigen")
- **onConfirm** (function, optioneel): Callback functie bij klikken op bevestigen
- **onCancel** (function, optioneel): Callback functie bij klikken op annuleren

## 📋 Voorbeelden

### Simpel Succesbericht
```javascript
// Na succesvol opslaan
showSuccess('Je profiel is bijgewerkt!');
```

### Error met Reload
```javascript
// Bij een fout met pagina refresh
showError('Er is iets misgegaan', 'Fout', function() {
    location.reload();
});
```

### Delete Bevestiging
```javascript
// Dubbele bevestiging voor gevaarlijke actie
showConfirm(
    'Weet je zeker dat je dit wilt verwijderen?',
    'Verwijderen',
    function() {
        // Bevestigd - toon tweede waarschuwing
        showConfirm(
            'LAATSTE WAARSCHUWING! Dit kan niet ongedaan worden.',
            'Definitief Verwijderen',
            function() {
                // Daadwerkelijk verwijderen
                deleteData();
            }
        );
    }
);
```

### Async Operaties
```javascript
// Bij async operaties
async function saveData() {
    try {
        const response = await fetch('/api/save', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message, 'Gelukt!', () => {
                window.location.reload();
            });
        } else {
            showError(result.message, 'Fout');
        }
    } catch (error) {
        showError('Er is een technische fout opgetreden', 'Fout');
    }
}
```

## 🎨 Customization

### Kleuren Aanpassen
De kleuren kunnen aangepast worden in `assets/css/popup.css`:

```css
.popup-icon.success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.popup-icon.error {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}
```

### Animaties Aanpassen
Pas de animatie snelheid aan:

```css
.popup-overlay {
    transition: opacity 0.3s ease, visibility 0.3s ease;
}

.popup-container {
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
```

## 🧪 Demo

Bezoek `/pages/popup-demo.php` voor een live demonstratie van alle popup types.

## 📝 Changelog

### v1.0.0 (2026-01-15)
- ✨ Initiële release
- 🎨 Success, Error, Warning, Info, en Confirm dialogen
- 📱 Responsive design
- ⌨️ Keyboard support
- 🎭 Soepele animaties

## 💡 Tips

1. **Gebruik de juiste kleuren**: Success voor positieve acties, Error voor fouten, Warning voor waarschuwingen
2. **Houd berichten kort**: Lange teksten zijn moeilijk te lezen in een popup
3. **Gebruik callbacks**: Voor een betere gebruikerservaring na acties
4. **Test op mobiel**: Zorg dat popups goed werken op kleine schermen

## 🐛 Troubleshooting

### Popup toont niet
- Controleer of de CSS en JS bestanden correct zijn gelinkt
- Controleer de browser console voor errors
- Zorg dat het script na de DOM geladen is

### Styling klopt niet
- Controleer of er geen conflicterende CSS is
- Zorg dat popup.css na andere stylesheets wordt geladen
- Check browser cache (hard refresh met Ctrl+F5)

### Callbacks werken niet
- Controleer of de callback een functie is
- Check de browser console voor JavaScript errors

## 📞 Support

Voor vragen of problemen, check de code in:
- `assets/css/popup.css` - Styling
- `assets/js/popup.js` - Functionaliteit
- `pages/popup-demo.php` - Voorbeelden
