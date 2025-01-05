<?php
session_start();

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    switch ($_SESSION['user_role']) {
        case 'Administrateur':
            header("Location: admin/dashboard.php");
            exit();
        case 'Enseignant':
            header("Location: enseignant/dashboard.php");
            exit();
        case 'Etudiant':
            header("Location: etudiant/dashboard.php");
            exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamMaster - Accueil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <div class="brand">ExamMaster</div>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-card">
            <h1>Bienvenue sur ExamMaster</h1>
            <p>Choisissez votre rôle pour vous connecter et accéder à votre espace dédié.</p>
        </div>

        <div class="roles">
            <a href="auth/login.php" class="role-card">
                <div class="role-icon">
                    <i class="fas fa-user-cog"></i>
                </div>
                <h2 class="role-title">Administrateur</h2>
                <p class="role-description">Gérez les utilisateurs et les paramètres du système</p>
            </a>
            <a href="auth/login.php" class="role-card">
                <div class="role-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <h2 class="role-title">Enseignant</h2>
                <p class="role-description">Créez et gérez des examens pour vos étudiants</p>
            </a>
            <a href="auth/login.php" class="role-card">
                <div class="role-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h2 class="role-title">Étudiant</h2>
                <p class="role-description">Passez des examens et consultez vos résultats</p>
            </a>
        </div>
    </div>
</body>
</html>