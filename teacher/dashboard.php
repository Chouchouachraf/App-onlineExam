<?php
session_start();
require_once '../config/connection.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'enseignant') {
    $_SESSION['login_error'] = "Please login as a teacher to access this page.";
    header("Location: ../auth/login.php");
    exit();
}

// Initialize variables
$teacher_id = $_SESSION['user_id'];
$exams = [];
$error_message = null;
$total_students = 0;
$total_submissions = 0;

try {
    // Get teacher's exams with submission statistics
    $stmt = $conn->prepare("
        SELECT 
            e.*,
            COUNT(DISTINCT es.id) as submission_count,
            COUNT(DISTINCT CASE WHEN es.status = 'submitted' THEN es.id END) as pending_count,
            COUNT(DISTINCT CASE WHEN es.status = 'graded' THEN es.id END) as graded_count,
            COALESCE(e.subject, 'Not specified') as subject,
            COALESCE(e.total_marks, 0) as total_marks
        FROM exams e
        LEFT JOIN exam_submissions es ON e.id = es.exam_id
        WHERE e.created_by = ?
        GROUP BY e.id
        ORDER BY e.created_at DESC
    ");
    $stmt->execute([$teacher_id]);
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total number of students
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'etudiant'");
    $stmt->execute();
    $total_students = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Get total pending submissions
    $total_submissions = array_sum(array_column($exams, 'pending_count'));

} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - ExamMaster</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include_once 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Teacher Dashboard</h2>
                <a href="create_exam.php" class="btn btn-primary">
                    <i class='bx bx-plus'></i> Create New Exam
                </a>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Total Exams</h5>
                            <p class="card-text display-6"><?php echo count($exams); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Total Students</h5>
                            <p class="card-text display-6"><?php echo $total_students; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Pending Submissions</h5>
                            <p class="card-text display-6"><?php echo $total_submissions; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exams Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Your Exams</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($exams)): ?>
                        <div class="text-center py-4">
                            <i class='bx bx-book-content' style="font-size: 4rem; color: #ccc;"></i>
                            <p class="mt-3">No exams created yet. Start by creating your first exam!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Subject</th>
                                        <th>Duration</th>
                                        <th>Total Marks</th>
                                        <th>Submissions</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($exams as $exam): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                            <td><?php echo htmlspecialchars($exam['subject']); ?></td>
                                            <td><?php echo $exam['duration']; ?> mins</td>
                                            <td><?php echo $exam['total_marks']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2"><?php echo $exam['submission_count']; ?> total</span>
                                                    <?php if ($exam['pending_count'] > 0): ?>
                                                        <span class="badge bg-warning"><?php echo $exam['pending_count']; ?> pending</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($exam['submission_count'] == 0): ?>
                                                    <span class="badge bg-secondary">No submissions</span>
                                                <?php elseif ($exam['pending_count'] > 0): ?>
                                                    <span class="badge bg-warning">Needs grading</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">All graded</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="view_submissions.php?exam_id=<?php echo $exam['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class='bx bx-task'></i> View Submissions
                                                    </a>
                                                    <a href="edit_exam.php?id=<?php echo $exam['id']; ?>" 
                                                       class="btn btn-sm btn-outline-secondary">
                                                        <i class='bx bx-edit'></i> Edit
                                                    </a>
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
