<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Etudiant') {
    http_response_code(403);
    exit('Unauthorized');
}

require_once '../../config/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$take_exam_id = $_POST['take_exam_id'];
$student_id = $_SESSION['user_id'];

try {
    // Verify this is the student's exam
    $stmt = $conn->prepare("
        SELECT id FROM take_exam 
        WHERE id = ? AND student_id = ? AND status = 'in_progress'
    ");
    $stmt->execute([$take_exam_id, $student_id]);
    if (!$stmt->fetch()) {
        throw new Exception("Invalid exam session");
    }

    // Delete previous saved answers
    $stmt = $conn->prepare("
        DELETE FROM student_answers 
        WHERE take_exam_id = ?
    ");
    $stmt->execute([$take_exam_id]);

    // Save current answers
    $stmt = $conn->prepare("
        INSERT INTO student_answers 
        (take_exam_id, question_id, selected_option_id, answer_text)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($_POST['answers'] as $questionId => $answer) {
        $stmt->execute([
            $take_exam_id,
            $questionId,
            is_numeric($answer) ? $answer : null,
            !is_numeric($answer) ? $answer : null
        ]);
    }

    http_response_code(200);
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error']);
}