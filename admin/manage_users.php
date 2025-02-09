<?php
session_start();
require_once '../config/connection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['login_error'] = "Please login as an administrator to access this page.";
    header("Location: ../auth/login.php");
    exit();
}

try {
    // Handle user actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            $userId = $_POST['user_id'];
            
            switch ($_POST['action']) {
                case 'approve':
                    $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                    $stmt->execute([$userId]);
                    $_SESSION['success'] = "User approved successfully.";
                    break;

                case 'change_role':
                    $newRole = $_POST['new_role'];
                    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                    $stmt->execute([$newRole, $userId]);
                    $_SESSION['success'] = "User role updated successfully.";
                    break;

                case 'delete':
                    $stmt = $conn->prepare("UPDATE users SET status = 'deleted' WHERE id = ?");
                    $stmt->execute([$userId]);
                    $_SESSION['success'] = "User deleted successfully.";
                    break;

                case 'restore':
                    $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                    $stmt->execute([$userId]);
                    $_SESSION['success'] = "User restored successfully.";
                    break;
            }
        }
    }

    // Get users based on filter
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $query = "SELECT * FROM users WHERE id != ?";
    if ($status !== 'all') {
        $query .= " AND status = ?";
    }
    $query .= " ORDER BY created_at DESC";

    $stmt = $conn->prepare($query);
    if ($status !== 'all') {
        $stmt->execute([$_SESSION['user_id'], $status]);
    } else {
        $stmt->execute([$_SESSION['user_id']]);
    }
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    $users = []; // Initialize empty array to prevent undefined variable error
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - ExamMaster Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        .sidebar {
            height: 100vh;
            background: #4e73df;
            background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            padding-top: 20px;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            background-color: #f8f9fc;
            min-height: 100vh;
        }
        .sidebar-link {
            color: rgba(255,255,255,.8);
            text-decoration: none;
            padding: 15px 20px;
            display: block;
            transition: 0.3s;
            border-left: 3px solid transparent;
        }
        .sidebar-link:hover {
            color: white;
            background: rgba(255,255,255,.1);
            border-left-color: white;
        }
        .sidebar-link i {
            margin-right: 10px;
        }
        .divider {
            border-top: 1px solid rgba(255,255,255,.15);
            margin: 10px 0;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-3px);
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            border-top: none;
            background: #f8f9fc;
            font-weight: 600;
            color: #4e73df;
        }
        .table td {
            vertical-align: middle;
        }
        .badge {
            padding: 8px 12px;
            font-weight: 500;
        }
        .badge-success {
            background-color: #1cc88a;
        }
        .badge-warning {
            background-color: #f6c23e;
            color: #fff;
        }
        .badge-danger {
            background-color: #e74a3b;
        }
        .btn-group-sm > .btn, .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.875rem;
            border-radius: 0.35rem;
        }
        .btn-success {
            background-color: #1cc88a;
            border-color: #1cc88a;
        }
        .btn-success:hover {
            background-color: #17a673;
            border-color: #169b6b;
        }
        .btn-danger {
            background-color: #e74a3b;
            border-color: #e74a3b;
        }
        .btn-danger:hover {
            background-color: #be2617;
            border-color: #be2617;
        }
        .btn-info {
            background-color: #36b9cc;
            border-color: #36b9cc;
            color: #fff;
        }
        .btn-info:hover {
            background-color: #2c9faf;
            border-color: #2a9297;
            color: #fff;
        }
        .form-select {
            padding: 0.375rem 2.25rem 0.375rem 0.75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #6e707e;
            background-color: #fff;
            border: 1px solid #d1d3e2;
            border-radius: 0.35rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .form-select:focus {
            border-color: #bac8f3;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        .alert {
            border: none;
            border-radius: 0.35rem;
        }
        .alert-success {
            color: #0f6848;
            background-color: #d0f3e3;
        }
        .alert-danger {
            color: #78261f;
            background-color: #f8d7da;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #4e73df;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .pagination {
            margin-bottom: 0;
        }
        .page-link {
            color: #4e73df;
            background-color: #fff;
            border: 1px solid #dddfeb;
        }
        .page-link:hover {
            color: #224abe;
            background-color: #eaecf4;
            border-color: #dddfeb;
        }
        .page-item.active .page-link {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .action-buttons .btn {
            margin: 0 2px;
        }
        .action-buttons .btn i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <!-- Include the same sidebar as dashboard.php -->
    <div class="sidebar">
        <!-- Same sidebar content as dashboard.php -->
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4">User Management</h2>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Filter Options -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h5 class="card-title">Filter Users</h5>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" id="statusFilter" onchange="window.location.href='?status='+this.value">
                                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Users</option>
                                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Pending Approval</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active Users</option>
                                <option value="deleted" <?php echo $status === 'deleted' ? 'selected' : ''; ?>>Deleted Users</option>
                            </select>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="dashboard.php" class="btn btn-secondary me-2">
                                <i class='bx bxs-dashboard'></i> Return to Dashboard
                            </a>
                            <a href="add_user.php" class="btn btn-primary">
                                <i class='bx bx-user-plus'></i> Add New User
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Registration Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        switch($user['status']) {
                                            case 'active':
                                                $statusClass = 'bg-success';
                                                break;
                                            case 'inactive':
                                                $statusClass = 'bg-warning';
                                                break;
                                            case 'deleted':
                                                $statusClass = 'bg-danger';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo ucfirst(htmlspecialchars($user['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td class="action-buttons">
                                        <?php if ($user['status'] === 'inactive'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class='bx bx-check'></i> Approve
                                            </button>
                                        </form>
                                        <?php endif; ?>

                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class='bx bx-edit'></i> Edit
                                        </a>

                                        <?php if ($user['status'] !== 'deleted'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class='bx bx-trash'></i> Delete
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="restore">
                                            <button type="submit" class="btn btn-info btn-sm">
                                                <i class='bx bx-refresh'></i> Restore
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
