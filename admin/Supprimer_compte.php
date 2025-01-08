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

// Connexion à la base de données
$conn = new mysqli("localhost", "root", "", "exammaster");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Traitement de la suppression
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    
    // Vérifier que l'utilisateur n'est pas l'administrateur actuel
    if ($user_id != $_SESSION['user_id']) {
        // Commencer une transaction
        $conn->begin_transaction();
        
        try {
            // Supprimer l'utilisateur
            $sql = "DELETE FROM users WHERE id = ? AND role != 'Administrateur'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $conn->commit();
                    $success_message = "Compte supprimé avec succès!";
                } else {
                    throw new Exception("Impossible de supprimer un compte administrateur.");
                }
            } else {
                throw new Exception("Erreur lors de la suppression du compte.");
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = $e->getMessage();
        }
    } else {
        $error_message = "Vous ne pouvez pas supprimer votre propre compte!";
    }
}

// Récupération des utilisateurs
$sql = "SELECT * FROM users WHERE id != ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamMaster - Supprimer des comptes</title>
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
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #1f2937;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .users-table th,
        .users-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .users-table th {
            background-color: #f9fafb;
            font-weight: 600;
        }

        .delete-btn {
            background-color: #dc2626;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .delete-btn:hover {
            background-color: #b91c1c;
        }

        .back-btn {
            background-color: #6b7280;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .back-btn:hover {
            background-color: #4b5563;
        }

        .success-message {
            background-color: #10b981;
            color: white;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }

        .error-message {
            background-color: #ef4444;
            color: white;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            border-radius: 0.5rem;
            padding: 2rem;
            max-width: 500px;
            margin: 2rem auto;
            text-align: center;
        }

        .modal-title {
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            color: #dc2626;
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .cancel-btn {
            background-color: #6b7280;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
        }

        .confirm-btn {
            background-color: #dc2626;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
        }

        .logout-btn {
            background-color: #dc2626;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <div class="brand">ExamMaster</div>
            <form method="POST" action="../auth/logout.php" style="display: inline;">
                <button type="submit" name="logout" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </button>
            </form>
        </div>
    </nav>

    <div class="container">
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Retour au tableau de bord
        </a>

        <div class="card">
            <h1 class="page-title">Suppression des comptes utilisateurs</h1>
            
            <?php if (isset($success_message)): ?>
                <div class="success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <table class="users-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Date de création</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['lastname']); ?></td>
                        <td><?php echo htmlspecialchars($row['firstname']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['role']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                        <td>
                            <button class="delete-btn" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?>')">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de confirmation -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-title">Confirmer la suppression</h2>
            <p>Êtes-vous sûr de vouloir supprimer le compte de <span id="userName"></span> ?</p>
            <p>Cette action est irréversible.</p>
            
            <div class="modal-buttons">
                <button class="cancel-btn" onclick="closeDeleteModal()">Annuler</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <button type="submit" name="delete_user" class="confirm-btn">Confirmer</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(userId, userName) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('userName').textContent = userName;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('deleteModal')) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>
<?php 
$stmt->close();
$conn->close(); 
?>