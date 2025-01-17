<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['login_error'] = "Please login as an administrator to access this page.";
    header("Location: ../auth/login.php");
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'exammaster';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
        /* Include the same sidebar styles as dashboard.php */
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
        }
        .sidebar-link {
            color: rgba(255,255,255,.8);
            text-decoration: none;
            padding: 15px 20px;
            display: block;
            transition: 0.3s;
        }
        .sidebar-link:hover {
            color: white;
            background: rgba(255,255,255,.1);
        }
        .user-table th, .user-table td {
            vertical-align: middle;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
        }
        .status-inactive { background-color: #f8d7da; color: #721c24; }
        .status-active { background-color: #d4edda; color: #155724; }
        .status-deleted { background-color: #f8f9fa; color: #6c757d; }
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
                        <div class="col-md-6">
                            <h5 class="card-title">Filter Users</h5>
                        </div>
                        <div class="col-md-6">
                            <select class="form-select" id="statusFilter" onchange="window.location.href='?status='+this.value">
                                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Users</option>
                                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Pending Approval</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active Users</option>
                                <option value="deleted" <?php echo $status === 'deleted' ? 'selected' : ''; ?>>Deleted Users</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover user-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="change_role">
                                            <select class="form-select form-select-sm" name="new_role" onchange="this.form.submit()">
                                                <option value="etudiant" <?php echo $user['role'] === 'etudiant' ? 'selected' : ''; ?>>Student</option>
                                                <option value="enseignant" <?php echo $user['role'] === 'enseignant' ? 'selected' : ''; ?>>Teacher</option>
                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $user['status']; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if ($user['status'] === 'inactive'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class='bx bx-check'></i> Approve
                                            </button>
                                        </form>
                                        <?php endif; ?>

                                        <?php if ($user['status'] !== 'deleted'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?')">
                                                <i class='bx bx-trash'></i>
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
