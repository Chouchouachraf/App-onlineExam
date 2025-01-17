<?php
$pageTitle = "Attribution des Rôles";
require_once '../config/config.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Access denied. Admin privileges required.');
    redirect('../auth/login.php');
}

// Initialize variables
$users = [];
$error = '';
$success = '';

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['new_role'])) {
    try {
        $userId = (int)$_POST['user_id'];
        $newRole = $_POST['new_role'];
        
        // Validate role
        if (in_array($newRole, ['admin', 'teacher', 'student'])) {
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $userId]);
            $success = "Le rôle a été mis à jour avec succès.";
        } else {
            $error = "Rôle invalide.";
        }
    } catch(PDOException $e) {
        $error = "Une erreur est survenue lors de la mise à jour du rôle: " . $e->getMessage();
        error_log("Role update error: " . $e->getMessage());
    }
}

// Get all users
try {
    $stmt = $conn->prepare("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Erreur lors de la récupération des utilisateurs: " . $e->getMessage();
    error_log("Error fetching users: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-card {
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .user-row {
            transition: all 0.3s ease;
        }
        .user-row:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="dashboard.php">
                            <i class="fas fa-home me-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="gerer_compte.php">
                            <i class="fas fa-users me-2"></i>
                            Gérer les comptes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="approver_etudiant.php">
                            <i class="fas fa-user-check me-2"></i>
                            Approuver les étudiants
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active text-white" href="attribuer_role.php">
                            <i class="fas fa-user-tag me-2"></i>
                            Attribuer des rôles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>
                            Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Attribution des Rôles</h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card dashboard-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Liste des utilisateurs</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Email</th>
                                    <th>Rôle actuel</th>
                                    <th>Date d'inscription</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr class="user-row">
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'teacher' ? 'info' : 'success') ?>">
                                                <?= htmlspecialchars(ucfirst($user['role'])) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <form method="POST" class="d-inline-block">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <select name="new_role" class="form-select form-select-sm d-inline-block w-auto me-2">
                                                    <option value="student" <?= $user['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                                                    <option value="teacher" <?= $user['role'] === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                </select>
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-save"></i> Mettre à jour
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Aucun utilisateur trouvé</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>