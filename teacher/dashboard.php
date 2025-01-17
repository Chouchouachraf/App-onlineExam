<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'enseignant') {
    header("Location: ../auth/login.php");
    exit();
}

$host = 'localhost';
$dbname = 'exammaster';
$user = 'root';
$pass = '';

// Initialize variables
$exams = [];
$total_students = 0;
$error_message = null;

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get teacher's exams with basic information
    $stmt = $conn->prepare("
        SELECT e.*, 
               COUNT(DISTINCT ea.id) as attempt_count,
               COUNT(DISTINCT CASE WHEN ea.submit_time IS NOT NULL THEN ea.id END) as submitted_count,
               COUNT(DISTINCT CASE WHEN ea.submit_time IS NOT NULL AND NOT EXISTS (
                   SELECT 1 FROM student_answers sa 
                   WHERE sa.attempt_id = ea.id AND sa.is_graded = 0
               ) THEN ea.id END) as graded_count
        FROM exams e
        LEFT JOIN exam_attempts ea ON e.id = ea.exam_id
        WHERE e.created_by = ?
        GROUP BY e.id, e.title, e.description, e.start_date, e.end_date, e.duration, e.created_at, e.created_by
        ORDER BY e.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total number of students
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'etudiant' AND status = 'active'");
    $stmt->execute();
    $total_students = $stmt->fetchColumn();

} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "An error occurred while loading the dashboard. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - ExamMaster</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        .sidebar {
            height: 100vh;
            background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            padding-top: 20px;
            color: white;
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
            display: block;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-link:hover {
            color: white;
            background: rgba(255,255,255,.1);
            text-decoration: none;
        }
        .sidebar-link.active {
            background: rgba(255,255,255,.1);
            color: white;
        }
        .card {
            box-shadow: 0 0.15rem 1.75rem 0 rgb(58 59 69 / 15%);
            margin-bottom: 20px;
        }
        .card-stats {
            border-left: 4px solid;
        }
        .card-stats.primary { border-color: #4e73df; }
        .card-stats.success { border-color: #1cc88a; }
        .card-stats.warning { border-color: #f6c23e; }
        .card-stats.danger { border-color: #e74a3b; }
        .stats-icon {
            font-size: 1.5rem;
            opacity: 0.3;
        }
        .table td, .table th {
            vertical-align: middle;
        }
        .badge {
            font-size: 85%;
        }
        .status-badge {
            min-width: 80px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="text-center mb-4">
            <h4>ExamMaster</h4>
        </div>
        <a href="dashboard.php" class="sidebar-link active">
            <i class='bx bxs-dashboard'></i> Dashboard
        </a>
        <a href="create_exam.php" class="sidebar-link">
            <i class='bx bx-plus-circle'></i> Create Exam
        </a>
        <a href="#exams" class="sidebar-link" data-bs-toggle="collapse">
            <i class='bx bx-book'></i> Exams
            <i class='bx bx-chevron-down ms-auto'></i>
        </a>
        <div class="collapse" id="exams">
            <a href="?view=active" class="sidebar-link ps-4">
                <i class='bx bx-checkbox-square'></i> Active Exams
            </a>
            <a href="?view=past" class="sidebar-link ps-4">
                <i class='bx bx-archive'></i> Past Exams
            </a>
        </div>
        <a href="#submissions" class="sidebar-link" data-bs-toggle="collapse">
            <i class='bx bx-task'></i> Submissions
            <i class='bx bx-chevron-down ms-auto'></i>
        </a>
        <div class="collapse" id="submissions">
            <a href="?view=pending" class="sidebar-link ps-4">
                <i class='bx bx-time'></i> Pending Review
            </a>
            <a href="?view=graded" class="sidebar-link ps-4">
                <i class='bx bx-check-double'></i> Graded
            </a>
        </div>
        <a href="../auth/logout.php" class="sidebar-link">
            <i class='bx bx-log-out'></i> Logout
        </a>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card card-stats primary">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h6 class="text-muted mb-1">Total Exams</h6>
                                    <h4 class="mb-0"><?php echo count($exams); ?></h4>
                                </div>
                                <div class="col-auto">
                                    <div class="stats-icon text-primary">
                                        <i class='bx bx-book'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-stats success">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h6 class="text-muted mb-1">Total Students</h6>
                                    <h4 class="mb-0"><?php echo $total_students; ?></h4>
                                </div>
                                <div class="col-auto">
                                    <div class="stats-icon text-success">
                                        <i class='bx bx-user'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-stats warning">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h6 class="text-muted mb-1">Pending Review</h6>
                                    <h4 class="mb-0"><?php 
                                        $pending = array_sum(array_map(function($exam) {
                                            return $exam['submitted_count'] - $exam['graded_count'];
                                        }, $exams));
                                        echo $pending;
                                    ?></h4>
                                </div>
                                <div class="col-auto">
                                    <div class="stats-icon text-warning">
                                        <i class='bx bx-time'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-stats danger">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h6 class="text-muted mb-1">Active Exams</h6>
                                    <h4 class="mb-0"><?php 
                                        echo array_sum(array_map(function($exam) {
                                            return strtotime($exam['end_date']) > time() ? 1 : 0;
                                        }, $exams));
                                    ?></h4>
                                </div>
                                <div class="col-auto">
                                    <div class="stats-icon text-danger">
                                        <i class='bx bx-calendar-check'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Exams Table -->
            <div class="card">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h5 class="m-0 font-weight-bold">Your Exams</h5>
                    <a href="create_exam.php" class="btn btn-primary btn-sm">
                        <i class='bx bx-plus'></i> Create New Exam
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($exams)): ?>
                        <div class="text-center py-4">
                            <i class='bx bx-book-content' style="font-size: 4rem; color: #ccc;"></i>
                            <p class="mt-3">No exams created yet. Start by creating your first exam!</p>
                            <a href="create_exam.php" class="btn btn-primary">Create Exam</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Date</th>
                                        <th>Duration</th>
                                        <th>Submissions</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($exams as $exam): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                            <td>
                                                <?php 
                                                    echo date('M d, Y', strtotime($exam['start_date']));
                                                    if ($exam['start_date'] != $exam['end_date']) {
                                                        echo ' - ' . date('M d, Y', strtotime($exam['end_date']));
                                                    }
                                                ?>
                                            </td>
                                            <td><?php echo $exam['duration']; ?> min</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php echo $exam['submitted_count']; ?> / <?php echo $exam['attempt_count']; ?>
                                                    <?php if ($exam['submitted_count'] > $exam['graded_count']): ?>
                                                        <span class="badge bg-warning ms-2">
                                                            <?php echo $exam['submitted_count'] - $exam['graded_count']; ?> pending
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                    $now = time();
                                                    $start = strtotime($exam['start_date']);
                                                    $end = strtotime($exam['end_date']);
                                                    if ($now < $start) {
                                                        echo '<span class="badge bg-info status-badge">Upcoming</span>';
                                                    } elseif ($now >= $start && $now <= $end) {
                                                        echo '<span class="badge bg-success status-badge">Active</span>';
                                                    } else {
                                                        echo '<span class="badge bg-secondary status-badge">Ended</span>';
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="view_submissions.php?exam_id=<?php echo $exam['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class='bx bx-task'></i> Submissions
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split" 
                                                            data-bs-toggle="dropdown">
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="edit_exam.php?id=<?php echo $exam['id']; ?>">
                                                                <i class='bx bx-edit'></i> Edit
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="add_questions.php?exam_id=<?php echo $exam['id']; ?>">
                                                                <i class='bx bx-list-plus'></i> Questions
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="#" 
                                                               onclick="if(confirm('Are you sure you want to delete this exam?')) 
                                                                        window.location.href='delete_exam.php?id=<?php echo $exam['id']; ?>'">
                                                                <i class='bx bx-trash'></i> Delete
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
