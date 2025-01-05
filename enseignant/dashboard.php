<?php
    session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        header("Location: ../auth/login.php");
        exit();
    }

    if ($_SESSION['user_role'] !== 'Enseignant') {
        header("Location: ../auth/login.php");
        exit();
    }

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamMaster - Tableau de bord enseignant</title>
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

        .action-card {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .action-card:hover {
            transform: translateY(-5px);
        }

        .card-icon {
            width: 60px;
            height: 60px;
            background-color: #4a90e2;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-icon img {
            width: 30px;
            height: 30px;
            filter: invert(1);
        }

        .action-button {
            display: inline-block;
            background-color: #1a365d;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 1rem;
            transition: background-color 0.2s;
        }

        .action-button:hover {
            background-color: #2d4a8c;
        }

        .validate-button {
            background-color: #28a745;
            display: inline-block;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 1rem;
            transition: background-color 0.2s;
        }

        .validate-button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <span>ExamMaster</span>
        </div>
        <a href="../auth/logout.php" class="deconnexion-btn">Déconnexion</a>
    </nav>

    <div class="container">
        <div class="sidebar">
            <div class="profile-section">
                <div class="profile-icon">
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 4a4 4 0 014 4 4 4 0 01-4 4 4 4 0 01-4-4 4 4 0 014-4m0 10c4.42 0 8 1.79 8 4v2H4v-2c0-2.21 3.58-4 8-4z'/%3E%3C/svg%3E" alt="Profile">
                </div>
                <h2>Enseignant</h2>
            </div>
            <div class="nav-menu">
                <a href="?page=profile" class="nav-item">Profile</a>
                <a href="dashboard.php" class="nav-item">Tableau de bord</a>
                <a href="?page=compte" class="nav-item">Compte</a>
            </div>
        </div>

        <div class="main-content">
            <div class="card-grid">
                <!-- Créer Examen -->
                <div class="action-card">
                    <div class="card-icon">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z'/%3E%3C/svg%3E" alt="Créer">
                    </div>
                    <h3>Créer Examen</h3>
                    <p>Créer un nouvel examen pour vos étudiants</p>
                    <a href="exam/create_exam.php" class="action-button">Créer</a>
                </div>

       

                <!-- Consulter résultat -->
                <div class="action-card">
                    <div class="card-icon">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z'/%3E%3C/svg%3E" alt="Consulter">
                    </div>
                    <h3>Consulter résultat</h3>
                    <p>Voir les résultats des examens</p>
                    <a href="exam/view_results.php" class="action-button">Consulter</a>
                </div>

                <!-- Corriger Examen -->
                <div class="action-card">
                    <div class="card-icon">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z'/%3E%3C/svg%3E" alt="Corriger">
                    </div>
                    <h3>Corriger Examen</h3>
                    <p>Corriger les examens soumis</p>
                    <a href="exam/grade_exams.php" class="action-button">Corriger</a>
                </div>

                <!-- Importer Questions -->
                <div class="action-card">
                    <div class="card-icon">
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z'/%3E%3C/svg%3E" alt="Importer">
                    </div>
                    <h3>Importer Questions</h3>
                    <p>Importer des questions depuis un fichier</p>
                    <a href="" class="action-button">Importer</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>