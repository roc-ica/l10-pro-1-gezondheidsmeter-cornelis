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
                <a href="../../pages/home.php" class="btn-naar-app">Naar App</a>
            </div>
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
                        <path d="M 40,180 L 100,175 L 160,170 L 220,165 L 280,160 L 340,155 L 400,150 L 460,145 L 460,200 L 40,200 Z" 
                              fill="#ff6c6c" 
                              opacity="0.6"/>
                        
                        <path d="M 40,180 L 100,170 L 160,155 L 220,145 L 280,130 L 340,110 L 400,85 L 460,60 L 460,200 L 40,200 Z" 
                              fill="#4ade80" 
                              opacity="0.6"/>
                        
                        <!-- Trend lines -->
                        <polyline points="40,180 100,175 160,170 220,165 280,160 340,155 400,150 460,145" 
                                  fill="none" 
                                  stroke="#ff6c6c" 
                                  stroke-width="1"/>
                        
                        <polyline points="40,180 100,170 160,155 220,145 280,130 340,110 400,85 460,60" 
                                  fill="none" 
                                  stroke="#22c55e" 
                                  stroke-width="1"/>
                    </svg>
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
                    <svg class="line-chart-svg" viewBox="0 0 500 200" preserveAspectRatio="xMidYMid meet">
                        <!-- Grid lines -->
                        <line x1="40" y1="160" x2="480" y2="160" stroke="#f3f4f6" stroke-width="0.75"/>
                        <line x1="40" y1="120" x2="480" y2="120" stroke="#f3f4f6" stroke-width="0.75"/>
                        <line x1="40" y1="80" x2="480" y2="80" stroke="#f3f4f6" stroke-width="0.75"/>
                        <line x1="40" y1="40" x2="480" y2="40" stroke="#f3f4f6" stroke-width="0.75"/>
                        
                        <!-- Axes -->
                        <line x1="40" y1="20" x2="40" y2="160" stroke="#374151" stroke-width="1"/>
                        <line x1="40" y1="160" x2="480" y2="160" stroke="#374151" stroke-width="1"/>
                        
                        <polyline points="40,140 80,130 120,115 160,120 200,100 240,95 280,105 320,85 360,80 400,70 440,65 480,60" 
                                  fill="none" 
                                  stroke="#22c55e" 
                                  stroke-width="1.2"/>
                        
                        <!-- Smaller dots -->
                        <circle cx="40" cy="140" r="2" fill="#22c55e"/>
                        <circle cx="80" cy="130" r="2" fill="#22c55e"/>
                        <circle cx="120" cy="115" r="2" fill="#22c55e"/>
                        <circle cx="160" cy="120" r="2" fill="#22c55e"/>
                        <circle cx="200" cy="100" r="2" fill="#22c55e"/>
                        <circle cx="240" cy="95" r="2" fill="#22c55e"/>
                        <circle cx="280" cy="105" r="2" fill="#22c55e"/>
                        <circle cx="320" cy="85" r="2" fill="#22c55e"/>
                        <circle cx="360" cy="80" r="2" fill="#22c55e"/>
                        <circle cx="400" cy="70" r="2" fill="#22c55e"/>
                        <circle cx="440" cy="65" r="2" fill="#22c55e"/>
                        <circle cx="480" cy="60" r="2" fill="#22c55e"/>
                    </svg>
                </div>
                <div class="chart-legend">
                    <div class="legend-item">
                        <div class="legend-box" style="background: #22c55e;"></div>
                        <span style="color: #6b7280;">Gemiddelde Score</span>
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
                            <!-- Slaap -->
                            <div class="bar-group">
                                <div class="day-bars">
                                    <div class="bar green" style="height: 50px;"></div>
                                    <div class="bar pink" style="height: 8px; margin-top: auto; margin-bottom: -4px; transform: translateY(4px); background: #ff6b6b;" title="Trend: -5"></div>
                                </div>
                                <div class="bar-tooltip">
                                    <strong>Slaap</strong><br>
                                    Score: 50<br>
                                    Trend: -5
                                </div>
                            </div>
                            <!-- Voeding -->
                            <div class="bar-group">
                                <div class="day-bars">
                                    <div class="bar green" style="height: 35px;"></div>
                                    <div class="bar pink" style="height: 5px; background: #4ade80;" title="Trend: +3"></div>
                                </div>
                                <div class="bar-tooltip">
                                    <strong>Voeding</strong><br>
                                    Score: 35<br>
                                    Trend: +3
                                </div>
                            </div>
                            <!-- Beweging -->
                            <div class="bar-group">
                                <div class="day-bars">
                                    <div class="bar green" style="height: 25px;"></div>
                                    <div class="bar pink" style="height: 40px; background: #ff8787;" title="Trend: +38"></div>
                                </div>
                                <div class="bar-tooltip">
                                    <strong>Beweging</strong><br>
                                    Score: 25<br>
                                    Trend: +38
                                </div>
                            </div>
                            <!-- Stress -->
                            <div class="bar-group">
                                <div class="day-bars">
                                    <div class="bar green" style="height: 38px;"></div>
                                    <div class="bar pink" style="height: 8px; background: #ff8787;" title="Trend: +6"></div>
                                </div>
                                <div class="bar-tooltip">
                                    <strong>Stress</strong><br>
                                    Score: 38<br>
                                    Trend: +6
                                </div>
                            </div>
                            <!-- Hydratatie -->
                            <div class="bar-group">
                                <div class="day-bars">
                                    <div class="bar green" style="height: 28px;"></div>
                                    <div class="bar pink" style="height: 12px; margin-top: auto; margin-bottom: -6px; transform: translateY(6px); background: #ff6b6b;" title="Trend: -10"></div>
                                </div>
                                <div class="bar-tooltip">
                                    <strong>Hydratatie</strong><br>
                                    Score: 28<br>
                                    Trend: -10
                                </div>
                            </div>
                            <!-- Mentaal -->
                            <div class="bar-group">
                                <div class="day-bars">
                                    <div class="bar green" style="height: 45px;"></div>
                                    <div class="bar pink" style="height: 10px; background: #ff8787;" title="Trend: +8"></div>
                                </div>
                                <div class="bar-tooltip">
                                    <strong>Mentaal</strong><br>
                                    Score: 45<br>
                                    Trend: +8
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="x-axis">
                        <div class="x-axis-label">Slaap</div>
                        <div class="x-axis-label">Voeding</div>
                        <div class="x-axis-label">Beweging</div>
                        <div class="x-axis-label">Stress</div>
                        <div class="x-axis-label">Hydratatie</div>
                        <div class="x-axis-label">Mentaal</div>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div class="legend-box green" style="background: #008000;"></div>
                            <span style="color: #6b7280;">Gemiddelde Score</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box pink" style="background: #ff8787;"></div>
                            <span style="color: #6b7280;">Trend (-/+)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../components/footer.php'; ?>
</body>
</html>