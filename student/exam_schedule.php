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

    // Get all exams for the student's class
    $stmt = $conn->prepare("
        SELECT e.*, 
               u.nom as teacher_nom, 
               u.prenom as teacher_prenom,
               s.name as subject_name,
               s.code as subject_code,
               (SELECT COUNT(*) FROM exam_attempts ea 
                WHERE ea.exam_id = e.id AND ea.student_id = ?) as attempt_count,
               (SELECT MAX(score) FROM exam_attempts ea 
                WHERE ea.exam_id = e.id AND ea.student_id = ? AND ea.submitted_at IS NOT NULL) as best_score,
               (SELECT started_at FROM exam_attempts ea 
                WHERE ea.exam_id = e.id AND ea.student_id = ? 
                AND ea.submitted_at IS NULL
                ORDER BY ea.started_at DESC LIMIT 1) as current_attempt_start
        FROM exams e
        JOIN users u ON e.created_by = u.id
        LEFT JOIN subjects s ON e.subject_id = s.id
        WHERE (e.start_date >= CURDATE() - INTERVAL 7 DAY)
        AND (
            e.class_id IS NULL 
            OR e.class_id = (SELECT class_id FROM users WHERE id = ?)
        )
        ORDER BY e.start_date ASC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group exams by month and date
    $grouped_exams = [];
    foreach ($exams as $exam) {
        $month = date('F Y', strtotime($exam['start_date']));
        $date = date('Y-m-d', strtotime($exam['start_date']));
        
        // Calculate exam status
        $now = new DateTime();
        $start_date = new DateTime($exam['start_date']);
        $end_date = new DateTime($exam['end_date']);
        
        if ($now < $start_date) {
            $exam['status'] = 'upcoming';
            $exam['status_text'] = 'Not Started';
        } elseif ($now >= $start_date && $now <= $end_date) {
            if ($exam['current_attempt_start']) {
                $exam['status'] = 'in_progress';
                $exam['status_text'] = 'In Progress';
            } else {
                $exam['status'] = 'available';
                $exam['status_text'] = 'Available';
            }
        } else {
            if ($exam['best_score'] !== null) {
                $exam['status'] = 'completed';
                $exam['status_text'] = 'Completed';
            } else {
                $exam['status'] = 'missed';
                $exam['status_text'] = 'Missed';
            }
        }
        
        if (!isset($grouped_exams[$month])) {
            $grouped_exams[$month] = [];
        }
        if (!isset($grouped_exams[$month][$date])) {
            $grouped_exams[$month][$date] = [];
        }
        $grouped_exams[$month][$date][] = $exam;
    }

} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "An error occurred while loading the exam schedule. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Schedule - ExamMaster</title>
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
        .sidebar-link.active {
            background: rgba(255,255,255,.1);
            color: white;
        }
        .sidebar-link i {
            margin-right: 10px;
        }
        .exam-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .exam-card:hover {
            transform: translateY(-5px);
        }
        .date-header {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .month-header {
            color: #2c3e50;
            margin-top: 30px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
        }
        .exam-time {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .subject-code {
            font-size: 0.8rem;
            padding: 3px 8px;
            background: #e9ecef;
            border-radius: 4px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="text-center mb-4">
            <h4>ExamMaster</h4>
        </div>
        <a href="dashboard.php" class="sidebar-link">
            <i class='bx bxs-dashboard'></i> Dashboard
        </a>
        <a href="my_exams.php" class="sidebar-link">
            <i class='bx bx-book-content'></i> My Exams
        </a>
        <a href="exam_schedule.php" class="sidebar-link active">
            <i class='bx bx-calendar'></i> Exam Schedule
        </a>
        <a href="my_results.php" class="sidebar-link">
            <i class='bx bx-bar-chart-alt-2'></i> My Results
        </a>
        <a href="messages.php" class="sidebar-link">
            <i class='bx bx-message-square-detail'></i> Messages
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

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Exam Schedule</h2>
                <div class="btn-group">
                    <a href="?view=calendar" class="btn btn-outline-primary">
                        <i class='bx bx-calendar'></i> Calendar View
                    </a>
                    <a href="?view=list" class="btn btn-outline-primary">
                        <i class='bx bx-list-ul'></i> List View
                    </a>
                </div>
            </div>

            <?php if (empty($grouped_exams)): ?>
                <div class="text-center py-5">
                    <i class='bx bx-calendar bx-lg text-muted'></i>
                    <p class="mt-3 text-muted">No upcoming exams scheduled.</p>
                </div>
            <?php else: ?>
                <?php foreach ($grouped_exams as $month => $dates): ?>
                    <h3 class="month-header"><?php echo $month; ?></h3>
                    <?php foreach ($dates as $date => $day_exams): ?>
                        <div class="date-header">
                            <h5 class="mb-0">
                                <?php echo date('l, F j', strtotime($date)); ?>
                            </h5>
                        </div>
                        <div class="row">
                            <?php foreach ($day_exams as $exam): ?>
                                <div class="col-md-6">
                                    <div class="exam-card card">
                                        <span class="badge <?php echo $exam['status'] == 'upcoming' ? 'bg-info' : ($exam['status'] == 'in_progress' ? 'bg-success' : ($exam['status'] == 'available' ? 'bg-primary' : ($exam['status'] == 'completed' ? 'bg-success' : 'bg-secondary'))); ?> status-badge">
                                            <?php echo $exam['status_text']; ?>
                                        </span>
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="subject-code"><?php echo htmlspecialchars($exam['subject_code']); ?></span>
                                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($exam['title']); ?></h5>
                                            </div>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    Subject: <?php echo htmlspecialchars($exam['subject_name']); ?>
                                                </small>
                                            </p>
                                            <div class="exam-time mb-3">
                                                <i class='bx bx-time'></i> 
                                                <?php echo date('H:i', strtotime($exam['start_date'])); ?> - 
                                                <?php echo date('H:i', strtotime($exam['end_date'])); ?>
                                                (<?php echo $exam['duration']; ?> minutes)
                                            </div>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    Teacher: <?php echo htmlspecialchars($exam['teacher_prenom'] . ' ' . $exam['teacher_nom']); ?>
                                                </small>
                                            </p>
                                            <?php if ($exam['attempt_count'] > 0): ?>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="badge bg-warning">Already attempted</span>
                                                    <?php if ($exam['best_score'] !== null): ?>
                                                        <span class="badge <?php echo $exam['best_score'] >= 50 ? 'bg-success' : 'bg-danger'; ?>">
                                                            Best Score: <?php echo number_format($exam['best_score'], 1); ?>%
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php elseif ($exam['status'] == 'available'): ?>
                                                <a href="take_exam.php?id=<?php echo $exam['id']; ?>" 
                                                   class="btn btn-primary">Start Exam</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
