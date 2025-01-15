<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Etudiant') {
    header("Location: ../auth/login.php");
    exit();
}

require '../../config/connection.php';

// Vérifier que l'examen est spécifié et que des réponses ont été soumises
if (!isset($_POST['exam_id']) || !isset($_POST['answers'])) {
    die('Aucune réponse soumise.');
}

$examId = $_POST['exam_id'];
$userId = $_SESSION['user_id'];
$answers = $_POST['answers'];

// Enregistrer les réponses de l'étudiant
foreach ($answers as $questionId => $answer) {
    $stmt = $conn->prepare("INSERT INTO student_answers (exam_id, question_id, student_id, answer) VALUES (?, ?, ?, ?)");
    $stmt->execute([$examId, $questionId, $userId, $answer]);
}

// Rediriger l'étudiant vers la page de confirmation ou de résultats
header("Location: exam_results.php?exam_id=$examId");
exit();
?>
