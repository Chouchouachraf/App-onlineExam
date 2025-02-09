<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base path if not defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Include functions
require_once BASE_PATH . '/config/functions.php';

// Get user data before using it
if (isLoggedIn()) {
    $userData = getUserData($_SESSION['user_id']);
}

// Get current page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>ExamMaster</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .welcome-card {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            color: white;
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <?php 
    if (isset($_SESSION['user_role'])) {
        $navbarFile = BASE_PATH . '/includes/' . strtolower($_SESSION['user_role']) . '_navbar.php';
        if (file_exists($navbarFile)) {
            include $navbarFile;
        }
    }
    ?>
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
                <a class="nav-link" href="/App-onlineExam copie/student/dashboard.php">
                    <i class="fas fa-user-graduate me-1"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/App-onlineExam copie/student/exam/exam_list.php">
                    <i class="fas fa-file-alt me-1"></i>Exams
                </a>
            </li>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (isLoggedIn()): ?>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                <i class="fas fa-user-circle me-1"></i><?= isset($_SESSION['firstname']) ? htmlspecialchars($_SESSION['firstname']) : 'User' ?>
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
    <?php if ($flashMessage = getFlashMessage()): ?>
        <div class="container mt-3">
            <div class="alert alert-<?= $flashMessage['type'] ?> alert-dismissible fade show">
                <?= $flashMessage['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>
