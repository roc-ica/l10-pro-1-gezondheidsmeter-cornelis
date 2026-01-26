# Instructies: Een jaar aan data toevoegen voor alle gebruikers

Ik heb een SQL script gemaakt dat automatisch een jaar aan testdata genereert voor alle gebruikers (behalve admins).

## Bestand
`generate_year_data_all_users.sql`

## Wat doet dit script?

Voor **elke gebruiker** (behalve admin gebruikers) maakt het script:
- **365 dagelijkse entries** (één per dag voor het afgelopen jaar)
- **Antwoorden** op alle vragen per dag (water, beweging, slaap, verslavingen, sociaal)
- **Gezondheidsscores** per dag met pillar scores
- **Realistische variaties**: 
  - Weekendgedrag verschilt van doordeweeks
  - Willekeurige maar realistische waarden
  - Verschillende tijden van invullen

## Hoe uit te voeren

### Optie 1: Via phpMyAdmin (GEMAKKELIJKST)

1. Open **phpMyAdmin** in je browser: `http://localhost/phpmyadmin`
2. Selecteer de database **gezondheidsmeter** in het linker menu
3. Klik op het **SQL** tabblad bovenaan
4. Open het bestand `generate_year_data_all_users.sql` in een teksteditor
5. **Kopieer de volledige inhoud** van het bestand
6. **Plak** het in het SQL venster in phpMyAdmin
7. Klik op **Go** (of Uitvoeren)
8. Wacht enkele seconden/minuten terwijl het script wordt uitgevoerd
9. Je ziet een overzicht van alle gegenereerde data

### Optie 2: Via MySQL Command Line

1. Start XAMPP Control Panel
2. Zorg dat **MySQL** draait (klik op Start als het nog niet actief is)
3. Open een Command Prompt (CMD, niet PowerShell)
4. Navigeer naar de project directory:
   ```
   cd C:\xampp\htdocs\l10-pro-1-gezondheidsmeter-cornelis
   ```
5. Voer het script uit:
   ```
   C:\xampp\mysql\bin\mysql.exe -u root gezondheidsmeter < generate_year_data_all_users.sql
   ```

## Wat gebeurt er precies?

Het script:
1. Haalt alle niet-admin gebruikers op uit de database
2. Voor elke gebruiker:
   - Loop door 365 dagen (van 1 jaar geleden tot vandaag)
   - Check of er al data bestaat voor die dag (skippt die dan)
   - Genereert realistische antwoorden:
     - **Water**: 4-12 glazen (weekend iets minder)
     - **Beweging**: 10-90 minuten (weekend meer)
     - **Slaap**: 5-9 uur (weekend meer)
     - **Sociaal**: 0-12 uur contact (weekend veel meer)
     - **Alcohol/drugs**: 20% kans doordeweeks, 40% weekend
   - Berekent gezondheidsscores gebaseerd op de antwoorden
3. Toont een overzicht van alle gegenereerde data

## Veiligheid

- Het script controleert of er al data bestaat voor een bepaalde dag
- Als er al data is, wordt die **NIET overschreven**
- Alleen **nieuwe** dagen krijgen data
- Alleen **niet-admin gebruikers** krijgen data

## Na het uitvoeren

Je kunt de geschiedenis pagina's bekijken voor elke gebruiker en je zult een volledig jaar aan data zien met grafieken en statistieken!

## Problemen?

Als je MySQL errors krijgt:
1. Zorg dat MySQL draait in XAMPP Control Panel
2. Controleer of de database "gezondheidsmeter" bestaat
3. Probeer via phpMyAdmin (Optie 1) - dit is het makkelijkst
