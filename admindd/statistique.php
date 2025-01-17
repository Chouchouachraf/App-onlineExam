<?php
$pageTitle = "Statistiques";
require_once '../config/config.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Access denied. Admin privileges required.');
    redirect('../auth/login.php');
}

// Initialize variables
$error = '';
$stats = [];

try {
    // Get user statistics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as total_students,
            SUM(CASE WHEN role = 'teacher' THEN 1 ELSE 0 END) as total_teachers,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
            SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked_users,
            SUM(CASE WHEN status = 'deleted' THEN 1 ELSE 0 END) as deleted_users
        FROM users
    ");
    $stmt->execute();
    $stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get monthly user registrations for the past 6 months
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count,
            SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as students,
            SUM(CASE WHEN role = 'teacher' THEN 1 ELSE 0 END) as teachers
        FROM users
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute();
    $stats['monthly'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get user status distribution
    $stmt = $conn->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM users
        GROUP BY status
    ");
    $stmt->execute();
    $stats['status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get role distribution
    $stmt = $conn->prepare("
        SELECT 
            role,
            COUNT(*) as count
        FROM users
        GROUP BY role
    ");
    $stmt->execute();
    $stats['roles'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error = "Une erreur est survenue lors de la récupération des statistiques: " . $e->getMessage();
    error_log("Statistics error: " . $e->getMessage());
}

// Prepare data for charts
$monthlyLabels = [];
$monthlyStudents = [];
$monthlyTeachers = [];
foreach ($stats['monthly'] as $data) {
    $monthlyLabels[] = date('M Y', strtotime($data['month'] . '-01'));
    $monthlyStudents[] = $data['students'];
    $monthlyTeachers[] = $data['teachers'];
}

$statusLabels = [];
$statusData = [];
$statusColors = [
    'active' => '#10B981',
    'blocked' => '#F59E0B',
    'deleted' => '#EF4444'
];
foreach ($stats['status'] as $data) {
    $statusLabels[] = ucfirst($data['status']);
    $statusData[] = $data['count'];
}

$roleLabels = [];
$roleData = [];
$roleColors = [
    'admin' => '#3B82F6',
    'teacher' => '#8B5CF6',
    'student' => '#EC4899'
];
foreach ($stats['roles'] as $data) {
    $roleLabels[] = ucfirst($data['role']);
    $roleData[] = $data['count'];
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
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            padding: 1.5rem;
            border-radius: 15px;
            color: white;
            height: 100%;
        }
        .stat-card.primary {
            background: linear-gradient(45deg, #3B82F6, #60A5FA);
        }
        .stat-card.success {
            background: linear-gradient(45deg, #10B981, #34D399);
        }
        .stat-card.warning {
            background: linear-gradient(45deg, #F59E0B, #FBBF24);
        }
        .stat-card.danger {
            background: linear-gradient(45deg, #EF4444, #F87171);
        }
        .stat-card .icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
        }
        .stat-card .label {
            font-size: 1rem;
            opacity: 0.9;
        }
        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
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
                        <a class="nav-link active text-white" href="statistique.php">
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
                <h1 class="h2">Statistiques</h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card primary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="number"><?= $stats['users']['total_users'] ?></div>
                                <div class="label">Utilisateurs Total</div>
                            </div>
                            <div class="icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card success">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="number"><?= $stats['users']['total_students'] ?></div>
                                <div class="label">Étudiants</div>
                            </div>
                            <div class="icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card warning">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="number"><?= $stats['users']['total_teachers'] ?></div>
                                <div class="label">Enseignants</div>
                            </div>
                            <div class="icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card danger">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="number"><?= $stats['users']['active_users'] ?></div>
                                <div class="label">Utilisateurs Actifs</div>
                            </div>
                            <div class="icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row g-4">
                <!-- Monthly Registration Chart -->
                <div class="col-12 col-lg-8">
                    <div class="card dashboard-card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Inscriptions Mensuelles</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="monthlyChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Status Chart -->
                <div class="col-12 col-lg-4">
                    <div class="card dashboard-card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Statut des Utilisateurs</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Role Distribution Chart -->
                <div class="col-12">
                    <div class="card dashboard-card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Distribution des Rôles</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="roleChart"></canvas>
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
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Monthly Registration Chart
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($monthlyLabels) ?>,
        datasets: [{
            label: 'Étudiants',
            data: <?= json_encode($monthlyStudents) ?>,
            borderColor: '#10B981',
            backgroundColor: '#10B98133',
            tension: 0.4,
            fill: true
        }, {
            label: 'Enseignants',
            data: <?= json_encode($monthlyTeachers) ?>,
            borderColor: '#8B5CF6',
            backgroundColor: '#8B5CF633',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// User Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($statusLabels) ?>,
        datasets: [{
            data: <?= json_encode($statusData) ?>,
            backgroundColor: Object.values(<?= json_encode($statusColors) ?>),
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Role Distribution Chart
const roleCtx = document.getElementById('roleChart').getContext('2d');
new Chart(roleCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($roleLabels) ?>,
        datasets: [{
            label: 'Nombre d\'utilisateurs',
            data: <?= json_encode($roleData) ?>,
            backgroundColor: Object.values(<?= json_encode($roleColors) ?>),
            borderWidth: 0,
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>
</body>
</html>