<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Etudiant') {
    header("Location: ../auth/login.php");
    exit();
}

require '../config/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $examId = $_POST['exam_id'];
    $studentId = $_SESSION['user_id'];
    $answers = $_POST['answers'];

    // Vérifier si l'examen n'est pas expiré
    $stmt = $conn->prepare("
        SELECT * FROM exams 
        WHERE id = ? AND NOW() BETWEEN 
            CONCAT(exam_date, ' ', start_time) AND 
            CONCAT(exam_date, ' ', end_time)
    ");
    $stmt->execute([$examId]);
    if (!$stmt->fetch()) {
        header("Location: exam_list.php?error=expired");
        exit();
    }

    try {
        $conn->beginTransaction();

        // Créer l'entrée de résultat d'examen
        $stmt = $conn->prepare("
            INSERT INTO exam_results (exam_id, student_id, status, start_time, end_time)
            VALUES (?, ?, 'completed', NOW(), NOW())
        ");
        $stmt->execute([$examId, $studentId]);
        $resultId = $conn->lastInsertId();

        // Enregistrer les réponses
        foreach ($answers as $questionId => $answer) {
            if (is_array($answer)) {
                $answerText = implode(',', $answer);
            } else {
                $answerText = $answer;
            }

            $stmt = $conn->prepare("
                INSERT INTO student_answers (exam_result_id, question_id, answer_text)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$resultId, $questionId, $answerText]);
        }

        $conn->commit();
        header("Location: exam_list.php?success=submitted");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        header("Location: exam_list.php?error=submission