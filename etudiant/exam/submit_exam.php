<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Etudiant') {
    header("Location: ../../auth/login.php");
    exit();
}

require_once '../../config/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit();
}

$take_exam_id = $_POST['take_exam_id'];
$exam_id = $_POST['exam_id'];
$student_id = $_SESSION['user_id'];

try {
    $conn->beginTransaction();

    // Verify this is the student's exam
    $stmt = $conn->prepare("
        SELECT id FROM take_exam 
        WHERE id = ? AND student_id = ? AND exam_id = ? AND status = 'in_progress'
    ");
    $stmt->execute([$take_exam_id, $student_id, $exam_id]);
    if (!$stmt->fetch()) {
        throw new Exception("Invalid exam submission");
    }

    // Get all questions and their correct answers
    $stmt = $conn->prepare("
        SELECT q.id, q.question_type, q.points, 
               GROUP_CONCAT(CASE WHEN qo.is_correct = 1 THEN qo.id END) as correct_options
        FROM questions q
        LEFT JOIN question_options qo ON q.id = qo.question_id
        WHERE q.exam_id = ?
        GROUP BY q.id
    ");
    $stmt->execute([$exam_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalScore = 0;
    $maxScore = 0;

    // Process each answer
    foreach ($questions as $question) {
        $questionId = $question['id'];
        $maxScore += $question['points'];
        $selectedAnswer = $_POST['answers'][$questionId] ?? null;
        
        if ($question['question_type'] === 'qcm') {
            $correctOptions = explode(',', $question['correct_options']);
            $isCorrect = in_array($selectedAnswer, $correctOptions);
            $pointsEarned = $isCorrect ? $question['points'] : 0;
        } else {
            // For text answers, store them for manual grading
            $isCorrect = null;
            $pointsEarned = null;
        }

        // Store the answer
        $stmt = $conn->prepare("
            INSERT INTO student_answers 
            (take_exam_id, question_id, selected_option_id, answer_text, is_correct, points_earned)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $take_exam_id,
            $questionId,
            $selectedAnswer,
            $question['question_type'] === 'text' ? $selectedAnswer : null,
            $isCorrect,
            $pointsEarned
        ]);

        if ($pointsEarned !== null) {
            $totalScore += $pointsEarned;
        }
    }

    // Update the exam attempt
    $stmt = $conn->prepare("
        UPDATE take_exam 
        SET status = 'completed',
            end_time = NOW(),
            score = ?
        WHERE id = ?
    ");
    $stmt->execute([$totalScore, $take_exam_id]);

    $conn->commit();
    header("Location: dashboard.php?success=exam_submitted");
    exit();

} catch (Exception $e) {
    $conn->rollBack();
    error_log($e->getMessage());
    header("Location: dashboard.php?error=submission_failed");
    exit();
}