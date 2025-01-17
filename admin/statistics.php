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
$dbname = 'exammaster';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get user statistics
    $stmt = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $roleStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM users GROUP BY status");
    $statusStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get monthly user registrations for the past 12 months
    $stmt = $conn->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
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
        /* Include the same sidebar styles as dashboard.php */
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
        }
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 15px rgba(0,0,0,.1);
        }
    </style>
</head>
<body>
    <!-- Include the same sidebar as dashboard.php -->
    <div class="sidebar">
        <!-- Same sidebar content as dashboard.php -->
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4">System Statistics</h2>

            <div class="row">
                <!-- User Roles Chart -->
                <div class="col-md-6 mb-4">
                    <div class="chart-container">
                        <h5>User Distribution by Role</h5>
                        <canvas id="roleChart"></canvas>
                    </div>
                </div>

                <!-- User Status Chart -->
                <div class="col-md-6 mb-4">
                    <div class="chart-container">
                        <h5>User Status Distribution</h5>
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <!-- Monthly Registrations Chart -->
                <div class="col-12">
                    <div class="chart-container">
                        <h5>Monthly User Registrations</h5>
                        <canvas id="registrationChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Role Distribution Chart
        new Chart(document.getElementById('roleChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($roleStats, 'role')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($roleStats, 'count')); ?>,
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Status Distribution Chart
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($statusStats, 'status')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($statusStats, 'count')); ?>,
                    backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Monthly Registrations Chart
        new Chart(document.getElementById('registrationChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthlyStats, 'month')); ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?php echo json_encode(array_column($monthlyStats, 'count')); ?>,
                    borderColor: '#4e73df',
                    tension: 0.1,
                    fill: true,
                    backgroundColor: 'rgba(78, 115, 223, 0.1)'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
