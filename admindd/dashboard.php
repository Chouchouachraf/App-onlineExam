<?php
$pageTitle = "Admin Dashboard";
require_once '../config/config.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Access denied. Admin privileges required.');
    redirect('../auth/login.php');
}

// Initialize variables
$usersStats = [
    'total_users' => 0,
    'students' => 0,
    'teachers' => 0
];
$recentUsers = [];
$error = '';

try {
    // Get current user data
    $userData = getUserData();

    // Get users statistics
    $statsQuery = "SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as students,
        SUM(CASE WHEN role = 'teacher' THEN 1 ELSE 0 END) as teachers
        FROM users";
    
    $statsStmt = $conn->query($statsQuery);
    $usersStats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    // Get recent users
    $recentQuery = "SELECT id, username, email, role, created_at 
                   FROM users 
                   ORDER BY created_at DESC 
                   LIMIT 5";
    
    $recentStmt = $conn->query($recentQuery);
    $recentUsers = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Admin Dashboard Error: " . $e->getMessage());
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
        .stat-card {
            padding: 20px;
            text-align: center;
            background: linear-gradient(45deg, #4e73df 0%, #224abe 100%);
            color: white;
        }
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
        }
        .stat-label {
            font-size: 1rem;
            opacity: 0.8;
        }
        .action-button {
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .action-button:hover {
            transform: scale(1.05);
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
                        <a class="nav-link active text-white" href="dashboard.php">
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
                        <a class="nav-link text-white" href="statistique.php">
                            <i class="fas fa-chart-bar me-2"></i>
                            Statistiques
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
                <h1 class="h2">Tableau de bord administrateur</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="gerer_compte.php" class="btn btn-sm btn-outline-primary action-button">
                            <i class="fas fa-user-plus"></i> Nouveau compte
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="dashboard-card stat-card">
                        <i class="fas fa-users stat-icon"></i>
                        <div class="stat-number"><?= number_format($usersStats['total_users']) ?></div>
                        <div class="stat-label">Utilisateurs totaux</div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="dashboard-card stat-card" style="background: linear-gradient(45deg, #1cc88a 0%, #169a6f 100%);">
                        <i class="fas fa-user-graduate stat-icon"></i>
                        <div class="stat-number"><?= number_format($usersStats['students']) ?></div>
                        <div class="stat-label">Étudiants</div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="dashboard-card stat-card" style="background: linear-gradient(45deg, #36b9cc 0%, #258391 100%);">
                        <i class="fas fa-chalkboard-teacher stat-icon"></i>
                        <div class="stat-number"><?= number_format($usersStats['teachers']) ?></div>
                        <div class="stat-label">Enseignants</div>
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="row">
                <div class="col-12">
                    <div class="card dashboard-card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Utilisateurs récents</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Utilisateur</th>
                                            <th>Email</th>
                                            <th>Rôle</th>
                                            <th>Date d'inscription</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentUsers as $user): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($user['username']) ?></td>
                                                <td><?= htmlspecialchars($user['email']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $user['role'] === 'student' ? 'success' : ($user['role'] === 'teacher' ? 'info' : 'primary') ?>">
                                                        <?= htmlspecialchars(ucfirst($user['role'])) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                                                <td>
                                                    <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="view_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($recentUsers)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Aucun utilisateur récent</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add any custom JavaScript here
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});
</script>
</body>
</html>