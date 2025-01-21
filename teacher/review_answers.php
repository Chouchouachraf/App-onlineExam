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

    // Handle grade submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $conn->beginTransaction();

        try {
            foreach ($_POST['grades'] as $answer_id => $data) {
                $points = $data['points'];
                $feedback = $data['feedback'];
                $stmt = $conn->prepare("
                    UPDATE student_answers 
                    SET points_earned = ?,
                        feedback = ?,
                        is_graded = 1
                    WHERE id = ?
                ");
                $stmt->execute([$points, $feedback, $answer_id]);
            }

            // Recalculate total score for each affected attempt
            $stmt = $conn->prepare("
                UPDATE exam_attempts ea
                SET points_earned = (
                    SELECT SUM(points_earned)
                    FROM student_answers
                    WHERE attempt_id = ea.id
                ),
                score = (
                    SELECT (SUM(points_earned) / SUM(q.points)) * 100
                    FROM student_answers sa
                    JOIN questions q ON sa.question_id = q.id
                    WHERE sa.attempt_id = ea.id
                )
                WHERE exam_id = ?
            ");
            $stmt->execute([$exam_id]);

            $conn->commit();
            $success_message = "Grades saved successfully!";
        } catch (Exception $e) {
            $conn->rollBack();
            $error_message = "Error saving grades: " . $e->getMessage();
        }
    }

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

    // Get all open questions with student answers
    $stmt = $conn->prepare("
        SELECT 
            q.id as question_id,
            q.question_text,
            q.points as max_points,
            q.question_type,
            sa.id as answer_id,
            sa.answer_text,
            sa.points_earned,
            sa.feedback,
            sa.is_graded,
            u.nom as student_nom,
            u.prenom as student_prenom,
            ea.id as attempt_id,
            ea.start_time,
            ea.submit_time,
            ea.score as total_score
        FROM questions q
        JOIN student_answers sa ON q.id = sa.question_id
        JOIN exam_attempts ea ON sa.attempt_id = ea.id
        JOIN users u ON ea.student_id = u.id
        WHERE q.exam_id = ? 
        " . (isset($_GET['attempt_id']) ? "AND ea.id = ?" : "") . "
        ORDER BY ea.submit_time DESC, q.id ASC
    ");

    if (isset($_GET['attempt_id'])) {
        $stmt->execute([$exam_id, $_GET['attempt_id']]);
    } else {
        $stmt->execute([$exam_id]);
    }
    $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Student Answers - ExamMaster</title>
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
        .answer-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .answer-card.graded {
            border-left: 4px solid #28a745;
        }
        .answer-card.ungraded {
            border-left: 4px solid #dc3545;
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
        <div class="container-fluid">
            <h2 class="mb-4">Review Student Answers</h2>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Exam Details</h5>
                    <p class="card-text">
                        <strong>Title:</strong> <?php echo htmlspecialchars($exam['title']); ?><br>
                        <strong>Created by:</strong> <?php echo htmlspecialchars($exam['teacher_prenom'] . ' ' . $exam['teacher_nom']); ?><br>
                        <?php if (!empty($answers)): ?>
                            <strong>Student:</strong> <?php echo htmlspecialchars($answers[0]['student_prenom'] . ' ' . $answers[0]['student_nom']); ?><br>
                            <strong>Submission Time:</strong> <?php echo date('Y-m-d H:i:s', strtotime($answers[0]['submit_time'])); ?><br>
                            <strong>Total Score:</strong> <?php echo number_format($answers[0]['total_score'], 2); ?>%
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <?php if (empty($answers)): ?>
                <div class="alert alert-info">No answers found for this exam.</div>
            <?php else: ?>
                <form method="POST">
                    <?php foreach ($answers as $answer): ?>
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Question</h5>
                                <p class="card-text"><?php echo htmlspecialchars($answer['question_text']); ?></p>
                                
                                <h5 class="card-title mt-4">Student's Answer</h5>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($answer['answer_text'])); ?></p>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Points (max: <?php echo $answer['max_points']; ?>)</label>
                                            <input type="number" 
                                                   name="grades[<?php echo $answer['answer_id']; ?>][points]" 
                                                   class="form-control" 
                                                   min="0" 
                                                   max="<?php echo $answer['max_points']; ?>" 
                                                   step="0.5"
                                                   value="<?php echo $answer['points_earned']; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Feedback</label>
                                            <textarea name="grades[<?php echo $answer['answer_id']; ?>][feedback]" 
                                                      class="form-control" 
                                                      rows="3"><?php echo htmlspecialchars($answer['feedback']); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="text-center mb-4">
                        <button type="submit" class="btn btn-primary">Save Grades</button>
                        <a href="view_submissions.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-secondary ms-2">Back to Submissions</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
