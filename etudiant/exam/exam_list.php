<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Etudiant') {
    header("Location: ../auth/login.php");
    exit();
}

require '../config/connection.php';

// Récupérer l'ID de l'étudiant
$studentId = $_SESSION['user_id'];

// Récupérer les classes de l'étudiant
$stmt = $conn->prepare("
    SELECT class_id 
    FROM class_student 
    WHERE student_id = ?
");
$stmt->execute([$studentId]);
$classes = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Si l'étudiant est inscrit dans des classes, récupérer les examens
if ($classes) {
    $placeholders = str_repeat('?,', count($classes) - 1) . '?';
    $currentDate = date('Y-m-d');
    
    $query = "
        SELECT e.*, s.name as subject_name
        FROM exams e
        JOIN subjects s ON e.subject_id = s.id
        WHERE e.class_id IN ($placeholders)
        AND e.status = 'published'
        AND e.exam_date >= ?
        ORDER BY e.exam_date ASC
    ";
    
    $params = array_merge($classes, [$currentDate]);
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Examens - ExamMaster</title>
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">ExamMaster</div>
        <a href="../auth/logout.php" class="deconnexion-btn">Déconnexion</a>
    </nav>

    <div class="container">
        <h1>Examens disponibles</h1>
        
        <div class="exam-grid">
            <?php if (!empty($exams)): ?>
                <?php foreach ($exams as $exam): ?>
                    <?php
                    $examDateTime = strtotime($exam['exam_date'] . ' ' . $exam['start_time']);
                    $now = time();
                    $isExamActive = $now >= $examDateTime && $now <= strtotime($exam['exam_date'] . ' ' . $exam['end_time']);
                    $isExamFuture = $now < $examDateTime;
                    ?>
                    <div class="exam-card">
                        <h3><?php echo htmlspecialchars($exam['title']); ?></h3>
                        <p class="subject"><?php echo htmlspecialchars($exam['subject_name']); ?></p>
                        <div class="exam-details">
                            <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($exam['exam_date'])); ?></p>
                            <p><strong>Horaire:</strong> <?php echo substr($exam['start_time'], 0, 5); ?> - <?php echo substr($exam['end_time'], 0, 5); ?></p>
                            <p><strong>Durée:</strong> <?php echo $exam['duration']; ?> minutes</p>
                            <p><strong>Points:</strong> <?php echo $exam['total_points']; ?></p>
                        </div>
                        <div class="exam-status">
                            <?php if ($isExamActive): ?>
                                <a href="take_exam.php?exam_id=<?php echo $exam['id']; ?>" class="exam-button active">
                                    Passer l'examen
                                </a>
                            <?php elseif ($isExamFuture): ?>
                                <span class="exam-button disabled">
                                    Pas encore disponible
                                </span>
                            <?php else: ?>
                                <span class="exam-button disabled">
                                    Examen terminé
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-exams">Aucun examen disponible pour le moment.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>