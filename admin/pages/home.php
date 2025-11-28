<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../src/views/auth/login.php');
    exit;
}

// Check if user is admin
require_once __DIR__ . '/../../src/models/User.php';
$user = \User::findByIdStatic($_SESSION['user_id']);

if (!$user || !$user->is_admin) {
    // Not admin, redirect to normal home
    header('Location: ../../pages/home.php');
    exit;
}

$username = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Gezondheidsmeter</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body class="auth-page">
    <?php include __DIR__ . '/../../components/navbar-admin.php'; ?>
    
    <div class="dashboard-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-header-left">
                <h1>Dashboard</h1>
                <p>Overzicht van alle gegevens</p>
            </div>
            <div class="dashboard-header-right">
                <a href="#" class="btn-naar-app">Naar App</a>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-block">
                <div class="stat-label">Totaal gebruikers</div>
                <div class="stat-number">1,234</div>
            </div>
            <div class="stat-block">
                <div class="stat-label">Vragen beantwoord</div>
                <div class="stat-number">8,973</div>
            </div>
            <div class="stat-block">
                <div class="stat-label">Gemiddelde Score</div>
                <div class="stat-number">76</div>
            </div>
            <div class="stat-block">
                <div class="stat-label">Actief Deze Week</div>
                <div class="stat-number">856</div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="charts-row">
            <!-- Weekly Activity Chart -->
            <div class="chart-block">
                <h3 class="chart-title">Wekelijkse Activiteit</h3>
                <div class="weekly-activity">
                    <div class="chart-area">
                        <div class="y-axis">
                            <div class="y-axis-label">180</div>
                            <div class="y-axis-label">135</div>
                            <div class="y-axis-label">90</div>
                            <div class="y-axis-label">45</div>
                            <div class="y-axis-label">0</div>
                        </div>
                        <div class="bars-container">
                            <div class="day-bars">
                                <div class="bar green" style="height: 60px;"></div>
                                <div class="bar pink" style="height: 80px;"></div>
                            </div>
                            <div class="day-bars">
                                <div class="bar green" style="height: 73px;"></div>
                                <div class="bar pink" style="height: 100px;"></div>
                            </div>
                            <div class="day-bars">
                                <div class="bar green" style="height: 93px;"></div>
                                <div class="bar pink" style="height: 107px;"></div>
                            </div>
                            <div class="day-bars">
                                <div class="bar green" style="height: 53px;"></div>
                                <div class="bar pink" style="height: 67px;"></div>
                            </div>
                            <div class="day-bars">
                                <div class="bar green" style="height: 87px;"></div>
                                <div class="bar pink" style="height: 120px;"></div>
                            </div>
                            <div class="day-bars">
                                <div class="bar green" style="height: 67px;"></div>
                                <div class="bar pink" style="height: 93px;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="x-axis">
                        <div class="x-axis-label">Maandag</div>
                        <div class="x-axis-label">Dinsdag</div>
                        <div class="x-axis-label">Woensdag</div>
                        <div class="x-axis-label">Donderdag</div>
                        <div class="x-axis-label">Vrijdag</div>
                        <div class="x-axis-label">Zaterdag</div>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div class="legend-box green"></div>
                            <span>Nieuwe gebruikers</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box pink"></div>
                            <span>Vragen beantwoord</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="chart-block recent-activity-block">
                <h3 class="chart-title">Recente activiteit</h3>
                <p class="activity-subtitle">Admin logboek en systeemmeldingen</p>
                <div class="activity-log">
                    <div class="activity-item">
                        <div class="activity-avatar">JD</div>
                        <div class="activity-content">
                            <div class="activity-name">Jan de Vries (Admin)</div>
                            <div class="activity-action">Heeft gebruiker #88291</div>
                        </div>
                        <div class="activity-time">10 min geleden</div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-avatar">PL</div>
                        <div class="activity-content">
                            <div class="activity-name">Peter Lodewijks (Admin)</div>
                            <div class="activity-action">Heeft vragen bijgewerkt</div>
                        </div>
                        <div class="activity-time">25 min geleden</div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-avatar">MB</div>
                        <div class="activity-content">
                            <div class="activity-name">Maria Bos (Admin)</div>
                            <div class="activity-action">Heeft nieuwe categorie aangemaakt</div>
                        </div>
                        <div class="activity-time">1 uur geleden</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trend Row -->
        <div class="trend-row">
            <div class="chart-block trend-block">
                <h3 class="chart-title">Gemiddelde Score Trend</h3>
                <div class="trend-chart">
                    <svg class="trend-svg" viewBox="0 0 300 120" preserveAspectRatio="xMidYMid meet">
                        <!-- Axes -->
                        <line x1="10" y1="10" x2="10" y2="100" stroke="#000" stroke-width="1"/>
                        <line x1="10" y1="100" x2="290" y2="100" stroke="#000" stroke-width="1"/>
                        
                        <!-- Trend line with points -->
                        <polyline 
                            points="10,85 40,75 70,60 100,65 130,50 160,45 190,55 220,40 250,35"
                            fill="none"
                            stroke="#008000"
                            stroke-width="0.5"
                        />
                        
                        <!-- Data points -->
                        <circle cx="40" cy="75" r="1" fill="#008000"/>
                        <circle cx="70" cy="60" r="1" fill="#008000"/>
                        <circle cx="100" cy="65" r="1" fill="#008000"/>
                        <circle cx="130" cy="50" r="1" fill="#008000"/>
                        <circle cx="160" cy="45" r="1" fill="#008000"/>
                        <circle cx="190" cy="55" r="1" fill="#008000"/>
                        <circle cx="220" cy="40" r="1" fill="#008000"/>
                        <circle cx="250" cy="35" r="1" fill="#008000"/>
                    </svg>
                </div>
                <div class="trend-legend">
                    <span class="trend-label">Gemiddelde Score</span>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../components/footer.php'; ?>
</body>
</html>
