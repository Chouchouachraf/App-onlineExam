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
$dbname = 'schemase';
$user = 'root';
$pass = '';

$success_message = '';
$error_message = '';
$userData = null;

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get user ID from URL
    $userId = isset($_GET['id']) ? $_GET['id'] : null;
    
    if (!$userId) {
        header("Location: manage_users.php");
        exit();
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get form data
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $status = $_POST['status'];
        $newPassword = trim($_POST['password']);

        // Validate input
        $errors = [];
        if (empty($nom)) $errors[] = "Last name is required";
        if (empty($prenom)) $errors[] = "First name is required";
        if (empty($email)) $errors[] = "Email is required";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
        if (!in_array($role, ['etudiant', 'enseignant'])) $errors[] = "Invalid role selected";
        if (!in_array($status, ['active', 'inactive', 'deleted'])) $errors[] = "Invalid status selected";

        // Check if email exists for other users
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email already exists";
        }

        if (empty($errors)) {
            try {
                $conn->beginTransaction();

                if (!empty($newPassword)) {
                    // Update with new password
                    if (strlen($newPassword) < 6) {
                        throw new Exception("Password must be at least 6 characters long");
                    }
                    $hashed_password = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET email = ?, password = ?, nom = ?, prenom = ?, role = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$email, $hashed_password, $nom, $prenom, $role, $status, $userId]);
                } else {
                    // Update without changing password
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET email = ?, nom = ?, prenom = ?, role = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$email, $nom, $prenom, $role, $status, $userId]);
                }

                $conn->commit();
                $success_message = "User updated successfully!";
            } catch (Exception $e) {
                $conn->rollBack();
                $error_message = $e->getMessage();
            }
        } else {
            $error_message = implode("<br>", $errors);
        }
    }

    // Get user data
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        header("Location: manage_users.php");
        exit();
    }

} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - ExamMaster Admin</title>
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="text-center mb-4">
            <h4>ExamMaster</h4>
            <small>Administration Panel</small>
        </div>
        <div class="divider"></div>
        <nav>
            <a href="dashboard.php" class="sidebar-link">
                <i class='bx bxs-dashboard'></i> Dashboard
            </a>
            <a href="manage_users.php" class="sidebar-link">
                <i class='bx bxs-user-detail'></i> User Management
            </a>
            <a href="statistics.php" class="sidebar-link">
                <i class='bx bxs-chart'></i> Statistics
            </a>
            <div class="divider"></div>
            <a href="../auth/logout.php" class="sidebar-link">
                <i class='bx bx-log-out'></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">Edit User</h4>
                        </div>
                        <div class="card-body">
                            <?php if ($success_message): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo $success_message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <?php if ($error_message): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo $error_message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="nom" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($userData['nom']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="prenom" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($userData['prenom']); ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                                    <input type="password" class="form-control" id="password" name="password">
                                    <div class="form-text">Only fill this if you want to change the password. Minimum 6 characters.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="etudiant" <?php echo $userData['role'] === 'etudiant' ? 'selected' : ''; ?>>Student</option>
                                        <option value="enseignant" <?php echo $userData['role'] === 'enseignant' ? 'selected' : ''; ?>>Teacher</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" <?php echo $userData['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $userData['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="deleted" <?php echo $userData['status'] === 'deleted' ? 'selected' : ''; ?>>Deleted</option>
                                    </select>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Update User</button>
                                    <div class="btn-group">
                                        <a href="manage_users.php" class="btn btn-secondary">Back to User Management</a>
                                        <a href="dashboard.php" class="btn btn-secondary">
                                            <i class='bx bxs-dashboard'></i> Return to Dashboard
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
