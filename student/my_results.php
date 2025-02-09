<?php
session_start();
require_once '../config/connection.php';

// Define base path
define('BASE_PATH', dirname(__DIR__));

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

    // Get all submitted exams with results
    $stmt = $conn->prepare("
        SELECT e.*, 
               u.nom as teacher_nom, 
               u.prenom as teacher_prenom,
               s.name as subject_name,
               c.name as class_name,
               es.submission_time,
               es.total_score,
               (SELECT COUNT(*) FROM student_answers sa 
                WHERE sa.submission_id = es.id AND sa.is_correct = 1) as correct_answers,
               (SELECT COUNT(*) FROM questions q 
                WHERE q.exam_id = e.id) as total_questions
        FROM exam_submissions es
        JOIN exams e ON es.exam_id = e.id
        JOIN users u ON e.created_by = u.id
        LEFT JOIN subjects s ON e.subject_id = s.id
        LEFT JOIN classes c ON e.class_id = c.id
        WHERE es.student_id = ?
        ORDER BY es.submission_time DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate statistics
    $total_exams = count($results);
    $total_score = 0;
    $passed_exams = 0;
    $perfect_score = 0;

    foreach ($results as $result) {
        $total_score += $result['total_score'];
        if ($result['total_score'] >= 50) $passed_exams++;
        if ($result['total_score'] == 100) $perfect_score++;
    }

    $average_score = $total_exams > 0 ? $total_score / $total_exams : 0;

} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Set page title
$page_title = "My Results";
include '../includes/header.php';
?>

<div class="container py-4">
    <!-- Welcome Section -->
    <div class="card welcome-card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">My Results</h2>
                    <p class="mb-0">
                        Class: <?php echo htmlspecialchars($user_details['class_name']); ?> |
                        Department: <?php echo htmlspecialchars($user_details['department_name']); ?>
                    </p>
                </div>
                <div class="text-end">
                    <h4 class="mb-1">Overall Performance</h4>
                    <p class="mb-0">
                        Average Score: <?php echo number_format($average_score, 1); ?>%
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center fade-in">
                <div class="card-body">
                    <i class='bx bx-book text-primary' style="font-size: 2rem;"></i>
                    <h3 class="mt-2"><?php echo $total_exams; ?></h3>
                    <p class="text-muted mb-0">Total Exams</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center fade-in">
                <div class="card-body">
                    <i class='bx bx-check-circle text-success' style="font-size: 2rem;"></i>
                    <h3 class="mt-2"><?php echo $passed_exams; ?></h3>
                    <p class="text-muted mb-0">Passed Exams</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center fade-in">
                <div class="card-body">
                    <i class='bx bx-trophy text-warning' style="font-size: 2rem;"></i>
                    <h3 class="mt-2"><?php echo $perfect_score; ?></h3>
                    <p class="text-muted mb-0">Perfect Scores</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center fade-in">
                <div class="card-body">
                    <i class='bx bx-bar-chart-alt-2 text-info' style="font-size: 2rem;"></i>
                    <h3 class="mt-2"><?php echo number_format($average_score, 1); ?>%</h3>
                    <p class="text-muted mb-0">Average Score</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Table -->
    <?php if (!empty($results)): ?>
        <div class="card mb-4 fade-in">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class='bx bx-list-ul'></i> Exam Results
                </h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Exam</th>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Submission Date</th>
                                <th>Score</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $result): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($result['title']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($result['subject_name']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($result['teacher_prenom'] . ' ' . $result['teacher_nom']); ?>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y H:i', strtotime($result['submission_time'])); ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php 
                                            $score_color = $result['total_score'] >= 80 ? 'success' : 
                                                         ($result['total_score'] >= 50 ? 'warning' : 'danger');
                                            ?>
                                            <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                <div class="progress-bar bg-<?php echo $score_color; ?>" 
                                                     role="progressbar" 
                                                     style="width: <?php echo $result['total_score']; ?>%">
                                                </div>
                                            </div>
                                            <span class="badge bg-<?php echo $score_color; ?>">
                                                <?php echo number_format($result['total_score'], 1); ?>%
                                            </span>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo $result['correct_answers']; ?>/<?php echo $result['total_questions']; ?> correct
                                        </small>
                                    </td>
                                    <td>
                                        <a href="view_result.php?exam_id=<?php echo $result['id']; ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class='bx bx-show'></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card text-center fade-in">
            <div class="card-body py-5">
                <i class='bx bx-notepad text-muted' style="font-size: 4rem;"></i>
                <h3 class="mt-3">No Results Found</h3>
                <p class="text-muted">You haven't taken any exams yet.</p>
                <a href="exam_schedule.php" class="btn btn-primary mt-3">
                    <i class='bx bx-calendar'></i> View Exam Schedule
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
