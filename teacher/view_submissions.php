<?php
session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'enseignant') {
    $_SESSION['login_error'] = "Please login as a teacher to access this page.";
    header("Location: ../auth/login.php");
    exit();
}

// Check if exam ID is provided
if (!isset($_GET['exam_id'])) {
    header("Location: dashboard.php");
    exit();
}

$exam_id = $_GET['exam_id'];
$host = 'localhost';
$dbname = 'schemase';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get exam details
    $stmt = $conn->prepare("
        SELECT e.*, u.nom as teacher_nom, u.prenom as teacher_prenom
        FROM exams e
        JOIN users u ON e.created_by = u.id
        WHERE e.id = ? AND e.created_by = ?
    ");
    $stmt->execute([$exam_id, $_SESSION['user_id']]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        header("Location: dashboard.php");
        exit();
    }

    // Get all student submissions for this exam
    $stmt = $conn->prepare("
        SELECT 
            ea.id as attempt_id,
            ea.started_at,
            ea.submitted_at,
            ea.score,
            u.nom,
            u.prenom,
            e.title as exam_title
        FROM exam_attempts ea
        JOIN users u ON ea.student_id = u.id
        JOIN exams e ON ea.exam_id = e.id
        WHERE ea.exam_id = ?
        ORDER BY ea.submitted_at DESC
    ");
    $stmt->execute([$exam_id]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Submissions - ExamMaster</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .submission-card {
            transition: transform 0.2s;
        }
        .submission-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="text-center mb-4">
            <h4>ExamMaster</h4>
        </div>
        <div class="list-group">
            <a href="dashboard.php" class="list-group-item list-group-item-action">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="create_exam.php" class="list-group-item list-group-item-action">
                <i class="bi bi-plus-circle"></i> Create Exam
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4">Exam Submissions: <?php echo htmlspecialchars($exam['title']); ?></h2>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Exam Details</h5>
                            <p class="card-text">
                                <strong>Created by:</strong> <?php echo htmlspecialchars($exam['teacher_prenom'] . ' ' . $exam['teacher_nom']); ?><br>
                                <strong>Duration:</strong> <?php echo htmlspecialchars($exam['duration']); ?> minutes<br>
                                <strong>Total Submissions:</strong> <?php echo count($submissions); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <?php foreach ($submissions as $submission): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card submission-card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <?php echo htmlspecialchars($submission['prenom'] . ' ' . $submission['nom']); ?>
                                </h5>
                                <div class="card-text">
                                    <p>
                                        <strong>Submitted:</strong> <?php echo date('Y-m-d H:i:s', strtotime($submission['submitted_at'])); ?><br>
                                        <strong>Score:</strong> <?php echo number_format($submission['score'], 2); ?>%<br>
                                    </p>
                                    <div class="mt-3">
                                        <a href="review_answers.php?exam_id=<?php echo $exam_id; ?>&attempt_id=<?php echo $submission['attempt_id']; ?>" 
                                           class="btn btn-primary">
                                            Review Answers
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($submissions)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            No submissions found for this exam yet.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
