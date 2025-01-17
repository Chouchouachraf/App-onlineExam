<?php
$pageTitle = "Approbation des étudiants";
require_once '../config/config.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Access denied. Admin privileges required.');
    redirect('../auth/login.php');
}

// Initialize variables
$students = [];
$error = '';
$success = '';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'], $_POST['student_id'])) {
            $action = $_POST['action'];
            $studentId = (int)$_POST['student_id'];
            
            if ($action === 'approve') {
                $stmt = $conn->prepare("UPDATE users SET approved = 1 WHERE id = ? AND role = 'student'");
                $stmt->execute([$studentId]);
                $success = "L'étudiant a été approuvé avec succès.";
            } elseif ($action === 'reject') {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
                $stmt->execute([$studentId]);
                $success = "L'étudiant a été rejeté avec succès.";
            }
        }
    } catch(PDOException $e) {
        $error = "Une erreur est survenue: " . $e->getMessage();
        error_log("Student approval error: " . $e->getMessage());
    }
}

// Get pending students
try {
    $stmt = $conn->prepare("SELECT id, username, email, created_at FROM users WHERE role = 'student' AND (approved = 0 OR approved IS NULL)");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Erreur lors de la récupération des étudiants: " . $e->getMessage();
    error_log("Error fetching pending students: " . $e->getMessage());
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
        .student-card {
            transition: all 0.3s ease;
        }
        .student-card:hover {
            background-color: #f8f9fa;
        }
        .approval-buttons .btn {
            transition: all 0.3s ease;
        }
        .approval-buttons .btn:hover {
            transform: translateY(-2px);
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
                        <a class="nav-link active text-white" href="approver_etudiant.php">
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
                <h1 class="h2">Approbation des étudiants</h1>
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

            <div class="row">
                <div class="col-12">
                    <div class="card dashboard-card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Étudiants en attente d'approbation</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($students)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                                    <h5>Aucun étudiant en attente d'approbation</h5>
                                    <p class="text-muted">Tous les étudiants ont été traités</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nom d'utilisateur</th>
                                                <th>Email</th>
                                                <th>Date d'inscription</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $student): ?>
                                                <tr class="student-card">
                                                    <td><?= htmlspecialchars($student['username']) ?></td>
                                                    <td><?= htmlspecialchars($student['email']) ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($student['created_at'])) ?></td>
                                                    <td class="approval-buttons">
                                                        <form method="POST" class="d-inline-block">
                                                            <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                                            <button type="submit" name="action" value="approve" class="btn btn-success btn-sm me-2">
                                                                <i class="fas fa-check me-1"></i> Approuver
                                                            </button>
                                                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir rejeter cet étudiant ?');">
                                                                <i class="fas fa-times me-1"></i> Rejeter
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
            </div>
        </main>
    </div>
</div>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});
</script>
</body>
</html>