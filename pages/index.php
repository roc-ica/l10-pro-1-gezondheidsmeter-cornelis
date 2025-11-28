<!DOCTYPE html>
<html lang="nl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../assets/css/style.css" />
    <title>Gezondheidsmeter</title>
  </head>
  <body class="home-body">
    <?php include __DIR__ . '/../components/navbar.php'; ?>

    <main class="home-container">
      <div class="groen-titel">
        <h5>♡ Jouw Gezondheid Getracked</h5>
      </div>

      <section class="hero-section">
        <div class="hero-copy">
          <h1>Gezondheidsmeter</h1>
          <p>
            Monitor je zes gezondheid pijlers en ontdek hoe je een gezondere
            leefstijl kunt aannemen. Beantwoord dagelijks vragen en krijg inzicht
            in je gezondheid.
          </p>
        </div>

        <div class="score-card">
          <div class="score-card-left">
            <p class="score-label">Je Gezondheidsscore</p>
            <div class="score-value">79</div>
            <p class="score-range">van 100</p>
            <div class="score-scale">
              <div class="score-scale-fill" style="width: 79%"></div>
            </div>
            <p class="score-caption">Goed bezig! Blijf zo door gaan.</p>
          </div>

          <div class="score-card-right">
            <p class="score-label">Vragen vandaag</p>
            <div class="score-questions">12</div>
            <p class="score-caption">Nog 3 vragen te gaan</p>
            <a class="score-btn" href="/pages/questions.php">Maak 7 vragen</a>
          </div>
        </div>
      </section>

      <section class="pillars-section">
        <h2>Je Gezondheid Pijlers</h2>
        <div class="pillars-grid">
          <article class="pillar-card">
            <div class="pillar-icon">
              <span class="pillar-percentage">75%</span>
            </div>
            <h3>Slaap</h3>
          </article>
          <article class="pillar-card">
            <div class="pillar-icon">
              <span class="pillar-percentage">90%</span>
            </div>
            <h3>Voeding</h3>
          </article>
          <article class="pillar-card">
            <div class="pillar-icon">
              <span class="pillar-percentage">55%</span>
            </div>
            <h3>Beweging</h3>
          </article>
          <article class="pillar-card">
            <div class="pillar-icon">
              <span class="pillar-percentage">40%</span>
            </div>
            <h3>Stress</h3>
          </article>
          <article class="pillar-card">
            <div class="pillar-icon">
              <span class="pillar-percentage">75%</span>
            </div>
            <h3>Hydratatie</h3>
          </article>
          <article class="pillar-card">
            <div class="pillar-icon">
              <span class="pillar-percentage">70%</span>
            </div>
            <h3>Mentaal</h3>
          </article>
        </div>
      </section>

      <section class="insights-row">
        <article class="question-card">
          <div class="question-badge">Vragen van vandaag</div>
          <h3 class="question-text">Heb je vandaag genoeg water gedronken?</h3>
          <p class="answer-label">Selecteer je antwoord</p>
          <div class="answer-grid">
            <button class="answer-btn selected">Niet genoeg</button>
            <button class="answer-btn">Meestal</button>
            <button class="answer-btn">Normaal</button>
            <button class="answer-btn">Zeer goed</button>
          </div>
          <div class="question-nav">
            <a href="#" class="nav-btn prev-btn">Vorige</a>
            <a href="#" class="nav-btn next-btn">Volgende</a>
          </div>
        </article>

        <article class="progress-card mini-progress-card">
          <p class="progress-label">Deze week</p>
          <p class="progress-count">Voortgang</p>
          <div class="mini-chart">
            <div class="chart-bar">
              <span class="bar bar-green" style="height: 65%"></span>
              <p>Slaap</p>
            </div>
            <div class="chart-bar">
              <span class="bar bar-blue" style="height: 80%"></span>
              <p>Voeding</p>
            </div>
            <div class="chart-bar">
              <span class="bar bar-orange" style="height: 50%"></span>
              <p>Stress</p>
            </div>
          </div>
        </article>
      </section>
    </main>

    <?php include __DIR__ . '/../components/footer.php'; ?>
  </body>
</html>