<?php
session_start();
require_once '../config/config.php';
require_once '../config/connection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['login_error'] = "Please login as an administrator to access this page.";
    header("Location: ../auth/login.php");
    exit();
}

$error = null;

try {
    // Verify database connection
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get total counts for dashboard
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'etudiant'");
    $studentCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'enseignant'");
    $teacherCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get unapproved users count
    $unapprovedUsersCount = 0;
    try {
        // Use the columnExists function from config.php
        if (columnExists($conn, 'users', 'is_approved')) {
            $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE is_approved = 0");
            $unapprovedUsersCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } elseif (columnExists($conn, 'users', 'status')) {
            $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'inactive'");
            $unapprovedUsersCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } else {
            // If no approval-related column exists, log an error
            error_log("No approval column found in users table");
        }
    } catch(PDOException $e) {
        // Log the error but don't stop execution
        error_log("Error fetching unapproved users: " . $e->getMessage());
        $unapprovedUsersCount = 0;
    }

} catch(PDOException $e) {
    $error = "Connection failed: " . $e->getMessage();
    error_log($error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ExamMaster</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
        }
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
            z-index: 1;
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
        .card-counter {
            padding: 20px;
            border-radius: 10px;
            color: white;
            transition: transform 0.3s;
            cursor: pointer;
        }
        .card-counter:hover {
            transform: translateY(-3px);
        }
        .card-counter i {
            font-size: 4rem;
            opacity: 0.3;
        }
        .card-counter .count {
            font-size: 26px;
            font-weight: bold;
        }
        .admin-header {
            background: white;
            padding: 15px 25px;
            border-bottom: 1px solid #e3e6f0;
            margin-bottom: 25px;
        }
        .recent-activity {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 15px rgba(0,0,0,.1);
        }
        .divider {
            border-left: 1px solid rgba(255,255,255,.15);
            margin: 10px 0;
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
            <a href="dashboard.php" class="sidebar-link active">
                <i class='bx bxs-dashboard'></i> Dashboard
            </a>
            <a href="manage_users.php" class="sidebar-link">
                <i class='bx bxs-user-detail'></i> User Management
            </a>
            <a href="manage_classrooms.php" class="sidebar-link">
                <i class='bx bxs-school'></i> Manage Classrooms
            </a>
            <a href="manage_user_approvals.php" class="sidebar-link">
                <i class='bx bxs-user-check'></i> User Approvals
                <?php if($unapprovedUsersCount !== null && $unapprovedUsersCount > 0) { ?>
                    <span class="badge bg-danger ms-2"><?php echo htmlspecialchars($unapprovedUsersCount); ?></span>
                <?php } ?>
            </a>
            <a href="statistics.php" class="sidebar-link">
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
        <div class="admin-header d-flex justify-content-between align-items-center">
            <h4 class="m-0">Dashboard Overview</h4>
            <div>
                <span class="text-muted">Welcome, </span>
                <span class="fw-bold"><?php echo htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']); ?></span>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card-counter bg-primary h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1">Total Users</h6>
                            <div class="count"><?php echo $totalUsers; ?></div>
                        </div>
                        <i class='bx bxs-group'></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card-counter bg-success h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1">Students</h6>
                            <div class="count"><?php echo $studentCount; ?></div>
                        </div>
                        <i class='bx bxs-graduation'></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card-counter bg-info h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1">Teachers</h6>
                            <div class="count"><?php echo $teacherCount; ?></div>
                        </div>
                        <i class='bx bxs-chalkboard'></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card-counter bg-warning h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1">Active Exams</h6>
                            <div class="count">--</div>
                        </div>
                        <i class='bx bxs-book-content'></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card-counter bg-danger h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1">Pending Approvals</h6>
                            <div class="count"><?php if($unapprovedUsersCount !== null) { echo $unapprovedUsersCount; } else { echo 0; } ?></div>
                        </div>
                        <i class='bx bxs-user-x'></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title m-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <a href="add_user.php" class="btn btn-primary w-100 mb-3">
                            <i class='bx bx-user-plus'></i> Add New User
                        </a>
                        <a href="reports.php" class="btn btn-info w-100 text-white">
                            <i class='bx bx-line-chart'></i> Generate Reports
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="recent-activity">
                    <h5 class="mb-4">Recent Activity</h5>
                    <div class="activity-item d-flex align-items-center mb-3">
                        <i class='bx bxs-user-plus text-success me-3'></i>
                        <div>New user registration</div>
                        <small class="text-muted ms-auto">2 mins ago</small>
                    </div>
                    <div class="activity-item d-flex align-items-center mb-3">
                        <i class='bx bxs-book-content text-primary me-3'></i>
                        <div>New exam created</div>
                        <small class="text-muted ms-auto">1 hour ago</small>
                    </div>
                    <div class="activity-item d-flex align-items-center">
                        <i class='bx bxs-message-square-check text-info me-3'></i>
                        <div>Exam results published</div>
                        <small class="text-muted ms-auto">3 hours ago</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
