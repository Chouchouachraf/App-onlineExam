<?php
session_start();
require_once '../config/connection.php';

// Define base path if not defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'etudiant') {
    $_SESSION['login_error'] = "Please login as a student to access this page.";
    header("Location: ../auth/login.php");
    exit();
}

try {
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

    // Get exam statistics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(CASE WHEN NOW() < e.exam_date THEN 1 END) as upcoming_count,
            COUNT(CASE WHEN NOW() >= e.exam_date AND NOW() <= e.end_time AND es.id IS NULL THEN 1 END) as available_count,
            COUNT(CASE WHEN es.id IS NOT NULL THEN 1 END) as submitted_count,
            AVG(CASE WHEN es.id IS NOT NULL THEN es.total_score END) as average_score
        FROM exams e
        LEFT JOIN exam_submissions es ON e.id = es.exam_id AND es.student_id = ?
        WHERE e.class_id = ? OR e.class_id IS NULL
    ");
    $stmt->execute([$_SESSION['user_id'], $user_details['class_id']]);
    $exam_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get recent exams
    $stmt = $conn->prepare("
        SELECT e.*, 
               s.name as subject_name,
               es.total_score,
               es.submitted_at,
               CASE 
                   WHEN es.id IS NOT NULL THEN 'Submitted'
                   WHEN NOW() > e.end_time THEN 'Expired'
                   WHEN NOW() < e.exam_date THEN 'Upcoming'
                   ELSE 'Available'
               END as status
        FROM exams e
        LEFT JOIN subjects s ON e.subject_id = s.id
        LEFT JOIN exam_submissions es ON e.id = es.exam_id AND es.student_id = ?
        WHERE e.class_id = ? OR e.class_id IS NULL
        ORDER BY e.exam_date DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id'], $user_details['class_id']]);
    $recent_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while fetching dashboard data.";
}

$page_title = "Student Dashboard";
include '../includes/header.php';
?>

<div class="container py-4">
    <!-- Welcome Section -->
    <div class="card welcome-card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-1">Welcome Back!</h2>
                    <p class="mb-0">
                        Class: <?php echo htmlspecialchars($user_details['class_name']); ?> | 
                        Department: <?php echo htmlspecialchars($user_details['department_name']); ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <p class="mb-0">
                        <span class="badge bg-primary"><?php echo (int)$exam_stats['upcoming_count']; ?> Upcoming</span>
                        <span class="badge bg-success"><?php echo (int)$exam_stats['available_count']; ?> Available</span>
                        <span class="badge bg-info"><?php echo (int)$exam_stats['submitted_count']; ?> Submitted</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Upcoming Exams</h5>
                    <h2 class="mb-0"><?php echo (int)$exam_stats['upcoming_count']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Available Now</h5>
                    <h2 class="mb-0"><?php echo (int)$exam_stats['available_count']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Completed</h5>
                    <h2 class="mb-0"><?php echo (int)$exam_stats['submitted_count']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Average Score</h5>
                    <h2 class="mb-0"><?php echo $exam_stats['average_score'] ? number_format($exam_stats['average_score'], 1) : 'N/A'; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Exams -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Recent Exams</h4>
            <a href="exam_schedule.php" class="btn btn-primary btn-sm">View All</a>
        </div>
        <div class="card-body">
            <?php if (empty($recent_exams)): ?>
                <div class="text-center py-3">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <p class="mb-0">No exams available yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Score</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_exams as $exam): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($exam['subject_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($exam['exam_date'])); ?></td>
                                    <td>
                                        <?php
                                        $status_class = [
                                            'Submitted' => 'bg-info',
                                            'Available' => 'bg-success',
                                            'Upcoming' => 'bg-primary',
                                            'Expired' => 'bg-secondary'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $status_class[$exam['status']]; ?>">
                                            <?php echo $exam['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($exam['status'] === 'Submitted'): ?>
                                            <?php echo $exam['total_score']; ?>%
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($exam['status'] === 'Available'): ?>
                                            <a href="take_exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-primary btn-sm">Take Exam</a>
                                        <?php elseif ($exam['status'] === 'Submitted'): ?>
                                            <a href="view_result.php?id=<?php echo $exam['id']; ?>" class="btn btn-info btn-sm">View Result</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
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

<?php include '../includes/footer.php'; ?>
