<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../src/views/auth/login.php');
    exit;
}

// Check if user is admin
require_once __DIR__ . '/../../src/models/User.php';
require_once __DIR__ . '/../../src/models/AdminActionLogger.php';

$user = \User::findByIdStatic($_SESSION['user_id']);

if (!$user || !$user->is_admin) {
    // Not admin, redirect to normal home
    header('Location: ../admin/pages/home.php');
    exit;
}

// Log analytics view
$logger = new AdminActionLogger();
$logger->logAnalyticsView($_SESSION['user_id']);

// Get period from URL parameter (default to 'week')
$period = $_GET['period'] ?? 'week';
$validPeriods = ['week', 'month', 'year'];
if (!in_array($period, $validPeriods)) {
    $period = 'week';
}

// Get period labels and days to show
$periodConfig = [
    'week' => ['label' => 'Week', 'days' => 8],
    'month' => ['label' => 'Maand', 'days' => 31],
    'year' => ['label' => 'Jaar', 'days' => 366]
];
$periodLabel = $periodConfig[$period]['label'];
$daysToShow = $periodConfig[$period]['days'];

$username = $_SESSION['username'] ?? 'Admin';
$pdo = Database::getConnection();

// --- DATA FETCHING FOR ANALYTICS ---

// 1. Get User Growth Data (Cumulative)
$userGrowthQuery = $pdo->prepare("
    SELECT 
        d.date,
        (SELECT COUNT(*) FROM users u WHERE DATE(u.created_at) <= d.date) as cumulative_users
    FROM (
        SELECT DATE(DATE_SUB(NOW(), INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY)) as date
        FROM (SELECT 0 as a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) as a
        CROSS JOIN (SELECT 0 as a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) as b
        CROSS JOIN (SELECT 0 as a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) as c
    ) d
    WHERE d.date >= DATE_SUB(NOW(), INTERVAL ? DAY)
    ORDER BY d.date ASC
");
$userGrowthQuery->execute([$daysToShow - 1]);
$growthData = $userGrowthQuery->fetchAll(PDO::FETCH_ASSOC);

// 2. Get Engagement Data (Daily Entries)
$engagementQuery = $pdo->prepare("
    SELECT 
        d.date,
        COUNT(de.id) as daily_entries
    FROM (
        SELECT DATE(DATE_SUB(NOW(), INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY)) as date
        FROM (SELECT 0 as a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) as a
        CROSS JOIN (SELECT 0 as a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) as b
        CROSS JOIN (SELECT 0 as a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) as c
    ) d
    LEFT JOIN daily_entries de ON d.date = de.entry_date
    WHERE d.date >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY d.date
    ORDER BY d.date ASC
");
$engagementQuery->execute([$daysToShow - 1]);
$engagementData = $engagementQuery->fetchAll(PDO::FETCH_ASSOC);

// 3. Get Average Score Trend
$scoreTrendQuery = $pdo->prepare("
    SELECT 
        d.date,
        IFNULL(AVG(uhs.overall_score), 0) as avg_score
    FROM (
        SELECT DATE(DATE_SUB(NOW(), INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY)) as date
        FROM (SELECT 0 as a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) as a
        CROSS JOIN (SELECT 0 as a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) as b
        CROSS JOIN (SELECT 0 as a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) as c
    ) d
    LEFT JOIN user_health_scores uhs ON d.date = uhs.score_date
    WHERE d.date >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY d.date
    ORDER BY d.date ASC
");
$scoreTrendQuery->execute([$daysToShow - 1]);
$scoreData = $scoreTrendQuery->fetchAll(PDO::FETCH_ASSOC);

// 4. Get Pillar Score Averages for Weekly Activity Chart
$pillarScoresQuery = $pdo->prepare("
    SELECT 
        p.id as pillar_id,
        p.name as pillar_name,
        ROUND(AVG(JSON_EXTRACT(uhs.pillar_scores, CONCAT('$.', p.id))), 2) as avg_score
    FROM pillars p
    LEFT JOIN user_health_scores uhs ON JSON_EXTRACT(uhs.pillar_scores, CONCAT('$.', p.id)) IS NOT NULL
        AND uhs.score_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY p.id, p.name
    ORDER BY p.id
");
$pillarScoresQuery->execute([$daysToShow - 1]);
$pillarScores = $pillarScoresQuery->fetchAll(PDO::FETCH_ASSOC);

// --- SVG COORDINATE CALCULATION ---

function generateSvgPoints($data, $valueKey, $width, $height, $maxVal = null) {
    if (empty($data)) return "";
    $count = count($data);
    if ($maxVal === null) {
        $maxVal = 0;
        foreach ($data as $row) {
            if ($row[$valueKey] > $maxVal) $maxVal = $row[$valueKey];
        }
    }
    if ($maxVal == 0) $maxVal = 1;

    $points = [];
    $xPadding = 40;
    $yPadding = 20;
    $chartWidth = $width - ($xPadding * 2);
    $chartHeight = $height - ($yPadding * 2);
    $bottom = $height - 50;

    foreach ($data as $i => $row) {
        $x = $xPadding + ($i * ($chartWidth / ($count - 1)));
        $y = $bottom - (($row[$valueKey] / $maxVal) * ($chartHeight - 30));
        $points[] = round($x, 1) . "," . round($y, 1);
    }
    return implode(" ", $points);
}

function generateSvgAreaPath($data, $valueKey, $width, $height, $maxVal = null) {
    $points = generateSvgPoints($data, $valueKey, $width, $height, $maxVal);
    if (empty($points)) return "";
    
    $pointArr = explode(" ", $points);
    $firstX = explode(",", $pointArr[0])[0];
    $lastX = explode(",", $pointArr[count($pointArr)-1])[0];
    $bottom = $height - 50;
    
    return "M $firstX,$bottom L $points L $lastX,$bottom Z";
}

// Prepare specific strings for charts
$growthPoints = generateSvgPoints($growthData, 'cumulative_users', 500, 250);
$growthArea = generateSvgAreaPath($growthData, 'cumulative_users', 500, 250);

$engagementPoints = generateSvgPoints($engagementData, 'daily_entries', 500, 250);
$engagementArea = generateSvgAreaPath($engagementData, 'daily_entries', 500, 250);

$scoreTrendPoints = generateSvgPoints($scoreData, 'avg_score', 500, 250, 100); // Max score is 100
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Analytics - Gezondheidsmeter</title>
    <link rel="stylesheet" href="../../assets/css/admin.css?v=6">
    <style>
        /* Tooltip Styles */
        .chart-tooltip {
            position: absolute;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s ease;
            z-index: 1000;
            white-space: nowrap;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .chart-tooltip.visible {
            opacity: 1;
        }
        
        .chart-tooltip-label {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .chart-tooltip-value {
            font-size: 14px;
            font-weight: 700;
        }
        
        /* Make SVG elements interactive */
        .area-chart-svg circle,
        .line-chart-svg circle {
            cursor: pointer;
            transition: r 0.2s ease;
        }
        
        .area-chart-svg circle:hover,
        .line-chart-svg circle:hover {
            r: 5;
        }
        
        .bar-group {
            position: relative;
            cursor: pointer;
        }
        
        .bar-group:hover .bar {
            opacity: 0.8;
            transform: scaleY(1.02);
            transition: all 0.2s ease;
        }
    </style>
</head>
<body class="auth-page">
    <?php include __DIR__ . '/../../components/navbar-admin.php'; ?>
    
    <div class="dashboard-container1">
        <div class="dashboard-header">
            <div class="dashboard-header-left">
                <h1>Analytics</h1>
                <p>Inzicht in gebruikersgegevens</p>
            </div>
            <div class="dashboard-header-right">
                <a href="../../pages/home.php" class="btn-naar-app">Naar App</a>
            </div>
        </div>

        <!-- Period Selector -->
        <div class="period-selector">
            <a href="?period=week" class="period-btn <?php echo $period === 'week' ? 'active' : ''; ?>">
                Week
            </a>
            <a href="?period=month" class="period-btn <?php echo $period === 'month' ? 'active' : ''; ?>">
                Maand
            </a>
            <a href="?period=year" class="period-btn <?php echo $period === 'year' ? 'active' : ''; ?>">
                Jaar
            </a>
        </div>
        
        <div class="period-info">
            Data van de afgelopen <strong><?= strtolower($periodLabel) ?></strong>
        </div>

        <div class="analytics-row">
            <div class="chart-block analytics-chart-block">
                <h3 class="chart-title">Gebruikersgroei & Engagement</h3>
                <div class="area-chart-container">
                    <svg class="area-chart-svg" viewBox="0 0 500 250" preserveAspectRatio="xMidYMid meet">
                        <!-- Grid lines -->
                        <line x1="40" y1="200" x2="480" y2="200" stroke="#f3f4f6" stroke-width="0.75"/>
                        <line x1="40" y1="150" x2="480" y2="150" stroke="#f3f4f6" stroke-width="0.75"/>
                        <line x1="40" y1="100" x2="480" y2="100" stroke="#f3f4f6" stroke-width="0.75"/>
                        <line x1="40" y1="50" x2="480" y2="50" stroke="#f3f4f6" stroke-width="0.75"/>
                        
                        <!-- Axes -->
                        <line x1="40" y1="20" x2="40" y2="200" stroke="#374151" stroke-width="1"/>
                        <line x1="40" y1="200" x2="480" y2="200" stroke="#374151" stroke-width="1"/>
                        
                        <!-- Area fills -->
                        <path d="<?= $engagementArea ?>" 
                              fill="#ff6c6c" 
                              opacity="0.6"/>
                        
                        <path d="<?= $growthArea ?>" 
                              fill="#4ade80" 
                              opacity="0.6"/>
                        
                        <!-- Trend lines -->
                        <polyline points="<?= $engagementPoints ?>" 
                                  fill="none" 
                                  stroke="#ff6c6c" 
                                  stroke-width="1"/>
                        
                        <polyline points="<?= $growthPoints ?>" 
                                  fill="none" 
                                  stroke="#22c55e" 
                                  stroke-width="1"/>
                        
                        <!-- Interactive dots for engagement -->
                        <?php
                        $engagementPointsArr = explode(" ", $engagementPoints);
                        foreach ($engagementPointsArr as $i => $point) {
                            if (empty($point)) continue;
                            $coords = explode(",", $point);
                            $value = $engagementData[$i]['daily_entries'] ?? 0;
                            $date = $engagementData[$i]['date'] ?? '';
                            echo '<circle cx="' . $coords[0] . '" cy="' . $coords[1] . '" r="3" fill="#ff6c6c" class="data-point" data-value="' . $value . '" data-label="Engagement: ' . date('d-m-Y', strtotime($date)) . '" data-type="entries"/>';
                        }
                        ?>
                        
                        <!-- Interactive dots for growth -->
                        <?php
                        $growthPointsArr = explode(" ", $growthPoints);
                        foreach ($growthPointsArr as $i => $point) {
                            if (empty($point)) continue;
                            $coords = explode(",", $point);
                            $value = $growthData[$i]['cumulative_users'] ?? 0;
                            $date = $growthData[$i]['date'] ?? '';
                            echo '<circle cx="' . $coords[0] . '" cy="' . $coords[1] . '" r="3" fill="#22c55e" class="data-point" data-value="' . $value . '" data-label="Gebruikers: ' . date('d-m-Y', strtotime($date)) . '" data-type="users"/>';
                        }
                        ?>
                    </svg>
                    <div class="chart-tooltip" id="tooltip-growth"></div>
                </div>
                <div class="chart-legend">
                    <div class="legend-item">
                        <div class="legend-box" style="background: #ff6c6c;"></div>
                        <span style="color: #6b7280;">Engagement</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-box" style="background: #22c55e;"></div>
                        <span style="color: #6b7280;">Gebruikers</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gemiddelde Score Trend Chart -->
        <div class="analytics-row">
            <div class="chart-block analytics-chart-block">
                <h3 class="chart-title">Gemiddelde Score Trend</h3>
                <div class="line-chart-container">
                    <svg class="line-chart-svg" viewBox="0 0 500 250" preserveAspectRatio="xMidYMid meet">
                        <!-- Grid lines -->
                        <line x1="40" y1="160" x2="480" y2="160" stroke="#f3f4f6" stroke-width="0.75"/>
                        <line x1="40" y1="120" x2="480" y2="120" stroke="#f3f4f6" stroke-width="0.75"/>
                        <line x1="40" y1="80" x2="480" y2="80" stroke="#f3f4f6" stroke-width="0.75"/>
                        <line x1="40" y1="40" x2="480" y2="40" stroke="#f3f4f6" stroke-width="0.75"/>
                        
                        <!-- Axes -->
                        <line x1="40" y1="20" x2="40" y2="200" stroke="#374151" stroke-width="1"/>
                        <line x1="40" y1="200" x2="480" y2="200" stroke="#374151" stroke-width="1"/>
                        
                        <polyline points="<?= $scoreTrendPoints ?>" 
                                  fill="none" 
                                  stroke="#22c55e" 
                                  stroke-width="1.2"/>
                        
                        <!-- Interactive dots with data -->
                        <?php
                        $points = explode(" ", $scoreTrendPoints);
                        foreach ($points as $i => $point) {
                            if (empty($point)) continue;
                            $coords = explode(",", $point);
                            $score = $scoreData[$i]['avg_score'] ?? 0;
                            $date = $scoreData[$i]['date'] ?? '';
                            echo '<circle cx="' . $coords[0] . '" cy="' . $coords[1] . '" r="3" fill="#22c55e" class="data-point" data-value="' . number_format($score, 1) . '" data-label="Score: ' . date('d-m-Y', strtotime($date)) . '"/>';
                        }
                        ?>
                    </svg>
                    <div class="chart-tooltip" id="tooltip-score"></div>
                </div>
                <div class="chart-legend">
                    <div class="legend-item">
                        <div class="legend-box" style="background: #22c55e;"></div>
                        <span style="color: #6b7280;">Gemiddelde Score</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wekelijkse Activiteit per Categorie Chart -->
        <div class="analytics-row">
            <div class="chart-block analytics-chart-block">
                <h3 class="chart-title">Gemiddelde Scores per Categorie</h3>
                <div class="weekly-activity">
                    <div class="chart-area">
                        <div class="y-axis">
                            <div class="y-axis-label">100</div>
                            <div class="y-axis-label">75</div>
                            <div class="y-axis-label">50</div>
                            <div class="y-axis-label">25</div>
                            <div class="y-axis-label">0</div>
                        </div>
                        <div class="bars-container">
                            <?php foreach ($pillarScores as $pillar): 
                                $score = $pillar['avg_score'] ?? 0;
                                $height = ($score / 100) * 240; // Max height 240px to match chart area
                            ?>
                            <div class="bar-group" data-value="<?= number_format($score, 1) ?>" data-label="<?= htmlspecialchars($pillar['pillar_name']) ?>">
                                <div class="day-bars">
                                    <div class="bar green" style="height: <?= $height ?>px;"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="x-axis">
                        <?php foreach ($pillarScores as $pillar): ?>
                        <div class="x-axis-label"><?= htmlspecialchars($pillar['pillar_name']) ?></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div class="legend-box green" style="background: #008000;"></div>
                            <span style="color: #6b7280;">Gemiddelde Score per Categorie</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="chart-tooltip" id="tooltip-bars"></div>
    </div>

    <?php include __DIR__ . '/../../components/footer.php'; ?>
    
    <script>
        // Tooltip functionality with mobile support
        document.addEventListener('DOMContentLoaded', function() {
            // Helper function to position tooltip within viewport
            function positionTooltip(tooltip, e, isMobile = false, targetElement = null) {
                const tooltipRect = tooltip.getBoundingClientRect();
                const tooltipWidth = tooltipRect.width;
                const tooltipHeight = tooltipRect.height;
                const viewportWidth = window.innerWidth;
                const viewportHeight = window.innerHeight;
                
                let left, top;
                
                if (isMobile && targetElement) {
                    // Center tooltip above element on mobile
                    const targetRect = targetElement.getBoundingClientRect();
                    left = targetRect.left + (targetRect.width / 2) - (tooltipWidth / 2);
                    top = targetRect.top + window.scrollY - tooltipHeight - 10;
                    
                    // If doesn't fit above, show below
                    if (top < window.scrollY + 10) {
                        top = targetRect.bottom + window.scrollY + 10;
                    }
                } else {
                    // Desktop: follow cursor
                    left = e.pageX + 15;
                    top = e.pageY - 10;
                    
                    // Adjust if goes off right edge
                    if (left + tooltipWidth > viewportWidth) {
                        left = e.pageX - tooltipWidth - 15;
                    }
                    
                    // Adjust if goes off bottom edge
                    if (top + tooltipHeight > viewportHeight + window.scrollY) {
                        top = e.pageY - tooltipHeight - 10;
                    }
                }
                
                // Keep within left edge
                if (left < 10) left = 10;
                
                // Keep within right edge
                if (left + tooltipWidth > viewportWidth - 10) {
                    left = viewportWidth - tooltipWidth - 10;
                }
                
                // Keep within top edge
                if (top < window.scrollY + 10) {
                    top = window.scrollY + 10;
                }
                
                tooltip.style.left = left + 'px';
                tooltip.style.top = top + 'px';
            }
            
            // Handle SVG data points (circles)
            const dataPoints = document.querySelectorAll('.data-point');
            const tooltipGrowth = document.getElementById('tooltip-growth');
            const tooltipScore = document.getElementById('tooltip-score');
            
            dataPoints.forEach(point => {
                point.addEventListener('mouseenter', function(e) {
                    const value = this.getAttribute('data-value');
                    const label = this.getAttribute('data-label');
                    const type = this.getAttribute('data-type');
                    
                    let tooltip;
                    if (this.closest('.area-chart-svg')) {
                        tooltip = tooltipGrowth;
                    } else if (this.closest('.line-chart-svg')) {
                        tooltip = tooltipScore;
                    }
                    
                    if (tooltip) {
                        let displayValue = value;
                        if (type === 'entries') {
                            displayValue = value + ' entries';
                        } else if (type === 'users') {
                            displayValue = value + ' gebruikers';
                        } else {
                            displayValue = value + ' punten';
                        }
                        
                        tooltip.innerHTML = '<div class="chart-tooltip-label">' + label + '</div><div class="chart-tooltip-value">' + displayValue + '</div>';
                        tooltip.classList.add('visible');
                    }
                });
                
                point.addEventListener('mousemove', function(e) {
                    let tooltip;
                    if (this.closest('.area-chart-svg')) {
                        tooltip = tooltipGrowth;
                    } else if (this.closest('.line-chart-svg')) {
                        tooltip = tooltipScore;
                    }
                    
                    if (tooltip) {
                        positionTooltip(tooltip, e);
                    }
                });
                
                point.addEventListener('mouseleave', function() {
                    let tooltip;
                    if (this.closest('.area-chart-svg')) {
                        tooltip = tooltipGrowth;
                    } else if (this.closest('.line-chart-svg')) {
                        tooltip = tooltipScore;
                    }
                    
                    if (tooltip) {
                        tooltip.classList.remove('visible');
                    }
                });
                
                // Touch support for mobile
                point.addEventListener('touchstart', function(e) {
                    e.preventDefault();
                    const value = this.getAttribute('data-value');
                    const label = this.getAttribute('data-label');
                    const type = this.getAttribute('data-type');
                    
                    let tooltip;
                    if (this.closest('.area-chart-svg')) {
                        tooltip = tooltipGrowth;
                    } else if (this.closest('.line-chart-svg')) {
                        tooltip = tooltipScore;
                    }
                    
                    if (tooltip) {
                        let displayValue = value;
                        if (type === 'entries') {
                            displayValue = value + ' entries';
                        } else if (type === 'users') {
                            displayValue = value + ' gebruikers';
                        } else {
                            displayValue = value + ' punten';
                        }
                        
                        tooltip.innerHTML = '<div class="chart-tooltip-label">' + label + '</div><div class="chart-tooltip-value">' + displayValue + '</div>';
                        positionTooltip(tooltip, e.touches[0], true, this);
                        tooltip.classList.add('visible');
                        
                        setTimeout(() => {
                            tooltip.classList.remove('visible');
                        }, 2000);
                    }
                });
            });
            
            // Handle bar groups
            const barGroups = document.querySelectorAll('.bar-group');
            const tooltipBars = document.getElementById('tooltip-bars');
            
            barGroups.forEach(bar => {
                bar.addEventListener('mouseenter', function(e) {
                    const value = this.getAttribute('data-value');
                    const label = this.getAttribute('data-label');
                    
                    tooltipBars.innerHTML = '<div class="chart-tooltip-label">' + label + '</div><div class="chart-tooltip-value">' + value + ' punten</div>';
                    tooltipBars.classList.add('visible');
                });
                
                bar.addEventListener('mousemove', function(e) {
                    positionTooltip(tooltipBars, e);
                });
                
                bar.addEventListener('mouseleave', function() {
                    tooltipBars.classList.remove('visible');
                });
                
                // Touch support for mobile
                bar.addEventListener('touchstart', function(e) {
                    e.preventDefault();
                    const value = this.getAttribute('data-value');
                    const label = this.getAttribute('data-label');
                    
                    tooltipBars.innerHTML = '<div class="chart-tooltip-label">' + label + '</div><div class="chart-tooltip-value">' + value + ' punten</div>';
                    positionTooltip(tooltipBars, e.touches[0], true, this);
                    tooltipBars.classList.add('visible');
                    
                    setTimeout(() => {
                        tooltipBars.classList.remove('visible');
                    }, 2000);
                });
            });
        });
    </script>
</body>
</html>