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
    header('Location: ../admin/pages/home.php');
    exit;
}

$username = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Analytics - Gezondheidsmeter</title>
<link rel="stylesheet" href="../../assets/css/admin.css?v=3">
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
                <a href="../pages/home.php" class="btn-naar-app">Naar App</a>
            </div>
        </div>

        <div class="analytics-row">
            <div class="chart-block analytics-chart-block">
                <h3 class="chart-title">Gebruikersgroei & Engagement</h3>
                <div class="area-chart-container">
                    <svg class="area-chart-svg" viewBox="0 0 500 250" preserveAspectRatio="xMidYMid meet">
                        <line x1="40" y1="200" x2="480" y2="200" stroke="#e5e7eb" stroke-width="1"/>
                        <line x1="40" y1="150" x2="480" y2="150" stroke="#e5e7eb" stroke-width="1"/>
                        <line x1="40" y1="100" x2="480" y2="100" stroke="#e5e7eb" stroke-width="1"/>
                        <line x1="40" y1="50" x2="480" y2="50" stroke="#e5e7eb" stroke-width="1"/>
                        
                        <line x1="40" y1="20" x2="40" y2="200" stroke="#000" stroke-width="1.5"/>
                        <line x1="40" y1="200" x2="480" y2="200" stroke="#000" stroke-width="1.5"/>
                        
                        <path d="M 40,180 L 100,175 L 160,170 L 220,165 L 280,160 L 340,155 L 400,150 L 460,145 L 460,200 L 40,200 Z" 
                              fill="#ff6c6c" 
                              opacity="0.7"/>
                        
                        <path d="M 40,180 L 100,170 L 160,155 L 220,145 L 280,130 L 340,110 L 400,85 L 460,60 L 460,200 L 40,200 Z" 
                              fill="#4ade80" 
                              opacity="0.7"/>
                        
                        <polyline points="40,180 100,175 160,170 220,165 280,160 340,155 400,150 460,145" 
                                  fill="none" 
                                  stroke="#ff6c6c" 
                                  stroke-width="2"/>
                        
                        <polyline points="40,180 100,170 160,155 220,145 280,130 340,110 400,85 460,60" 
                                  fill="none" 
                                  stroke="#22c55e" 
                                  stroke-width="2"/>
                    </svg>
                </div>
                <div class="chart-legend">
                    <div class="legend-item">
                        <div class="legend-box" style="background: #ff6c6c;"></div>
                        <span style="color: #ff6c6c;">Engagement</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-box" style="background: #22c55e;"></div>
                        <span style="color: #22c55e;">Gebruikers</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gemiddelde Score Trend Chart -->
        <div class="analytics-row">
            <div class="chart-block analytics-chart-block">
                <h3 class="chart-title">Gemiddelde Score Trend</h3>
                <div class="line-chart-container">
                    <svg class="line-chart-svg" viewBox="0 0 500 200" preserveAspectRatio="xMidYMid meet">
                        <line x1="40" y1="160" x2="480" y2="160" stroke="#e5e7eb" stroke-width="1"/>
                        <line x1="40" y1="120" x2="480" y2="120" stroke="#e5e7eb" stroke-width="1"/>
                        <line x1="40" y1="80" x2="480" y2="80" stroke="#e5e7eb" stroke-width="1"/>
                        <line x1="40" y1="40" x2="480" y2="40" stroke="#e5e7eb" stroke-width="1"/>
                        
                        <line x1="40" y1="20" x2="40" y2="160" stroke="#000" stroke-width="1.5"/>
                        <line x1="40" y1="160" x2="480" y2="160" stroke="#000" stroke-width="1.5"/>
                        
                        <polyline points="40,140 80,130 120,115 160,120 200,100 240,95 280,105 320,85 360,80 400,70 440,65 480,60" 
                                  fill="none" 
                                  stroke="#22c55e" 
                                  stroke-width="2.5"/>
                        
                        <circle cx="40" cy="140" r="3" fill="#22c55e"/>
                        <circle cx="80" cy="130" r="3" fill="#22c55e"/>
                        <circle cx="120" cy="115" r="3" fill="#22c55e"/>
                        <circle cx="160" cy="120" r="3" fill="#22c55e"/>
                        <circle cx="200" cy="100" r="3" fill="#22c55e"/>
                        <circle cx="240" cy="95" r="3" fill="#22c55e"/>
                        <circle cx="280" cy="105" r="3" fill="#22c55e"/>
                        <circle cx="320" cy="85" r="3" fill="#22c55e"/>
                        <circle cx="360" cy="80" r="3" fill="#22c55e"/>
                        <circle cx="400" cy="70" r="3" fill="#22c55e"/>
                        <circle cx="440" cy="65" r="3" fill="#22c55e"/>
                        <circle cx="480" cy="60" r="3" fill="#22c55e"/>
                    </svg>
                </div>
                <div class="chart-legend">
                    <div class="legend-item">
                        <div class="legend-box" style="background: #22c55e;"></div>
                        <span style="color: #22c55e;">Gemiddelde Score</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wekelijkse Activiteit Chart -->
        <div class="analytics-row">
            <div class="chart-block analytics-chart-block">
                <h3 class="chart-title">Wekelijkse Activiteit</h3>
                <div class="weekly-activity">
                    <div class="chart-area">
                        <div class="y-axis">
                            <div class="y-axis-label">90</div>
                            <div class="y-axis-label">60</div>
                            <div class="y-axis-label">30</div>
                            <div class="y-axis-label">0</div>
                            <div class="y-axis-label">-30</div>
                        </div>
                        <div class="bars-container">
                            <div class="day-bars">
                                <div class="bar green" style="height: 85px;"></div>
                                <div class="bar pink" style="height: 25px;"></div>
                            </div>
                            <div class="day-bars">
                                <div class="bar green" style="height: 0px;"></div>
                                <div class="bar pink" style="height: 15px;"></div>
                            </div>
                            <div class="day-bars">
                                <div class="bar green" style="height: 65px;"></div>
                                <div class="bar pink" style="height: 0px;"></div>
                            </div>
                            <div class="day-bars">
                                <div class="bar green" style="height: 75px;"></div>
                                <div class="bar pink" style="height: 35px;"></div>
                            </div>
                            <div class="day-bars">
                                <div class="bar green" style="height: 0px;"></div>
                                <div class="bar pink" style="height: 5px;"></div>
                            </div>
                            <div class="day-bars">
                                <div class="bar green" style="height: 95px;"></div>
                                <div class="bar pink" style="height: 0px;"></div>
                            </div>
                            <div class="day-bars">
                                <div class="bar green" style="height: 55px;"></div>
                                <div class="bar pink" style="height: 20px;"></div>
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
                        <div class="x-axis-label">Zondag</div>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div class="legend-box green"></div>
                            <span>Gemiddelde Score</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box pink"></div>
                            <span>Vragen</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../components/footer.php'; ?>
</body>
</html>