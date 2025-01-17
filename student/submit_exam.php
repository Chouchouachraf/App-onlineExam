<?php
session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'etudiant') {
    $_SESSION['login_error'] = "Please login as a student to access this page.";
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['exam_id'])) {
    header("Location: dashboard.php");
    exit();
}

$host = 'localhost';
$dbname = 'exammaster';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Start transaction
    $conn->beginTransaction();

    $exam_id = $_POST['exam_id'];
    $student_id = $_SESSION['user_id'];
    $answers = $_POST['answers'];
    $current_time = date('Y-m-d H:i:s');

    // Check if exam is still active
    $stmt = $conn->prepare("
        SELECT e.*, u.nom as teacher_nom, u.prenom as teacher_prenom 
        FROM exams e
        JOIN users u ON e.created_by = u.id
        WHERE e.id = ? 
        AND e.start_date <= ? 
        AND e.end_date >= ?
    ");
    $stmt->execute([$exam_id, $current_time, $current_time]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        throw new Exception("Exam is no longer available.");
    }

    // Check if student has already submitted this exam
    $stmt = $conn->prepare("
        SELECT id FROM exam_attempts 
        WHERE exam_id = ? AND student_id = ? AND submitted_at IS NOT NULL
    ");
    $stmt->execute([$exam_id, $student_id]);
    if ($stmt->fetch()) {
        throw new Exception("You have already submitted this exam.");
    }

    // Get the current attempt
    $stmt = $conn->prepare("
        SELECT * FROM exam_attempts 
        WHERE exam_id = ? AND student_id = ? AND submitted_at IS NULL
        ORDER BY started_at DESC LIMIT 1
    ");
    $stmt->execute([$exam_id, $student_id]);
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$attempt) {
        // Create a new attempt
        $stmt = $conn->prepare("
            INSERT INTO exam_attempts (exam_id, student_id, started_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$exam_id, $student_id]);
        $attempt_id = $conn->lastInsertId();
    } else {
        $attempt_id = $attempt['id'];
    }

    // Get all questions
    $stmt = $conn->prepare("
        SELECT q.*, 
               GROUP_CONCAT(CASE WHEN qo.is_correct = 1 THEN qo.id ELSE NULL END) as correct_option_id
        FROM questions q
        LEFT JOIN question_options qo ON q.id = qo.question_id
        WHERE q.exam_id = ?
        GROUP BY q.id, q.question_text, q.question_type, q.points
    ");
    $stmt->execute([$exam_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_points = 0;
    $earned_points = 0;

    // Process each answer
    foreach ($questions as $question) {
        $total_points += $question['points'];
        $student_answer = isset($answers[$question['id']]) ? $answers[$question['id']] : null;
        $is_correct = false;
        $points_earned = 0;

        switch ($question['question_type']) {
            case 'mcq':
            case 'true_false':
                $is_correct = $student_answer == $question['correct_option_id'];
                $points_earned = $is_correct ? $question['points'] : 0;
                break;
            case 'open':
                // Open questions need teacher review, set as pending
                $is_correct = null;
                $points_earned = null;
                break;
        }

        if ($is_correct === true) {
            $earned_points += $points_earned;
        }

        // Store the student's answer
        $stmt = $conn->prepare("
            INSERT INTO student_answers 
            (attempt_id, question_id, answer_text, is_correct, points_earned, submission_time, needs_review)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $attempt_id,
            $question['id'],
            $student_answer,
            $is_correct,
            $points_earned,
            $current_time,
            $question['question_type'] === 'open' ? 1 : 0
        ]);
    }

    // Calculate initial score (excluding open questions)
    $score = $total_points > 0 ? ($earned_points / $total_points) * 100 : 0;

    // Update attempt with score
    $stmt = $conn->prepare("
        UPDATE exam_attempts 
        SET score = ?,
            points_earned = ?,
            total_points = ?,
            submitted_at = ?,
            needs_review = EXISTS(
                SELECT 1 FROM questions q 
                WHERE q.exam_id = ? AND q.question_type = 'open'
            )
        WHERE id = ?
    ");
    $stmt->execute([$score, $earned_points, $total_points, $current_time, $exam_id, $attempt_id]);

    // Commit transaction
    $conn->commit();

    // Notify teacher about the submission
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, type, message, related_id, created_at)
        VALUES (?, 'exam_submission', ?, ?, ?)
    ");
    $stmt->execute([
        $exam['created_by'],
        "New exam submission from " . $_SESSION['prenom'] . " " . $_SESSION['nom'],
        $attempt_id,
        $current_time
    ]);

    // Redirect to results page
    $_SESSION['success_message'] = "Exam submitted successfully!";
    header("Location: exam_result.php?attempt_id=" . $attempt_id);
    exit();

} catch(Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollBack();
    }
    $_SESSION['error_message'] = "Error submitting exam: " . $e->getMessage();
    header("Location: take_exam.php?id=" . $exam_id);
    exit();
}
