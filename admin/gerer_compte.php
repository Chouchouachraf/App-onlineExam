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

// Traitement de l'ajout d'un nouvel utilisateur
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $password = $_POST['password'];  // Dans un cas réel, il faudrait hasher le mot de passe
    $role = $_POST['role'];

    $sql = "INSERT INTO users (firstname, lastname, email, password, role) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $firstname, $lastname, $email, $password, $role);
    
    if ($stmt->execute()) {
        $success_message = "Utilisateur ajouté avec succès!";
    } else {
        $error_message = "Erreur lors de l'ajout: " . $conn->error;
    }
    $stmt->close();
}

// Traitement de la modification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    $sql = "UPDATE users SET firstname=?, lastname=?, email=?, role=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $firstname, $lastname, $email, $role, $user_id);
    
    if ($stmt->execute()) {
        $success_message = "Utilisateur mis à jour avec succès!";
    } else {
        $error_message = "Erreur lors de la mise à jour: " . $conn->error;
    }
    $stmt->close();
}

// Récupération des utilisateurs
$sql = "SELECT * FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamMaster - Gérer les comptes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/Gerer_compte.css">
    <style>
        /* Styles pour le bouton d'ajout */
        .add-user-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .add-user-btn:hover {
            background-color: #45a049;
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
            <h1 class="page-title">Gestion des comptes utilisateurs</h1>
            
            <?php if (isset($success_message)): ?>
                <div class="success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <button class="add-user-btn" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Ajouter un utilisateur
            </button>

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
                            <button class="edit-btn" onclick="openEditModal(<?php 
                                echo htmlspecialchars(json_encode($row)); 
                            ?>)">
                                <i class="fas fa-edit"></i> Modifier
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal d'ajout -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-title">Ajouter un utilisateur</h2>
            <form id="addForm" method="POST">
                <div class="form-group">
                    <label for="add_firstname">Prénom</label>
                    <input type="text" id="add_firstname" name="firstname" required>
                </div>

                <div class="form-group">
                    <label for="add_lastname">Nom</label>
                    <input type="text" id="add_lastname" name="lastname" required>
                </div>

                <div class="form-group">
                    <label for="add_email">Email</label>
                    <input type="email" id="add_email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="add_password">Mot de passe</label>
                    <input type="password" id="add_password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="add_role">Rôle</label>
                    <select id="add_role" name="role" required>
                        <option value="Administrateur">Administrateur</option>
                        <option value="Enseignant">Enseignant</option>
                        <option value="Etudiant">Etudiant</option>
                    </select>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="back-btn" onclick="closeAddModal()">Annuler</button>
                    <button type="submit" name="add_user" class="edit-btn">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de modification -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-title">Modifier l'utilisateur</h2>
            <form id="editForm" method="POST">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-group">
                    <label for="edit_firstname">Prénom</label>
                    <input type="text" id="edit_firstname" name="firstname" required>
                </div>

                <div class="form-group">
                    <label for="edit_lastname">Nom</label>
                    <input type="text" id="edit_lastname" name="lastname" required>
                </div>

                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="edit_role">Rôle</label>
                    <select id="edit_role" name="role" required>
                        <option value="Administrateur">Administrateur</option>
                        <option value="Enseignant">Enseignant</option>
                        <option value="Etudiant">Etudiant</option>
                    </select>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="back-btn" onclick="closeEditModal()">Annuler</button>
                    <button type="submit" name="update_user" class="edit-btn">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        function openEditModal(userData) {
            document.getElementById('edit_user_id').value = userData.id;
            document.getElementById('edit_firstname').value = userData.firstname;
            document.getElementById('edit_lastname').value = userData.lastname;
            document.getElementById('edit_email').value = userData.email;
            document.getElementById('edit_role').value = userData.role;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Fermer les modals si on clique en dehors
        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
            if (event.target == document.getElementById('addModal')) {
                closeAddModal();
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>