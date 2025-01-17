<?php
$pageTitle = "Restauration des Comptes";
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

// Handle account restoration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_account'])) {
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    
    try {
        // Check if user exists and is deleted
        $stmt = $conn->prepare("SELECT role, status FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['status'] === 'deleted') {
            $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
            $stmt->execute([$userId]);
            $success = "Le compte a été restauré avec succès.";
        } else {
            $error = "Impossible de restaurer ce compte.";
        }
    } catch(PDOException $e) {
        $error = "Une erreur est survenue: " . $e->getMessage();
        error_log("Account restoration error: " . $e->getMessage());
    }
}

// Get all deleted users
try {
    $stmt = $conn->prepare("
        SELECT id, username, email, role, status, created_at 
        FROM users 
        WHERE status = 'deleted' 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Erreur lors de la récupération des utilisateurs: " . $e->getMessage();
    error_log("Error fetching deleted users: " . $e->getMessage());
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
                        <a class="nav-link text-white" href="attribuer_role.php">
                            <i class="fas fa-user-tag me-2"></i>
                            Attribuer des rôles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active text-white" href="restaurer_compte.php">
                            <i class="fas fa-trash-restore me-2"></i>
                            Restaurer les comptes
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
                <h1 class="h2">Restauration des Comptes Supprimés</h1>
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
                    <h5 class="mb-0">Comptes supprimés</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($users)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                            <h5>Aucun compte supprimé</h5>
                            <p class="text-muted">Tous les comptes sont actifs</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Utilisateur</th>
                                        <th>Email</th>
                                        <th>Rôle</th>
                                        <th>Date de suppression</th>
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
                                                    <button type="submit" name="restore_account" class="btn btn-success btn-sm" 
                                                            onclick="return confirm('Êtes-vous sûr de vouloir restaurer ce compte ?')">
                                                        <i class="fas fa-trash-restore"></i> Restaurer
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
        </main>
    </div>
</div>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>