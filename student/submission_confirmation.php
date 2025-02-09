<?php
session_start();
require_once '../config/connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$submission_id = isset($_GET['submission_id']) ? $_GET['submission_id'] : null;

try {
    // Get submission details
    $stmt = $conn->prepare("
        SELECT 
            es.*,
            e.title as exam_title,
            e.total_marks,
            COUNT(sa.id) as answered_questions,
            (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) as total_questions
        FROM exam_submissions es
        JOIN exams e ON es.exam_id = e.id
        LEFT JOIN student_answers sa ON es.id = sa.submission_id
        WHERE es.id = ? AND es.student_id = ?
        GROUP BY es.id, e.id, e.title, e.total_marks
    ");
    $stmt->execute([$submission_id, $_SESSION['user_id']]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$submission) {
        $_SESSION['error'] = "Submission not found.";
        header("Location: dashboard.php");
        exit();
    }

} catch(PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Submitted - ExamMaster</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card text-center">
                        <div class="card-body py-5">
                            <div class="mb-4">
                                <i class='bx bx-check-circle' style="font-size: 5rem; color: #28a745;"></i>
                            </div>
                            <h2 class="card-title mb-4">Exam Submitted Successfully!</h2>
                            <p class="card-text mb-4">
                                Your answers for <strong><?php echo htmlspecialchars($submission['exam_title']); ?></strong> 
                                have been submitted successfully.
                            </p>
                            
                            <div class="row justify-content-center mb-4">
                                <div class="col-md-8">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-6 border-end">
                                                    <h5>Questions Answered</h5>
                                                    <p class="mb-0">
                                                        <?php echo $submission['answered_questions']; ?> / <?php echo $submission['total_questions']; ?>
                                                    </p>
                                                </div>
                                                <div class="col-6">
                                                    <h5>Submission Time</h5>
                                                    <p class="mb-0">
                                                        <?php echo date('M d, Y H:i', strtotime($submission['submission_time'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if (isset($submission['total_score']) && $submission['status'] === 'graded'): ?>
                                <div class="alert alert-info">
                                    <h5>Your Score</h5>
                                    <p class="mb-0">
                                        <?php echo number_format($submission['total_score'], 1); ?>%
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    Your exam will be graded by your teacher soon.
                                </div>
                            <?php endif; ?>

                            <div class="mt-4">
                                <a href="dashboard.php" class="btn btn-primary">
                                    <i class='bx bx-home'></i> Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
