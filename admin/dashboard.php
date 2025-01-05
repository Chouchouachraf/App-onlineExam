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
            <h2 class="admin-name">Bienvenue</h2>
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
                <div class="stat-number">
                    <i class="fas fa-users"></i> 150
                </div>
                <div class="stat-label">Utilisateurs Actifs</div>
            </div>

            <div class="stat-card">
                <div class="stat-number">
                    <i class="fas fa-book"></i> 25
                </div>
                <div class="stat-label">Examens en Cours</div>
            </div>

            <div class="stat-card">
                <div class="stat-number">
                    <i class="fas fa-clock"></i> 10
                </div>
                <div class="stat-label">Actions Récentes</div>
            </div>
        </div>
    </div>
</body>
</html>