<?php
session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'etudiant') {
    $_SESSION['login_error'] = "Please login as a student to access this page.";
    header("Location: ../auth/login.php");
    exit();
}

// Check if attempt ID is provided
if (!isset($_GET['attempt_id'])) {
    header("Location: dashboard.php");
    exit();
}

$attempt_id = $_GET['attempt_id'];
$host = 'localhost';
$dbname = 'exammaster';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get attempt details with exam info
    $stmt = $conn->prepare("
        SELECT 
            ea.*,
            e.title as exam_title,
            e.description as exam_description,
            u.nom as teacher_nom,
            u.prenom as teacher_prenom
        FROM exam_attempts ea
        JOIN exams e ON ea.exam_id = e.id
        JOIN users u ON e.created_by = u.id
        WHERE ea.id = ? AND ea.student_id = ?
    ");
    $stmt->execute([$attempt_id, $_SESSION['user_id']]);
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$attempt) {
        header("Location: dashboard.php");
        exit();
    }

    // Get all questions and answers
    $stmt = $conn->prepare("
        SELECT 
            q.question_text,
            q.question_type,
            q.points as max_points,
            sa.answer_text,
            sa.points_earned,
            sa.is_correct,
            sa.feedback,
            GROUP_CONCAT(qo.option_text) as all_options,
            GROUP_CONCAT(qo.is_correct) as correct_options
        FROM questions q
        JOIN student_answers sa ON q.id = sa.question_id
        LEFT JOIN question_options qo ON q.id = qo.question_id
        WHERE sa.attempt_id = ?
        GROUP BY q.id
        ORDER BY q.id
    ");
    $stmt->execute([$attempt_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results - ExamMaster</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        .sidebar {
            height: 100vh;
            background: #2c3e50;
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
        .sidebar-link {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            transition: 0.3s;
        }
        .sidebar-link:hover {
            background: #34495e;
            color: #ecf0f1;
        }
        .sidebar-link i {
            margin-right: 10px;
        }
        .result-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            font-size: 2em;
            font-weight: bold;
            color: #fff;
        }
        .answer-correct {
            color: #28a745;
        }
        .answer-incorrect {
            color: #dc3545;
        }
        .answer-pending {
            color: #ffc107;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="text-center mb-4">
            <h4>ExamMaster</h4>
        </div>
        <nav>
            <a href="dashboard.php" class="sidebar-link">
                <i class='bx bxs-dashboard'></i> Dashboard
            </a>
            <a href="my_exams.php" class="sidebar-link">
                <i class='bx bx-book'></i> My Exams
            </a>
            <a href="../auth/logout.php" class="sidebar-link">
                <i class='bx bx-log-out'></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h2 class="mb-4">Exam Results: <?php echo htmlspecialchars($attempt['exam_title']); ?></h2>

            <!-- Overall Score -->
            <div class="result-card text-center">
                <div class="score-circle mb-3" style="background: <?php 
                    $score = $attempt['score'];
                    if ($score >= 80) echo '#28a745';
                    else if ($score >= 60) echo '#ffc107';
                    else echo '#dc3545';
                ?>;">
                    <?php echo number_format($score, 1); ?>%
                </div>
                <h4>Final Score</h4>
                <p class="text-muted">
                    Points: <?php echo $attempt['points_earned']; ?> / <?php echo $attempt['total_points']; ?><br>
                    Completed on: <?php echo date('F j, Y, g:i a', strtotime($attempt['end_time'])); ?>
                </p>
            </div>

            <!-- Questions and Answers -->
            <h3 class="mb-3">Question Review</h3>
            <?php foreach ($questions as $index => $question): ?>
                <div class="result-card">
                    <div class="row">
                        <div class="col-md-9">
                            <h5>Question <?php echo $index + 1; ?></h5>
                            <p><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
                            
                            <div class="mt-3">
                                <h6>Your Answer:</h6>
                                <?php if ($question['question_type'] === 'open'): ?>
                                    <p><?php echo nl2br(htmlspecialchars($question['answer_text'])); ?></p>
                                    <?php if ($question['feedback']): ?>
                                        <div class="mt-2">
                                            <strong>Teacher's Feedback:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($question['feedback'])); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="<?php echo $question['is_correct'] ? 'answer-correct' : 'answer-incorrect'; ?>">
                                        <?php echo htmlspecialchars($question['answer_text']); ?>
                                        <i class='bx <?php echo $question['is_correct'] ? 'bx-check' : 'bx-x'; ?>'></i>
                                    </p>
                                    <?php if (!$question['is_correct']): ?>
                                        <p class="text-muted">
                                            <strong>Correct Answer:</strong>
                                            <?php 
                                            $options = explode(',', $question['all_options']);
                                            $correct = explode(',', $question['correct_options']);
                                            foreach ($options as $i => $option) {
                                                if ($correct[$i] == '1') {
                                                    echo htmlspecialchars($option);
                                                    break;
                                                }
                                            }
                                            ?>
                                        </p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-3 text-end">
                            <div class="points-badge">
                                <?php if ($question['question_type'] === 'open' && !isset($question['points_earned'])): ?>
                                    <span class="badge bg-warning">Pending Review</span>
                                <?php else: ?>
                                    <span class="badge <?php echo $question['points_earned'] == $question['max_points'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $question['points_earned']; ?> / <?php echo $question['max_points']; ?> points
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="text-center mb-5">
                <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
