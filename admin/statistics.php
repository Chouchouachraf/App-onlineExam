<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['login_error'] = "Please login as an administrator to access this page.";
    header("Location: ../auth/login.php");
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'schemase';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get user statistics
    $stmt = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $userStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get user status statistics
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM users GROUP BY status");
    $statusStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent registrations (last 7 days)
    $stmt = $conn->query("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM users 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    $recentRegistrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error = "Connection failed: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics - ExamMaster Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar {
            height: 100vh;
            background: #4e73df;
            background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            padding-top: 20px;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            background-color: #f8f9fc;
            min-height: 100vh;
        }
        .sidebar-link {
            color: rgba(255,255,255,.8);
            text-decoration: none;
            padding: 15px 20px;
            display: block;
            transition: 0.3s;
            border-left: 3px solid transparent;
        }
        .sidebar-link:hover {
            color: white;
            background: rgba(255,255,255,.1);
            border-left-color: white;
        }
        .sidebar-link i {
            margin-right: 10px;
        }
        .divider {
            border-top: 1px solid rgba(255,255,255,.15);
            margin: 10px 0;
        }
        .stats-card {
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transition: transform 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-3px);
        }
        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="text-center mb-4">
            <h4>ExamMaster</h4>
            <small>Administration Panel</small>
        </div>
        <div class="divider"></div>
        <nav>
            <a href="dashboard.php" class="sidebar-link">
                <i class='bx bxs-dashboard'></i> Dashboard
            </a>
            <a href="manage_users.php" class="sidebar-link">
                <i class='bx bxs-user-detail'></i> User Management
            </a>
            <a href="statistics.php" class="sidebar-link active">
                <i class='bx bxs-chart'></i> Statistics
            </a>
            <div class="divider"></div>
            <a href="../auth/logout.php" class="sidebar-link">
                <i class='bx bx-log-out'></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Header with Return Button -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>System Statistics</h2>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class='bx bxs-dashboard'></i> Return to Dashboard
                </a>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <!-- User Distribution -->
                <div class="col-lg-6 mb-4">
                    <div class="card stats-card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">User Distribution</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="userDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Status -->
                <div class="col-lg-6 mb-4">
                    <div class="card stats-card">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">User Status Distribution</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="userStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Registrations -->
            <div class="row">
                <div class="col-12">
                    <div class="card stats-card">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">Recent Registrations (Last 7 Days)</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="registrationTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // User Distribution Chart
        const userDistributionCtx = document.getElementById('userDistributionChart').getContext('2d');
        new Chart(userDistributionCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($userStats, 'role')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($userStats, 'count')); ?>,
                    backgroundColor: [
                        '#4e73df',
                        '#1cc88a',
                        '#36b9cc'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // User Status Chart
        const userStatusCtx = document.getElementById('userStatusChart').getContext('2d');
        new Chart(userStatusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($statusStats, 'status')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($statusStats, 'count')); ?>,
                    backgroundColor: [
                        '#1cc88a',
                        '#f6c23e',
                        '#e74a3b'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Registration Trend Chart
        const registrationTrendCtx = document.getElementById('registrationTrendChart').getContext('2d');
        new Chart(registrationTrendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($recentRegistrations, 'date')); ?>,
                datasets: [{
                    label: 'New Registrations',
                    data: <?php echo json_encode(array_column($recentRegistrations, 'count')); ?>,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
