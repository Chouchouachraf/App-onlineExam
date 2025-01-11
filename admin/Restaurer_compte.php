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

// Handle account deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'], $_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);

    try {
        // Start transaction
        $conn->beginTransaction();

        // Get user data before deletion
        $user_query = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($user_query);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Insert into archived_users
            $archive_query = "INSERT INTO archived_users (user_id, firstname, lastname, email, password, role, created_at, deleted_at, reason_for_deletion, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, 'archived')";
            $stmt = $conn->prepare($archive_query);
            $stmt->execute([
                $user['id'],
                $user['firstname'],
                $user['lastname'],
                $user['email'],
                $user['password'],
                $user['role'],
                $user['created_at'],
                'Account deleted by administrator'
            ]);

            // Mark user as deleted
            $delete_query = "UPDATE users SET deleted_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->execute([$user_id]);

            // Create notification
            $notification_query = "INSERT INTO notifications (user_id, title, message, type) 
                VALUES (?, 'Compte archivé', 'Un compte utilisateur a été archivé.', 'warning')";
            $stmt = $conn->prepare($notification_query);
            $stmt->execute([$_SESSION['user_id']]);

            $conn->commit();
            $_SESSION['success'] = "Le compte a été archivé avec succès.";
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Erreur : " . $e->getMessage();
    }

    header("Location: restaurer_compte.php");
    exit();
}

// Handle account restoration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore'], $_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);

    try {
        $conn->beginTransaction();

        // Restore the user
        $restore_query = "UPDATE users SET deleted_at = NULL WHERE id = ?";
        $stmt = $conn->prepare($restore_query);
        $stmt->execute([$user_id]);

        // Update archived_users status
        $archive_update = "UPDATE archived_users SET status = 'restored' WHERE user_id = ? AND status = 'archived'";
        $stmt = $conn->prepare($archive_update);
        $stmt->execute([$user_id]);

        // Create notification
        $notification_query = "INSERT INTO notifications (user_id, title, message, type) 
            VALUES (?, 'Compte restauré', 'Un compte utilisateur a été restauré.', 'success')";
        $stmt = $conn->prepare($notification_query);
        $stmt->execute([$_SESSION['user_id']]);

        $conn->commit();
        $_SESSION['success'] = "Le compte a été restauré avec succès.";
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Erreur : " . $e->getMessage();
    }

    header("Location: restaurer_compte.php");
    exit();
}

// Retrieve archived accounts with more details
$archived_accounts = [];
try {
    $accounts_query = "SELECT u.id, u.firstname, u.lastname, u.email, u.role, u.deleted_at,
        a.reason_for_deletion, a.status
        FROM users u
        JOIN archived_users a ON u.id = a.user_id
        WHERE u.deleted_at IS NOT NULL AND a.status = 'archived'
        ORDER BY u.deleted_at DESC";
    $stmt = $conn->query($accounts_query);
    $archived_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamMaster - Restaurer des Comptes Archivés</title>
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
            color: #4b5563;
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
            transition: all 0.2s;
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-archived {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #6b7280;
        }
        .reason-text {
            font-size: 0.875rem;
            color: #6b7280;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
                <i class="fas fa-check-circle"></i>
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2 class="card-title">
                <i class="fas fa-archive"></i>
                Comptes Archivés
            </h2>

            <div class="table-container">
                <?php if (count($archived_accounts) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Date d'archivage</th>
                                <th>Raison</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archived_accounts as $account): ?>
                                <tr>
                                    <td><?= htmlspecialchars($account['firstname'] . ' ' . $account['lastname']); ?></td>
                                    <td><?= htmlspecialchars($account['email']); ?></td>
                                    <td><?= htmlspecialchars($account['role']); ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($account['deleted_at'])); ?></td>
                                    <td>
                                        <span class="reason-text" title="<?= htmlspecialchars($account['reason_for_deletion']); ?>">
                                            <?= htmlspecialchars($account['reason_for_deletion']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir restaurer ce compte ?');">
                                            <input type="hidden" name="user_id" value="<?= $account['id']; ?>">
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
                        <p>Aucun compte archivé à restaurer.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>