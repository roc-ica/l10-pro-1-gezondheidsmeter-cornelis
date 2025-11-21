# Gezondheidsmeter Cornelis

Dit project is een PHP-webapplicatie voor de **Gezondheidsmeter Cornelis**. In deze README vind je een kort overzicht van de mappenstructuur en waar alles voor bedoeld is.

## Projectstructuur

- **root**  
  Bevat de hoofdbestanden van de applicatie:
  - `index.php` – startpunt van de website / entrypoint.
  - `README.md` – dit bestand.

## Mappen

- **assets/**  
  Algemene statische bestanden voor de site.
  - `assets/images/` – hier komen afbeeldingen en iconen te staan die in de applicatie gebruikt worden.

- **components/**  
  Herbruikbare PHP-componenten die op meerdere pagina's terugkomen.
  - `navbar.php` – navigatiebalk van de site.
  - `footer.php` – footer van de site.

- **css/**  
  Map bedoeld voor alle CSS-stylesheets van de applicatie.  
  (Momenteel leeg; hier kun je bijvoorbeeld `style.css` of andere themabestanden plaatsen.)

- **js/**  
  Map voor JavaScript-bestanden (interactiviteit, formulieren valideren, kleine UI-scripts, enz.).  
  (Momenteel leeg; hier kun je later je scripts in opslaan.)

- **json/**  
  Bevat JSON-bestanden met data/configuratie.
  - `taakverdeling.json` – document met de taakverdeling binnen het project (wie wat doet).

- **pages/**  
  Bevat losse pagina-bestanden van de site.
  - `home.php` – (hoofd)homepage van de applicatie.

- **src/**  
  Bevat de "broncode"-structuur: configuratie, views, enz.

  - **src/config/**  
    Configuratie- en databasebestanden.
    - `database.php` – configuratie / connectie-instellingen voor de database.
    - `gezondheidsmeter.sql` – SQL-dump met de database-structuur en/of voorbeelddata.

  - **src/views/**  
    Views (paginaweergaves) van de applicatie.

    - **src/views/auth/**  
      Views die met authenticatie te maken hebben (bijvoorbeeld login/registreren).  
      (De precieze bestanden kunnen nog worden aangevuld of aangepast.)

## Ontwikkelnotities

- Plaats je nieuwe pagina's bij voorkeur in `pages/` of als view in `src/views/`.
- Gebruik `components/` voor herbruikbare delen zoals headers, footers en navigatie.
- Houd statische bestanden gestructureerd in `assets/`, `css/` en `js/`.

Pas deze README gerust aan als de structuur of functionaliteit verder groeit.