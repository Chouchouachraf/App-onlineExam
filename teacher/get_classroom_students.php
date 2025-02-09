<?php
session_start();
require_once '../config/connection.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'enseignant') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['classroom_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Classroom ID is required']);
    exit();
}

$classroom_id = $_GET['classroom_id'];
$teacher_id = $_SESSION['user_id'];

try {
    // First verify that this classroom belongs to the current teacher
    $stmt = $conn->prepare("
        SELECT id FROM classrooms 
        WHERE id = ? AND teacher_id = ?
    ");
    $stmt->execute([$classroom_id, $teacher_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }

    // Get all students in this classroom
    $stmt = $conn->prepare("
        SELECT 
            u.id,
            u.nom,
            u.prenom,
            u.email,
            cs.joined_at
        FROM classroom_students cs
        JOIN users u ON cs.student_id = u.id
        WHERE cs.classroom_id = ?
        ORDER BY u.nom, u.prenom
    ");
    $stmt->execute([$classroom_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($students);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
