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

// Check if exam ID is provided
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$exam_id = $_GET['id'];

try {
    // Get exam details
    $stmt = $conn->prepare("
        SELECT e.*, 
               u.nom as teacher_nom, 
               u.prenom as teacher_prenom,
               s.name as subject_name
        FROM exams e
        JOIN users u ON e.created_by = u.id
        LEFT JOIN subjects s ON e.subject_id = s.id
        WHERE e.id = ?
    ");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        header("Location: dashboard.php");
        exit();
    }

    // Check if the exam is available
    $now = new DateTime();
    $start_date = new DateTime($exam['start_date']);
    $end_date = new DateTime($exam['end_date']);

    if ($now < $start_date) {
        $_SESSION['error'] = "This exam has not started yet.";
        header("Location: dashboard.php");
        exit();
    }

    if ($now > $end_date) {
        $_SESSION['error'] = "This exam has expired.";
        header("Location: dashboard.php");
        exit();
    }

    // Check if student has already submitted this exam
    $stmt = $conn->prepare("
        SELECT id FROM exam_submissions 
        WHERE exam_id = ? AND student_id = ?
    ");
    $stmt->execute([$exam_id, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "You have already submitted this exam.";
        header("Location: dashboard.php");
        exit();
    }

    // Get exam questions
    $stmt = $conn->prepare("
        SELECT * FROM questions 
        WHERE exam_id = ?
        ORDER BY question_order
    ");
    $stmt->execute([$exam_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: dashboard.php");
    exit();
}

// Set page title
$page_title = $exam['title'];
?>

<?php include '../includes/header.php'; ?>

<div class="container py-4">
    <!-- Exam Header -->
    <div class="card welcome-card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1"><?php echo htmlspecialchars($exam['title']); ?></h2>
                    <p class="mb-0">
                        <i class='bx bx-book'></i> <?php echo htmlspecialchars($exam['subject_name']); ?> |
                        <i class='bx bx-user'></i> <?php echo htmlspecialchars($exam['teacher_prenom'] . ' ' . $exam['teacher_nom']); ?>
                    </p>
                </div>
                <div class="text-end">
                    <h4 class="mb-1">Time Remaining</h4>
                    <div id="timer" class="h3 mb-0">Loading...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Exam Form -->
    <form id="examForm" method="POST" action="submit_exam.php">
        <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
        
        <?php foreach ($questions as $index => $question): ?>
            <div class="card mb-4 fade-in">
                <div class="card-body">
                    <h5 class="card-title">Question <?php echo $index + 1; ?></h5>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
                    
                    <?php if ($question['question_type'] === 'multiple_choice'): ?>
                        <?php 
                        $options = json_decode($question['options'], true);
                        foreach ($options as $option): 
                        ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" 
                                       name="answers[<?php echo $question['id']; ?>]" 
                                       value="<?php echo htmlspecialchars($option); ?>"
                                       required>
                                <label class="form-check-label">
                                    <?php echo htmlspecialchars($option); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <textarea class="form-control" 
                                name="answers[<?php echo $question['id']; ?>]" 
                                rows="4" 
                                required></textarea>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-secondary" onclick="history.back()">
                        <i class='bx bx-arrow-back'></i> Back
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-check-circle'></i> Submit Exam
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php 
// Add custom JavaScript for timer
$extra_js = "
    // Calculate end time
    const endTime = new Date('" . $exam['end_date'] . "').getTime();
    
    // Update timer every second
    const timerInterval = setInterval(function() {
        const now = new Date().getTime();
        const distance = endTime - now;
        
        // Calculate hours, minutes and seconds
        const hours = Math.floor(distance / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        // Display the timer
        document.getElementById('timer').innerHTML = 
            hours.toString().padStart(2, '0') + ':' +
            minutes.toString().padStart(2, '0') + ':' +
            seconds.toString().padStart(2, '0');
            
        // If the countdown is over
        if (distance < 0) {
            clearInterval(timerInterval);
            document.getElementById('timer').innerHTML = 'EXPIRED';
            document.getElementById('examForm').submit();
        }
    }, 1000);

    // Confirm before leaving page
    window.onbeforeunload = function() {
        return 'Are you sure you want to leave? Your exam progress will be lost.';
    };

    // Remove confirmation when submitting form
    document.getElementById('examForm').onsubmit = function() {
        window.onbeforeunload = null;
    };
";

include '../includes/footer.php'; 
?>
