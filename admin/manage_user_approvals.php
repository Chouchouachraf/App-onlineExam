<?php
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['login_error'] = "Please login as an admin to access this page.";
    header("Location: ../auth/login.php");
    exit();
}

// Handle user approval actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Determine which approval column to use
        $approval_column = columnExists($conn, 'users', 'is_approved') ? 'is_approved' : 'status';

        if (isset($_POST['approve'])) {
            $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            
            if ($approval_column === 'is_approved') {
                $stmt = $conn->prepare("UPDATE users SET is_approved = 1, status = 'active' WHERE id = ?");
            } else {
                $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
            }
            
            $stmt->execute([$user_id]);
            $_SESSION['success_message'] = "User account approved successfully.";
        } 
        elseif (isset($_POST['reject'])) {
            $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            
            // Soft delete or hard delete based on preference
            $stmt = $conn->prepare("UPDATE users SET status = 'deleted' WHERE id = ?");
            $stmt->execute([$user_id]);
            
            $_SESSION['success_message'] = "User account rejected and marked as deleted.";
        }

        header("Location: manage_user_approvals.php");
        exit();
    } catch(PDOException $e) {
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        error_log("User Approval Error: " . $e->getMessage());
    }
}

// Fetch unapproved users
try {
    // Determine which column and condition to use for unapproved users
    $unapproved_query = "";
    if (columnExists($conn, 'users', 'is_approved')) {
        $unapproved_query = "SELECT * FROM users WHERE is_approved = 0";
    } elseif (columnExists($conn, 'users', 'status')) {
        $unapproved_query = "SELECT * FROM users WHERE status = 'inactive'";
    } else {
        throw new PDOException("No approval column found in users table");
    }

    $stmt = $conn->prepare($unapproved_query);
    $stmt->execute();
    $unapproved_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error_message'] = "Error fetching unapproved users: " . $e->getMessage();
    error_log("Unapproved Users Fetch Error: " . $e->getMessage());
    $unapproved_users = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Account Approvals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f4;
        }
        .container-custom {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-top: 30px;
        }
        .table > tbody > tr > td {
            vertical-align: middle;
        }
        .btn-custom {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12 container-custom">
                <div class="header-section">
                    <h2 class="m-0">
                        <i class="bx bxs-user-check me-2"></i>User Account Approvals
                    </h2>
                    <div>
                        <a href="dashboard.php" class="btn btn-secondary btn-custom me-2">
                            <i class="bx bx-arrow-back"></i> Return to Dashboard
                        </a>
                        <span class="badge bg-warning text-dark">
                            <?php echo count($unapproved_users); ?> Pending
                        </span>
                    </div>
                </div>

                <?php if (!empty($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo htmlspecialchars($_SESSION['success_message']); 
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo htmlspecialchars($_SESSION['error_message']); 
                        unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($unapproved_users)): ?>
                    <div class="alert alert-info text-center">
                        No pending user accounts for approval.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Registration Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($unapproved_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo htmlspecialchars($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php 
                                        echo isset($user['created_at']) 
                                            ? htmlspecialchars($user['created_at']) 
                                            : date('Y-m-d H:i:s'); 
                                    ?></td>
                                    <td>
                                        <form method="POST" class="d-flex gap-2">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="approve" class="btn btn-success btn-sm btn-custom">
                                                <i class="bx bx-check"></i> Approve
                                            </button>
                                            <button type="submit" name="reject" class="btn btn-danger btn-sm btn-custom">
                                                <i class="bx bx-x"></i> Reject
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
