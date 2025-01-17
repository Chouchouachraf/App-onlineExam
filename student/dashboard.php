<?php
session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'etudiant') {
    $_SESSION['login_error'] = "Please login as a student to access this page.";
    header("Location: ../auth/login.php");
    exit();
}

$host = 'localhost';
$dbname = 'exammaster';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get user details with additional info
    $stmt = $conn->prepare("
        SELECT u.*, 
               COALESCE(c.name, 'Not Assigned') as class_name,
               COALESCE(d.name, 'Not Assigned') as department_name
        FROM users u
        LEFT JOIN classes c ON u.class_id = c.id
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.id = ? AND u.role = 'etudiant'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_details = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_details) {
        $_SESSION['login_error'] = "User not found or not authorized. Please login again.";
        header("Location: ../auth/login.php");
        exit();
    }

    // Initialize variables with default values
    $statistics = [
        'total_exams_taken' => 0,
        'average_score' => 0,
        'passed_exams' => 0
    ];

    $upcoming_exams = [];

    try {
        // Get exam statistics
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_exams_taken,
                COALESCE(AVG(ea.score), 0) as average_score,
                SUM(CASE WHEN ea.score >= e.passing_score THEN 1 ELSE 0 END) as passed_exams
            FROM exam_attempts ea
            JOIN exams e ON ea.exam_id = e.id
            WHERE ea.student_id = ? AND ea.submit_time IS NOT NULL
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($stats) {
            $statistics = $stats;
        }

        // Get upcoming exams
        $stmt = $conn->prepare("
            SELECT e.*, 
                   u.nom as teacher_nom, u.prenom as teacher_prenom,
                   s.name as subject_name
            FROM exams e
            JOIN users u ON e.created_by = u.id
            LEFT JOIN subjects s ON e.subject_id = s.id
            WHERE e.start_date > NOW()
            AND (e.class_id IS NULL OR e.class_id = ?)
            ORDER BY e.start_date ASC
            LIMIT 5
        ");
        $stmt->execute([$user_details['class_id']]);
        $upcoming_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching dashboard data: " . $e->getMessage());
    }

    // Get available exams (ongoing exams)
    $stmt = $conn->prepare("
        SELECT e.*, u.nom as teacher_nom, u.prenom as teacher_prenom,
               s.name as subject_name,
               (SELECT COUNT(*) FROM exam_attempts ea 
                WHERE ea.exam_id = e.id AND ea.student_id = ?) as attempt_count
        FROM exams e
        JOIN users u ON e.created_by = u.id
        LEFT JOIN subjects s ON e.subject_id = s.id
        WHERE e.start_date <= NOW() 
        AND e.end_date >= NOW()
        AND (
            e.class_id IS NULL 
            OR e.class_id = (SELECT class_id FROM users WHERE id = ?)
        )
        ORDER BY e.end_date ASC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $available_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent exam results
    $stmt = $conn->prepare("
        SELECT ea.*, e.title, e.duration, s.name as subject_name,
               u.nom as teacher_nom, u.prenom as teacher_prenom
        FROM exam_attempts ea
        JOIN exams e ON ea.exam_id = e.id
        JOIN users u ON e.created_by = u.id
        LEFT JOIN subjects s ON e.subject_id = s.id
        WHERE ea.student_id = ?
        AND ea.submit_time IS NOT NULL
        ORDER BY ea.submit_time DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get notifications
    $stmt = $conn->prepare("
        SELECT n.*, e.title as exam_title
        FROM notifications n
        LEFT JOIN exams e ON n.related_id = e.id
        WHERE n.user_id = ?
        AND n.read_at IS NULL
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - ExamMaster</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/font/boxicons.min.css" rel="stylesheet">
    <style>
        .sidebar {
            height: 100vh;
            background: linear-gradient(180deg, #2c3e50 0%, #3498db 100%);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            padding-top: 20px;
            z-index: 1000;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .sidebar-link {
            color: rgba(255,255,255,.8);
            text-decoration: none;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            transition: 0.3s;
        }
        .sidebar-link:hover {
            background: rgba(255,255,255,.1);
            color: white;
            text-decoration: none;
        }
        .sidebar-link i {
            margin-right: 10px;
        }
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stats-card {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            border: none;
            height: 100%;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .exam-card {
            border-radius: 15px;
            transition: transform 0.3s;
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .exam-card:hover {
            transform: translateY(-5px);
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            padding: 5px 8px;
            border-radius: 50%;
            background: #e74c3c;
            color: white;
            font-size: 12px;
        }
        .progress {
            height: 10px;
            border-radius: 5px;
        }
        .notification-item {
            padding: 10px;
            border-left: 4px solid #3498db;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .notification-item.unread {
            background: #e8f4f8;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="text-center mb-4">
            <h4>ExamMaster</h4>
        </div>
        <a href="dashboard.php" class="sidebar-link active">
            <i class='bx bxs-dashboard'></i> Dashboard
        </a>
        <a href="my_exams.php" class="sidebar-link">
            <i class='bx bx-book-content'></i> My Exams
        </a>
        <a href="exam_schedule.php" class="sidebar-link">
            <i class='bx bx-calendar'></i> Exam Schedule
        </a>
        <a href="my_results.php" class="sidebar-link">
            <i class='bx bx-bar-chart-alt-2'></i> My Results
        </a>
        <a href="messages.php" class="sidebar-link">
            <i class='bx bx-message-square-detail'></i> Messages
            <?php if (isset($notifications) && count($notifications) > 0): ?>
                <span class="notification-badge"><?php echo count($notifications); ?></span>
            <?php endif; ?>
        </a>
        <a href="profile.php" class="sidebar-link">
            <i class='bx bx-user'></i> Profile
        </a>
        <a href="../auth/logout.php" class="sidebar-link">
            <i class='bx bx-log-out'></i> Logout
        </a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2>Welcome, <?php echo htmlspecialchars($user_details['prenom'] . ' ' . $user_details['nom']); ?>!</h2>
                        <p class="mb-0">
                            Class: <?php echo htmlspecialchars($user_details['class_name']); ?> | 
                            Department: <?php echo htmlspecialchars($user_details['department_name']); ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="exam_schedule.php" class="btn btn-light">View Schedule</a>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card card bg-primary text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="text-uppercase mb-1">Total Exams</h6>
                                    <h3 class="mb-0"><?php echo $statistics['total_exams_taken']; ?></h3>
                                </div>
                                <div class="col-auto">
                                    <i class='bx bx-book-content bx-lg'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card card bg-success text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="text-uppercase mb-1">Average Score</h6>
                                    <h3 class="mb-0"><?php echo number_format($statistics['average_score'], 1); ?>%</h3>
                                </div>
                                <div class="col-auto">
                                    <i class='bx bx-bar-chart-alt-2 bx-lg'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card card bg-info text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="text-uppercase mb-1">Passed Exams</h6>
                                    <h3 class="mb-0"><?php echo $statistics['passed_exams']; ?></h3>
                                </div>
                                <div class="col-auto">
                                    <i class='bx bx-check-circle bx-lg'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card card bg-warning text-white">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="text-uppercase mb-1">Upcoming Exams</h6>
                                    <h3 class="mb-0"><?php echo count($upcoming_exams); ?></h3>
                                </div>
                                <div class="col-auto">
                                    <i class='bx bx-calendar bx-lg'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Available Exams -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Available Exams</h5>
                            <a href="exam_schedule.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($available_exams)): ?>
                                <p class="text-muted text-center mb-0">No exams available right now.</p>
                            <?php else: ?>
                                <?php foreach ($available_exams as $exam): ?>
                                    <div class="exam-card card mb-3">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-md-8">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($exam['title']); ?></h5>
                                                    <p class="card-text mb-1">
                                                        <small class="text-muted">
                                                            Subject: <?php echo htmlspecialchars($exam['subject_name']); ?> |
                                                            Duration: <?php echo $exam['duration']; ?> minutes
                                                        </small>
                                                    </p>
                                                    <p class="card-text">
                                                        <small class="text-muted">
                                                            Teacher: <?php echo htmlspecialchars($exam['teacher_prenom'] . ' ' . $exam['teacher_nom']); ?>
                                                        </small>
                                                    </p>
                                                </div>
                                                <div class="col-md-4 text-end">
                                                    <?php if ($exam['attempt_count'] > 0): ?>
                                                        <span class="badge bg-warning">Already attempted</span>
                                                    <?php else: ?>
                                                        <a href="take_exam.php?id=<?php echo $exam['id']; ?>" 
                                                           class="btn btn-primary">Start Exam</a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Results -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Results</h5>
                            <a href="my_results.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_results)): ?>
                                <p class="text-muted text-center mb-0">No exam results yet.</p>
                            <?php else: ?>
                                <?php foreach ($recent_results as $result): ?>
                                    <div class="exam-card card mb-3">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-md-8">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($result['title']); ?></h5>
                                                    <p class="card-text mb-1">
                                                        <small class="text-muted">
                                                            Subject: <?php echo htmlspecialchars($result['subject_name']); ?> |
                                                            Submitted: <?php echo date('M d, Y H:i', strtotime($result['submit_time'])); ?>
                                                        </small>
                                                    </p>
                                                    <div class="progress mt-2">
                                                        <div class="progress-bar <?php echo $result['score'] >= 50 ? 'bg-success' : 'bg-danger'; ?>" 
                                                             role="progressbar" 
                                                             style="width: <?php echo $result['score']; ?>%" 
                                                             aria-valuenow="<?php echo $result['score']; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 text-end">
                                                    <h4 class="mb-0 <?php echo $result['score'] >= 50 ? 'text-success' : 'text-danger'; ?>">
                                                        <?php echo number_format($result['score'], 1); ?>%
                                                    </h4>
                                                    <a href="view_result.php?attempt_id=<?php echo $result['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary mt-2">View Details</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Content -->
                <div class="col-md-4">
                    <!-- Upcoming Exams -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Upcoming Exams</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($upcoming_exams)): ?>
                                <p class="text-muted text-center mb-0">No upcoming exams.</p>
                            <?php else: ?>
                                <?php foreach ($upcoming_exams as $exam): ?>
                                    <div class="notification-item">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($exam['title']); ?></h6>
                                        <p class="mb-1">
                                            <small>
                                                <i class='bx bx-calendar'></i> 
                                                <?php echo date('M d, Y H:i', strtotime($exam['start_date'])); ?>
                                            </small>
                                        </p>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($exam['subject_name']); ?> |
                                            <?php echo $exam['duration']; ?> minutes
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Notifications</h5>
                            <a href="messages.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($notifications)): ?>
                                <p class="text-muted text-center mb-0">No new notifications.</p>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="notification-item unread">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
