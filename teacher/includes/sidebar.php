<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="sidebar">
    <div class="text-center mb-4">
        <h4>ExamMaster</h4>
        <div class="text-muted small">
            <?php echo htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']); ?>
        </div>
    </div>
    <div class="list-group">
        <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class='bx bxs-dashboard'></i> Dashboard
        </a>
        <a href="manage_classrooms.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'manage_classrooms.php' ? 'active' : ''; ?>">
            <i class='bx bxs-school'></i> Manage Classrooms
        </a>
        <a href="create_exam.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'create_exam.php' ? 'active' : ''; ?>">
            <i class='bx bx-plus-circle'></i> Create Exam
        </a>
        <a href="my_exams.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'my_exams.php' ? 'active' : ''; ?>">
            <i class='bx bx-book'></i> My Exams
        </a>
        <a href="pending_submissions.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'pending_submissions.php' ? 'active' : ''; ?>">
            <i class='bx bx-task'></i> Pending Submissions
        </a>
        <a href="../auth/logout.php" class="list-group-item list-group-item-action text-danger">
            <i class='bx bx-log-out'></i> Logout
        </a>
    </div>
</div>
