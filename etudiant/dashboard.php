<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Etudiant') {
    header("Location: ../auth/login.php");
    exit();
}

// Fetch the student’s details and exams
require '../config/connection.php';
$studentId = $_SESSION['user_id'];

// Get the list of classes the student is enrolled in
$queryClasses = $conn->prepare("SELECT cs.class_id
                               FROM class_student cs
                               WHERE cs.student_id = ?");
$queryClasses->execute([4]);
$classes = $queryClasses->fetchAll(PDO::FETCH_COLUMN);

// If the student is enrolled in one or more classes, get exams for these classes
if ($classes) {
    $classIds = implode(",", $classes);
    $queryExams = $conn->prepare("SELECT e.id, e.title, e.exam_date, e.start_time, e.end_time, e.total_points 
                                 FROM exams e
                                 WHERE e.class_id IN ($classIds) AND e.status = 'published'");
    $queryExams->execute();
    $exams = $queryExams->fetchAll(PDO::FETCH_ASSOC);
} else {
    $exams = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Étudiant</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f0f4f8;
        }

        .navbar {
            background-color: #1a365d;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .nav-brand {
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-brand img {
            height: 30px;
        }

        .deconnexion-btn {
            background-color: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
        }

        .container {
            display: flex;
            min-height: calc(100vh - 64px);
        }

        .sidebar {
            width: 250px;
            background-color: white;
            padding: 2rem;
        }

        .profile-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-icon {
            width: 80px;
            height: 80px;
            background-color: #4a5568;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-icon img {
            width: 40px;
            height: 40px;
            filter: invert(1);
        }

        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .nav-item {
            padding: 0.75rem 1rem;
            color: #1a365d;
            text-decoration: none;
            border-radius: 4px;
        }

        .nav-item:hover {
            background-color: #f0f4f8;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        /* Creative Exam Cards */
        .exam-card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 1.5rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .exam-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }

        .exam-card h3 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .exam-card p {
            margin: 0.5rem 0;
            color: #555;
        }

        .exam-info {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            color: #777;
        }

        .exam-info div {
            margin-right: 15px;
        }

        .exam-card .exam-actions {
            margin-top: 1rem;
            text-align: center;
        }

        .exam-button {
            background-color: #1a365d;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .exam-button:hover {
            background-color: #2d4a8c;
        }

        .footer {
            background-color: #1a365d;
            color: white;
            text-align: center;
            padding: 1rem;
            position: fixed;
            bottom: 0;
            width: 100%;
        }

    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">ExamMaster</div>
        <a href="../auth/logout.php" class="deconnexion-btn">Déconnexion</a>
    </nav>

    <div class="container">
        <div class="sidebar">
            <h3>Menu</h3>
        </div>

        <div class="main-content">
            <h1>Bienvenue sur votre tableau de bord, Étudiant!</h1>
            
            <?php if (!empty($exams)): ?>
                <div class="card-grid">
                    <?php foreach ($exams as $exam): ?>
                        <div class="exam-card">
                            <h3><?php echo htmlspecialchars($exam['title']); ?></h3>
                            <p><strong>Date :</strong> <?php echo date('d-m-Y', strtotime($exam['exam_date'])); ?></p>
                            <div class="exam-info">
                                <div><strong>Heure de début :</strong> <?php echo date('H:i', strtotime($exam['start_time'])); ?></div>
                                <div><strong>Heure de fin :</strong> <?php echo date('H:i', strtotime($exam['end_time'])); ?></div>
                            </div>
                            <p><strong>Total des points :</strong> <?php echo $exam['total_points']; ?></p>
                            <div class="exam-actions">
                                <form action="exam/take_exam.php" method="get">
                                    <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                                    <input type="submit" value="Passer l'examen" class="exam-button">
                                </form>
                        </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Aucun examen à venir.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2025 ExamMaster. Tous droits réservés.</p>
    </div>
</body>
</html>
