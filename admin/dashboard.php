<?php
    session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        header("Location: ../auth/login.php");
        exit();
    }

    if ($_SESSION['user_role'] !== 'Administrateur') {
        header("Location: ../auth/login.php");
        exit();
    }

    require_once '../config/connection.php';

    // Récupérer les informations de l'utilisateur connecté
    $user_query = "SELECT firstname, lastname FROM users WHERE id = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Récupérer le nombre d'utilisateurs actifs
    $users_query = "SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'Etudiant' THEN 1 ELSE 0 END) as students,
        SUM(CASE WHEN role = 'Enseignant' THEN 1 ELSE 0 END) as teachers
        FROM users";
    $stmt = $conn->query($users_query);
    $users_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Récupérer les examens en cours
    $exams_query = "SELECT COUNT(*) as active_exams 
        FROM exams 
        WHERE status = 'published' 
        AND DATE(exam_date) >= CURRENT_DATE()";
    $stmt = $conn->query($exams_query);
    $exams_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Récupérer le nombre d'actions récentes (dernières 24h)
    $actions_query = "SELECT COUNT(*) as recent_actions 
        FROM notifications 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $stmt = $conn->query($actions_query);
    $actions_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Récupérer les 5 dernières actions pour l'infobulle
    $recent_actions_query = "SELECT n.title, n.created_at, u.firstname, u.lastname 
        FROM notifications n
        JOIN users u ON n.user_id = u.id
        ORDER BY n.created_at DESC
        LIMIT 5";
    $stmt = $conn->query($recent_actions_query);
    $recent_actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les détails des examens pour l'infobulle
    $detailed_exams_query = "SELECT e.title, c.name as class_name, e.exam_date 
        FROM exams e
        JOIN classes c ON e.class_id = c.id
        WHERE e.status = 'published' 
        AND DATE(e.exam_date) >= CURRENT_DATE()
        LIMIT 5";
    $stmt = $conn->query($detailed_exams_query);
    $detailed_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamMaster - Administration</title>
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

        .logout-btn:hover {
            background-color: #b91c1c;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .welcome-card {
            background-color: white;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .admin-icon {
            width: 80px;
            height: 80px;
            background-color: #3b82f6;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
        }

        .admin-name {
            font-size: 1.5rem;
            color: #1f2937;
            margin-top: 1rem;
        }

        .tabs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .admin-card {
            background-color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.2s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-decoration: none;
            color: #1f2937;
        }

        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .card-icon {
            width: 50px;
            height: 50px;
            background-color: #3b82f6;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .card-description {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .stat-card {
            background-color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: relative;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .stat-tooltip {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background-color: #1f2937;
            color: white;
            padding: 1rem;
            border-radius: 0.375rem;
            width: 300px;
            display: none;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .stat-card:hover .stat-tooltip {
            display: block;
        }

        .tooltip-item {
            text-align: left;
            padding: 0.5rem 0;
            border-bottom: 1px solid #374151;
        }

        .tooltip-item:last-child {
            border-bottom: none;
        }

        .tooltip-title {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .tooltip-meta {
            font-size: 0.875rem;
            color: #9ca3af;
        }

        .stat-breakdown {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #3b82f6;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #3b82f6;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6b7280;
        }

        @media (max-width: 768px) {
            .tabs {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
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
        <!-- Carte de bienvenue -->
        <div class="welcome-card">
            <div class="admin-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h2 class="admin-name">Bienvenue <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h2>
            <p>Administrateur</p>
        </div>

        <!-- Cartes d'actions -->
        <div class="tabs">
            <a href="attribuer_role.php" class="admin-card">
                <div class="card-icon">
                    <i class="fas fa-user-cog"></i>
                </div>
                <h3 class="card-title">Attribuer Role</h3>
                <p class="card-description">Gérer les rôles des utilisateurs</p>
            </a>

            <a href="statistique.php" class="admin-card">
                <div class="card-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h3 class="card-title">Statistiques</h3>
                <p class="card-description">Voir les statistiques du système</p>
            </a>

            <a href="gerer_compte.php" class="admin-card">
                <div class="card-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <h3 class="card-title">Gérer Comptes</h3>
                <p class="card-description">Administration des comptes</p>
            </a>

            <a href="restaurer_compte.php" class="admin-card">
                <div class="card-icon">
                    <i class="fas fa-undo"></i>
                </div>
                <h3 class="card-title">Restaurer Compte</h3>
                <p class="card-description">Récupérer les comptes supprimés</p>
            </a>

            <a href="supprimer_compte.php" class="admin-card">
                <div class="card-icon">
                    <i class="fas fa-user-minus"></i>
                </div>
                <h3 class="card-title">Supprimer Compte</h3>
                <p class="card-description">Supprimer des comptes utilisateurs</p>
            </a>
        </div>

        <!-- Statistiques rapides -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number">
                    <?php echo $users_stats['total_users']; ?>
                </div>
                <div class="stat-label">Utilisateurs Actifs</div>
                <div class="stat-breakdown">
                    <?php echo $users_stats['students']; ?> Étudiants |
                    <?php echo $users_stats['teachers']; ?> Enseignants
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-number">
                    <?php echo $exams_stats['active_exams']; ?>
                </div>
                <div class="stat-label">Examens en Cours</div>
                <div class="stat-tooltip">
                    <h4 style="margin-bottom: 0.5rem;">Détails des examens actifs</h4>
                    <?php foreach($detailed_exams as $exam): ?>
                        <div class="tooltip-item">
                            <div class="tooltip-title"><?php echo htmlspecialchars($exam['title']); ?></div>
                            <div class="tooltip-meta">
                                Classe: <?php echo htmlspecialchars($exam['class_name']); ?><br>
                                Date: <?php echo date('d/m/Y', strtotime($exam['exam_date'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number">
                    <?php echo $actions_stats['recent_actions']; ?>
                </div>
                <div class="stat-label">Actions Récentes</div>
                <div class="stat-tooltip">
                    <h4 style="margin-bottom: 0.5rem;">Dernières actions</h4>
                    <?php foreach($recent_actions as $action): ?>
                        <div class="tooltip-item">
                            <div class="tooltip-title"><?php echo htmlspecialchars($action['title']); ?></div>
                            <div class="tooltip-meta">
                            <div class="tooltip-title"><?php echo htmlspecialchars($action['title']); ?></div>
                            <div class="tooltip-meta">
                                Par: <?php echo htmlspecialchars($action['firstname'] . ' ' . $action['lastname']); ?><br>
                                Le: <?php echo date('d/m/Y H:i', strtotime($action['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Rafraîchissement automatique des statistiques toutes les 5 minutes
        setInterval(function() {
            location.reload();
        }, 5 * 60 * 1000);
    </script>
</body>
</html>