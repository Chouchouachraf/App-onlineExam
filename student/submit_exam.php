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

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['exam_id']) || !isset($_POST['answers'])) {
    header("Location: dashboard.php");
    exit();
}

$exam_id = $_POST['exam_id'];
$student_id = $_SESSION['user_id'];
$answers = $_POST['answers'];

try {
    $conn = new PDO("mysql:host=localhost;dbname=exammaster", 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Start transaction
    $conn->beginTransaction();

    // Check if exam exists and is still open
    $stmt = $conn->prepare("
        SELECT e.*, 
               u.nom as teacher_nom, 
               u.prenom as teacher_prenom,
               s.name as subject_name
        FROM exams e
        JOIN users u ON e.created_by = u.id
        LEFT JOIN subjects s ON e.subject_id = s.id
        WHERE e.id = ? AND NOW() BETWEEN e.start_date AND e.end_date
    ");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        throw new Exception("Exam not found or has expired.");
    }

    // Check if student has already submitted this exam
    $stmt = $conn->prepare("
        SELECT id FROM exam_submissions 
        WHERE exam_id = ? AND student_id = ?
    ");
    $stmt->execute([$exam_id, $student_id]);
    if ($stmt->fetch()) {
        throw new Exception("You have already submitted this exam.");
    }

    // Get correct answers and calculate score
    $stmt = $conn->prepare("
        SELECT * FROM questions 
        WHERE exam_id = ?
    ");
    $stmt->execute([$exam_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_questions = count($questions);
    $correct_answers = 0;

    // Create exam submission
    $stmt = $conn->prepare("
        INSERT INTO exam_submissions (exam_id, student_id, submission_time, total_score) 
        VALUES (?, ?, NOW(), 0)
    ");
    $stmt->execute([$exam_id, $student_id]);
    $submission_id = $conn->lastInsertId();

    // Process each answer
    foreach ($questions as $question) {
        $student_answer = $answers[$question['id']] ?? null;
        $is_correct = 0;

        if ($question['question_type'] === 'multiple_choice') {
            $correct_option = json_decode($question['correct_answer'], true);
            $is_correct = ($student_answer === $correct_option) ? 1 : 0;
            if ($is_correct) {
                $correct_answers++;
            }
        }

        // Store student's answer
        $stmt = $conn->prepare("
            INSERT INTO student_answers 
            (submission_id, question_id, student_answer, is_correct) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $submission_id,
            $question['id'],
            $student_answer,
            $is_correct
        ]);
    }

    // Calculate and update total score
    $score = ($total_questions > 0) ? ($correct_answers / $total_questions) * 100 : 0;
    $stmt = $conn->prepare("
        UPDATE exam_submissions 
        SET total_score = ? 
        WHERE id = ?
    ");
    $stmt->execute([$score, $submission_id]);

    // Commit transaction
    $conn->commit();

    // Set success message
    $_SESSION['success'] = "Exam submitted successfully! Your score: " . number_format($score, 2) . "%";

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    $_SESSION['error'] = $e->getMessage();
}

// Set page title and include header
$page_title = "Exam Submission";
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class='bx bx-error-circle me-2'></i>
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="card text-center fade-in">
                    <div class="card-body">
                        <i class='bx bx-check-circle text-success' style="font-size: 4rem;"></i>
                        <h3 class="card-title mt-3">Exam Submitted Successfully!</h3>
                        <p class="card-text">
                            <?php 
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                            ?>
                        </p>
                        <div class="mt-4">
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class='bx bx-home'></i> Return to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card text-center fade-in">
                    <div class="card-body">
                        <i class='bx bx-error-circle text-danger' style="font-size: 4rem;"></i>
                        <h3 class="card-title mt-3">Submission Error</h3>
                        <p class="card-text">There was an error submitting your exam. Please try again or contact support.</p>
                        <div class="mt-4">
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class='bx bx-home'></i> Return to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
