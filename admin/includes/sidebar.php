<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="sidebar">
    <div class="text-center mb-4">
        <h4>ExamMaster</h4>
        <div class="text-muted small">
            Administrator Panel
        </div>
    </div>
    <div class="list-group">
        <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class='bx bxs-dashboard'></i> Dashboard
        </a>
        <a href="manage_users.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : ''; ?>">
            <i class='bx bxs-user-account'></i> Manage Users
        </a>
        <a href="manage_classrooms.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'manage_classrooms.php' ? 'active' : ''; ?>">
            <i class='bx bxs-school'></i> Manage Classrooms
        </a>
        <a href="manage_exams.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'manage_exams.php' ? 'active' : ''; ?>">
            <i class='bx bx-book'></i> Manage Exams
        </a>
        <a href="system_settings.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'system_settings.php' ? 'active' : ''; ?>">
            <i class='bx bx-cog'></i> System Settings
        </a>
        <a href="../auth/logout.php" class="list-group-item list-group-item-action text-danger">
            <i class='bx bx-log-out'></i> Logout
        </a>
    </div>
</div>
