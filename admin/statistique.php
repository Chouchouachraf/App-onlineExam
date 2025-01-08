<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Administrateur') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/connection.php';

// Récupérer le nombre total d'utilisateurs actifs
$users_query = "SELECT COUNT(*) as total_users, 
    SUM(CASE WHEN role = 'Etudiant' THEN 1 ELSE 0 END) as total_students,
    SUM(CASE WHEN role = 'Enseignant' THEN 1 ELSE 0 END) as total_teachers
    FROM users";
$stmt = $conn->query($users_query);
$users_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer les examens en cours
$exams_query = "SELECT COUNT(*) as total_exams,
    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as active_exams,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_exams
    FROM exams";
$stmt = $conn->query($exams_query);
$exams_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer les actions récentes (notifications)
$recent_actions_query = "SELECT n.*, u.firstname, u.lastname 
    FROM notifications n 
    JOIN users u ON n.user_id = u.id 
    ORDER BY n.created_at DESC 
    LIMIT 10";
$stmt = $conn->query($recent_actions_query);
$recent_actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les statistiques des résultats d'examens
$results_query = "SELECT 
    COUNT(*) as total_results,
    SUM(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) as passed_count,
    AVG(score) as average_score
    FROM exam_results";
$stmt = $conn->query($results_query);
$results_stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques - ExamMaster</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background-color: #f3f4f6;
            color: #1f2937;
        }

        .navbar {
            background-color: #1e40af;
            color: white;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .logout-btn {
            background-color: #dc2626;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            font-size: 1.25rem;
            color: #4b5563;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #3b82f6;
            margin-bottom: 0.5rem;
        }

        .stat-subtext {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .recent-actions {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .recent-actions h3 {
            font-size: 1.25rem;
            color: #4b5563;
            margin-bottom: 1rem;
        }

        .action-list {
            display: grid;
            gap: 1rem;
        }

        .action-item {
            padding: 1rem;
            border-radius: 0.375rem;
            background: #f9fafb;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .action-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4b5563;
        }

        .action-content {
            flex: 1;
        }

        .action-title {
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .action-meta {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .chart-container {
            margin-top: 2rem;
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .action-item {
                flex-direction: column;
                text-align: center;
            }

            .action-icon {
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <div class="brand">ExamMaster</div>
            <form method="POST" action="../auth/logout.php" style="display: inline;">
                <button type="submit" name="logout" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </button>
            </form>
        </div>
    </nav>

    <div class="container">
        <div class="stats-grid">
            <!-- Statistiques des utilisateurs -->
            <div class="stat-card">
                <h3><i class="fas fa-users"></i> Utilisateurs</h3>
                <div class="stat-value"><?php echo $users_stats['total_users']; ?></div>
                <div class="stat-subtext">
                    <?php echo $users_stats['total_students']; ?> Étudiants<br>
                    <?php echo $users_stats['total_teachers']; ?> Enseignants
                </div>
            </div>

            <!-- Statistiques des examens -->
            <div class="stat-card">
                <h3><i class="fas fa-file-alt"></i> Examens</h3>
                <div class="stat-value"><?php echo $exams_stats['active_exams']; ?></div>
                <div class="stat-subtext">
                    <?php echo $exams_stats['total_exams']; ?> Examens au total<br>
                    <?php echo $exams_stats['completed_exams']; ?> Examens terminés
                </div>
            </div>

            <!-- Statistiques des résultats -->
            <div class="stat-card">
                <h3><i class="fas fa-chart-line"></i> Résultats</h3>
                <div class="stat-value"><?php echo number_format($results_stats['average_score'], 1); ?></div>
                <div class="stat-subtext">
                    Note moyenne<br>
                    <?php echo $results_stats['passed_count']; ?> Examens réussis
                </div>
            </div>
        </div>

        <!-- Actions récentes -->
        <div class="recent-actions">
            <h3><i class="fas fa-history"></i> Actions Récentes</h3>
            <div class="action-list">
                <?php foreach ($recent_actions as $action): ?>
                    <div class="action-item">
                        <div class="action-icon">
                            <?php
                            $icon = 'info-circle';
                            switch ($action['type']) {
                                case 'warning': $icon = 'exclamation-triangle'; break;
                                case 'success': $icon = 'check-circle'; break;
                                case 'error': $icon = 'times-circle'; break;
                            }
                            ?>
                            <i class="fas fa-<?php echo $icon; ?>"></i>
                        </div>
                        <div class="action-content">
                            <div class="action-title"><?php echo htmlspecialchars($action['title']); ?></div>
                            <div class="action-meta">
                                Par <?php echo htmlspecialchars($action['firstname'] . ' ' . $action['lastname']); ?> -
                                <?php echo date('d/m/Y H:i', strtotime($action['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>