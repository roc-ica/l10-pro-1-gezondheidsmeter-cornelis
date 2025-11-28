<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../src/views/auth/login.php');
    exit;
}

$username = $_SESSION['username'] ?? 'Gebruiker';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geschiedenis - Gezondheidsmeter</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/images/icons/gm192x192.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="auth-page">
    <?php include __DIR__ . '/../components/navbar.php'; ?>
    
    <div class="dashboard-container">
        <div class="history-header">
            <h1>Jouw Gezondheidsgeschiedenis</h1>
            <p>Bekijk je voortgang en trends van de afgelopen week.</p>
        </div>

        <!-- Summary Cards -->
        <div class="stats-row">
            <div class="stat-block">
                <div class="stat-label">Gemiddelde Score</div>
                <div class="stat-number green-text">72</div>
            </div>
            <div class="stat-block">
                <div class="stat-label">Beste Dag</div>
                <div class="stat-number green-text">Zaterdag</div>
            </div>
            <div class="stat-block">
                <div class="stat-label">Trend</div>
                <div class="stat-number green-text">↗ +5%</div>
            </div>
            <div class="stat-block">
                <div class="stat-label">Streak</div>
                <div class="stat-number green-text">7 Dagen</div>
            </div>
        </div>

        <!-- Weekly Progress Chart -->
        <div class="chart-section">
            <div class="chart-block">
                <h2 class="chart-title">Wekelijkse Voortgang</h2>
                <div class="chart-legend">
                    <div class="legend-item">
                        <span class="legend-icon" style="color: #22c55e;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                        </span>
                        <span class="legend-text" style="color: #22c55e;">Beweging</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-icon" style="color: #3b82f6;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h20M2 12l5-5m0 10l-5-5m20 0l-5-5m0 10l5-5"/></svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                        </span>
                        <span class="legend-text" style="color: #3b82f6;">Slaap</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-icon" style="color: #eab308;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M16 16s-1.5-2-4-2-4 2-4 2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                        </span>
                        <span class="legend-text" style="color: #eab308;">Stress</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-icon" style="color: #f97316;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
                        </span>
                        <span class="legend-text" style="color: #f97316;">Voeding</span>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="weeklyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Detailed Statistics Table -->
        <div class="table-section">
            <div class="table-block">
                <h2 class="table-title">Gedetailleerde Statistieken</h2>
                <div class="table-responsive">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Dag</th>
                                <th>Slaap</th>
                                <th>Voeding</th>
                                <th>Beweging</th>
                                <th>Stress</th>
                                <th>Hydratatie</th>
                                <th>Mentaal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Current Week -->
                            <tr>
                                <td>Ma</td>
                                <td>65</td>
                                <td>80</td>
                                <td>45</td>
                                <td>60</td>
                                <td>75</td>
                                <td>70</td>
                            </tr>
                            <tr>
                                <td>Di</td>
                                <td>72</td>
                                <td>78</td>
                                <td>52</td>
                                <td>58</td>
                                <td>78</td>
                                <td>72</td>
                            </tr>
                            <tr>
                                <td>Wo</td>
                                <td>68</td>
                                <td>82</td>
                                <td>48</td>
                                <td>62</td>
                                <td>76</td>
                                <td>71</td>
                            </tr>
                            <tr>
                                <td>Do</td>
                                <td>75</td>
                                <td>85</td>
                                <td>55</td>
                                <td>55</td>
                                <td>80</td>
                                <td>75</td>
                            </tr>
                            <tr>
                                <td>Vr</td>
                                <td>70</td>
                                <td>80</td>
                                <td>50</td>
                                <td>60</td>
                                <td>77</td>
                                <td>73</td>
                            </tr>
                            <tr>
                                <td>Za</td>
                                <td>80</td>
                                <td>75</td>
                                <td>65</td>
                                <td>50</td>
                                <td>82</td>
                                <td>78</td>
                            </tr>
                            <tr>
                                <td>Zo</td>
                                <td>78</td>
                                <td>79</td>
                                <td>60</td>
                                <td>52</td>
                                <td>81</td>
                                <td>76</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <?php include __DIR__ . '/../components/footer.php'; ?>
    <script src="/js/pwa.js"></script>
    <script>
        // Chart.js Configuration
        const ctx = document.getElementById('weeklyChart').getContext('2d');
        const weeklyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo'],
                datasets: [
                    {
                        label: 'Beweging',
                        data: [45, 52, 48, 55, 50, 65, 60],
                        borderColor: '#22c55e',
                        backgroundColor: '#22c55e',
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#22c55e',
                        pointBorderWidth: 2,
                        borderWidth: 2,
                        tension: 0.4
                    },
                    {
                        label: 'Slaap',
                        data: [65, 72, 68, 75, 70, 80, 78],
                        borderColor: '#3b82f6',
                        backgroundColor: '#3b82f6',
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#3b82f6',
                        pointBorderWidth: 2,
                        borderWidth: 2,
                        tension: 0.4
                    },
                    {
                        label: 'Stress',
                        data: [60, 58, 62, 55, 60, 50, 52],
                        borderColor: '#eab308',
                        backgroundColor: '#eab308',
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#eab308',
                        pointBorderWidth: 2,
                        borderWidth: 2,
                        tension: 0.4
                    },
                    {
                        label: 'Voeding',
                        data: [80, 78, 82, 85, 80, 75, 79],
                        borderColor: '#f97316',
                        backgroundColor: '#f97316',
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#f97316',
                        pointBorderWidth: 2,
                        borderWidth: 2,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            borderDash: [2, 2]
                        },
                        ticks: {
                            stepSize: 25
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>