<?php
session_start();

require_once __DIR__ . '/../../src/models/DashboardStats.php';
require_once __DIR__ . '/../../src/models/Question.php';

$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$basePath = preg_replace('#/pages$#', '', $scriptDir);
if ($basePath === '/' || $basePath === '\\' || $basePath === false) {
    $basePath = '';
}
if (!defined('APP_BASE_PATH')) {
    define('APP_BASE_PATH', $basePath);
}

$userId = $_SESSION['user_id'] ?? null;

// If user is already logged in, redirect to dashboard
if ($userId) {
    header('Location: home.php');
    exit;
}

$statsModel = new DashboardStats();
$dashboardData = $statsModel->getOverview($userId);

$questionModel = new Question();
$highlightQuestion = $questionModel->getHighlightQuestion();  

$isLoggedIn = isset($_SESSION['user_id']);
$questionsData = $dashboardData['hero']['questions'];
$remainingLabel = $isLoggedIn
    ? ($questionsData['remaining'] > 0
        ? 'Nog ' . $questionsData['remaining'] . ' vragen te gaan'
        : 'Alle vragen zijn beantwoord')
    : 'Log in om jouw vragen te vervolgen';
$scoreBtnLabel = $isLoggedIn
    ? ($questionsData['remaining'] > 0
        ? 'Beantwoord ' . $questionsData['remaining'] . ' vragen'
        : 'Bekijk vragen')
    : 'Log in';
$scoreBtnLink = $isLoggedIn ? 'vragen.php' : '../src/views/auth/login.php';

$miniChartPalette = ['bar-green', 'bar-blue', 'bar-orange'];
?>
<!DOCTYPE html>
<html lang="nl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="<?= htmlspecialchars(APP_BASE_PATH); ?>/assets/css/style.css" />
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
            <div class="score-value"><?= $dashboardData['hero']['score']; ?></div>
            <p class="score-range">van 100</p>
            <div class="score-scale">
              <div
                class="score-scale-fill"
                style="width: <?= $dashboardData['hero']['score']; ?>%"
              ></div>
            </div>
            <p class="score-caption">
              <?= htmlspecialchars($dashboardData['hero']['message']); ?>
            </p>
          </div>

          <div class="score-card-right">
            <p class="score-label">Vragen vandaag</p>
            <div class="score-questions"><?= $questionsData['answered']; ?></div>
            <p class="score-caption"><?= htmlspecialchars($remainingLabel); ?></p>
            <a class="score-btn" href="<?= htmlspecialchars($scoreBtnLink); ?>">
              <?= htmlspecialchars($scoreBtnLabel); ?>
            </a>
          </div>
        </div>
      </section>

      <section class="pillars-section">
        <h2>Je Gezondheid Pijlers</h2>
        <div class="pillars-grid">
          <?php if (!empty($dashboardData['pillars'])): ?>
            <?php foreach ($dashboardData['pillars'] as $pillar): ?>
              <article class="pillar-card">
                <div
                  class="pillar-icon"
                  style="border-color: <?= htmlspecialchars($pillar['color'] ?? '#dcfce7'); ?>;"
                >
                  <span class="pillar-percentage"><?= $pillar['percentage']; ?>%</span>
                </div>
                <h3><?= htmlspecialchars($pillar['name']); ?></h3>
              </article>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="no-questions">Nog geen pijlerdata beschikbaar.</p>
          <?php endif; ?>
        </div>
      </section>

      <section class="insights-row">
        <article class="question-card">
          <?php if ($highlightQuestion): ?>
            <div class="question-badge">
              <?= htmlspecialchars($highlightQuestion['pillar_name'] ?? 'Vraag'); ?>
            </div>
            <h3 class="question-text">
              <?= htmlspecialchars($highlightQuestion['question_text']); ?>
            </h3>
            <p class="answer-label">Voorbeeld antwoorden</p>
            <div class="answer-grid">
              <?php foreach (array_slice($highlightQuestion['parsed_choices'], 0, 4) as $choice): ?>
                <button class="answer-btn">
                  <?= htmlspecialchars($choice); ?>
                </button>
              <?php endforeach; ?>
            </div>
            <div class="question-nav">
              <a href="<?= htmlspecialchars($scoreBtnLink); ?>" class="nav-btn next-btn">
                Start vragen
              </a>
            </div>
          <?php else: ?>
            <div class="question-badge">Vragen</div>
            <p class="no-questions">Er zijn momenteel geen vragen beschikbaar.</p>
          <?php endif; ?>
        </article>

        <article class="progress-card mini-progress-card">
          <p class="progress-label">Deze week</p>
          <p class="progress-count">Voortgang</p>
          <?php if (!empty($dashboardData['mini_chart'])): ?>
            <div class="mini-chart">
              <?php foreach ($dashboardData['mini_chart'] as $index => $bar): ?>
                <?php $barClass = $miniChartPalette[$index] ?? 'bar-green'; ?>
                <div class="chart-bar">
                  <span
                    class="bar <?= $barClass; ?>"
                    style="height: <?= max(5, $bar['percentage']); ?>%"
                  ></span>
                  <p><?= htmlspecialchars($bar['name']); ?></p>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="no-questions">Nog geen voortgangsdata.</p>
          <?php endif; ?>
        </article>
      </section>
    </main>

    <?php include __DIR__ . '/../components/footer.php'; ?>
  </body>
</html> -->