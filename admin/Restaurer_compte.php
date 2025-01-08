<?php
    session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        header("Location: ../auth/login.php");
        exit();
    }

    if ($_SESSION['user_role'] !== 'Administrateur') {
        header("Location: ../auth/login.php");
        exit();
    }

    require_once '../config/connection.php';

    // Traitement de la restauration
    if (isset($_POST['restore']) && isset($_POST['user_id'])) {
        $user_id = $_POST['user_id'];
        
        // Restaurer l'utilisateur (mettre à jour le statut)
        $restore_query = "UPDATE users SET deleted_at = NULL WHERE id = ?";
        $stmt = $conn->prepare($restore_query);
        
        if ($stmt->execute([$user_id])) {
            // Créer une notification
            $notification_query = "INSERT INTO notifications (user_id, title, message, type) 
                VALUES (?, 'Compte restauré', 'Un compte utilisateur a été restauré.', 'success')";
            $stmt = $conn->prepare($notification_query);
            $stmt->execute([$_SESSION['user_id']]);
            
            $_SESSION['success'] = "Le compte a été restauré avec succès.";
        } else {
            $_SESSION['error'] = "Une erreur est survenue lors de la restauration du compte.";
        }
        
        header("Location: restaurer_compte.php");
        exit();
    }

    // Récupérer les comptes supprimés
    $accounts_query = "SELECT id, firstname, lastname, email, role, deleted_at 
        FROM users 
        WHERE deleted_at IS NOT NULL 
        ORDER BY deleted_at DESC";
    $stmt = $conn->query($accounts_query);
    $deleted_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamMaster - Restaurer des Comptes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background-color: #f3f4f6;
            color: #1f2937;
        }

        .navbar {
            background-color: #1e40af;
            color: white;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            padding: 1.5rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #1e40af;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background-color: #f9fafb;
            font-weight: 600;
        }

        tr:hover {
            background-color: #f9fafb;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background-color: #1e40af;
            color: white;
        }

        .btn-primary:hover {
            background-color: #1e3a8a;
        }

        .btn-success {
            background-color: #059669;
            color: white;
        }

        .btn-success:hover {
            background-color: #047857;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #1e40af;
            text-decoration: none;
            margin-bottom: 1rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #6b7280;
        }

        @media (max-width: 768px) {
            .table-container {
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <div class="brand">ExamMaster</div>
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Retour au tableau de bord
            </a>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2 class="card-title">
                <i class="fas fa-undo"></i>
                Restaurer des Comptes Supprimés
            </h2>

            <div class="table-container">
                <?php if (count($deleted_accounts) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Date de suppression</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deleted_accounts as $account): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($account['firstname'] . ' ' . $account['lastname']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($account['email']); ?></td>
                                    <td><?php echo htmlspecialchars($account['role']); ?></td>
                                    <td>
                                        <?php echo date('d/m/Y H:i', strtotime($account['deleted_at'])); ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $account['id']; ?>">
                                            <button type="submit" name="restore" class="btn btn-success">
                                                <i class="fas fa-undo"></i> Restaurer
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-info-circle"></i>
                        <p>Aucun compte supprimé à restaurer.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>