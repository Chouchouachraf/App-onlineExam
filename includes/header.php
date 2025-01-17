<?php
require_once __DIR__ . '/../config/config.php';

// Get user data if logged in
$userData = isLoggedIn() ? getUserData($_SESSION['user_id']) : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'ExamMaster' ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: bold;
            color: white !important;
        }

        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: white !important;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }

        .btn-primary {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        .table {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
        }

        .table th {
            background-color: var(--primary-color);
            color: white;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .sidebar {
            background-color: var(--primary-color);
            min-height: calc(100vh - 56px);
            padding-top: 20px;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 10px 20px;
            margin: 5px 0;
            border-radius: 5px;
        }

        .sidebar .nav-link:hover {
            background-color: var(--secondary-color);
            color: white;
        }

        .sidebar .nav-link.active {
            background-color: var(--accent-color);
            color: white;
        }

        .main-content {
            padding: 20px;
        }

        .dashboard-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
        }

        .dashboard-card .card-body {
            padding: 20px;
        }

        .dashboard-card .icon {
            font-size: 2.5rem;
            color: var(--accent-color);
        }

        .dashboard-card .card-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-top: 15px;
        }

        .dashboard-card .card-text {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/App-onlineExam copie/index.php">
                <i class="fas fa-graduation-cap me-2"></i>ExamMaster
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/App-onlineExam copie/admin/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                </a>
                            </li>
                        <?php elseif (isTeacher()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/App-onlineExam copie/enseignant/dashboard.php">
                                    <i class="fas fa-chalkboard-teacher me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/App-onlineExam copie/enseignant/exam/create_exam.php">
                                    <i class="fas fa-plus-circle me-1"></i>Create Exam
                                </a>
                            </li>
                        <?php elseif (isStudent()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/App-onlineExam copie/etudiant/dashboard.php">
                                    <i class="fas fa-user-graduate me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/App-onlineExam copie/etudiant/exam/exam_list.php">
                                    <i class="fas fa-file-alt me-1"></i>Exams
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn() && $userData): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($userData['firstname']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/App-onlineExam copie/profile.php">
                                    <i class="fas fa-user-cog me-1"></i>Profile
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/App-onlineExam copie/auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/App-onlineExam copie/auth/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php if ($flashMessage = getFlashMessage()): ?>
        <div class="container mt-3">
            <div class="alert alert-<?= $flashMessage['type'] ?> alert-dismissible fade show">
                <?= $flashMessage['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>
