<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Administrateur') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/connection.php';

// Pagination
$users_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $users_per_page;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
$params = [];

if (!empty($search)) {
    $search_condition = "WHERE u.firstname LIKE ? OR u.lastname LIKE ? OR u.email LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

// Get total users for pagination
$count_query = "SELECT COUNT(*) FROM users u " . $search_condition;
$stmt = $conn->prepare($count_query);
$stmt->execute($params);
$total_users = $stmt->fetchColumn();
$total_pages = ceil($total_users / $users_per_page);

// Get users with their class information
$query = "SELECT u.id, u.firstname, u.lastname, u.email, u.role, 
          GROUP_CONCAT(c.name) as class_names, GROUP_CONCAT(c.id) as class_ids
          FROM users u 
          LEFT JOIN class_student cs ON u.id = cs.student_id 
          LEFT JOIN classes c ON cs.class_id = c.id 
          " . $search_condition . "
          GROUP BY u.id 
          ORDER BY u.id DESC LIMIT $users_per_page OFFSET $offset";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all classes (spécifiquement dd201 et dd202)
$classes_query = "SELECT id, name FROM classes WHERE name IN ('dd201', 'dd202') ORDER BY name";
$stmt = $conn->prepare($classes_query);
$stmt->execute();
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Update role and class
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_role'])) {
        $user_id = $_POST['user_id'];
        $new_role = $_POST['new_role'];
        $new_class = isset($_POST['class']) ? $_POST['class'] : null;
        
        try {
            $conn->beginTransaction();
            
            // Update role
            $update_query = "UPDATE users SET role = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->execute([$new_role, $user_id]);
            
            // Update class assignments if role is student
            if ($new_role === 'Etudiant' && $new_class) {
                // Remove existing class assignments
                $delete_query = "DELETE FROM class_student WHERE student_id = ?";
                $stmt = $conn->prepare($delete_query);
                $stmt->execute([$user_id]);
                
                // Add new class assignment
                $insert_query = "INSERT INTO class_student (student_id, class_id) VALUES (?, ?)";
                $stmt = $conn->prepare($insert_query);
                $stmt->execute([$user_id, $new_class]);
            }
            
            $conn->commit();
            $_SESSION['success_message'] = "Le rôle et la classe ont été mis à jour avec succès.";
        } catch(PDOException $e) {
            $conn->rollBack();
            $_SESSION['error_message'] = "Erreur lors de la mise à jour.";
        }
        
        header("Location: attribuer_role.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attribution des Rôles - ExamMaster</title>
    <link rel="stylesheet" href="../assets/css/attribuer_role.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        .class-select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            margin-top: 5px;
            width: 150px;
        }
        .class-selection {
            display: none;
        }
        .role-table th, .role-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .role-select, .class-select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .update-role-btn {
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .update-role-btn:hover {
            background-color: #45a049;
        }
        .current-class {
            font-weight: 500;
            color: #2196F3;
        }
 .navbar {
        background-color: #1e40af;
        color: white;
        padding: 1rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

    .nav-buttons {
        display: flex;
        align-items: center;
        gap: 1.5rem; /* Espace entre les boutons */
    }

    .back-btn {
        background-color: hsl(208, 7.3%, 45.7%);
        color: white;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 0.375rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: background-color 0.2s;
    }

    .back-btn:hover {
        background-color: #2563eb;
    }

    .logout-btn {
        background-color: #dc2626;
        color: white;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 0.375rem;
        cursor: pointer;
        transition: background-color 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .logout-btn:hover {
        background-color: #b91c1c;
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
    <div class="container"> <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Retour au tableau de bord
            </a>    <br>

        <div class="role-management">
            <h2>Attribution des Rôles et Classes</h2>  
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <?php 
                        echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <form method="GET" class="search-bar">
                <input type="text" name="search" class="search-input" 
                       placeholder="Rechercher par nom, prénom ou email" 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="search-btn">Rechercher</button>
            </form>

            <table class="role-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>Rôle actuel</th>
                        <th>Classe actuelle</th>
                        <th>Nouveau rôle</th>
                        <th>Nouvelle classe</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['firstname']); ?></td>
                            <td><?php echo htmlspecialchars($user['lastname']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td class="current-class">
                                <?php echo $user['class_names'] ? htmlspecialchars($user['class_names']) : 'Aucune classe'; ?>
                            </td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <select name="new_role" class="role-select" onchange="toggleClassSelection(this)">
                                        <option value="Administrateur" <?php echo $user['role'] === 'Administrateur' ? 'selected' : ''; ?>>Administrateur</option>
                                        <option value="Enseignant" <?php echo $user['role'] === 'Enseignant' ? 'selected' : ''; ?>>Enseignant</option>
                                        <option value="Etudiant" <?php echo $user['role'] === 'Etudiant' ? 'selected' : ''; ?>>Etudiant</option>
                                    </select>
                            </td>
                            <td>
                                <div class="class-selection" <?php echo $user['role'] === 'Etudiant' ? 'style="display:block;"' : ''; ?>>
                                    <select name="class" class="class-select">
                                        <option value="">Sélectionner une classe</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class['id']; ?>" 
                                                <?php echo (strpos($user['class_ids'] ?? '', (string)$class['id']) !== false) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($class['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </td>
                            <td>
                                    <button type="submit" name="update_role" class="update-role-btn">
                                        Mettre à jour
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                       class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleClassSelection(roleSelect) {
            const classSelection = roleSelect.closest('tr').querySelector('.class-selection');
            if (roleSelect.value === 'Etudiant') {
                classSelection.style.display = 'block';
            } else {
                classSelection.style.display = 'none';
            }
        }

        // Initialize class selections on page load
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelects = document.querySelectorAll('.role-select');
            roleSelects.forEach(function(select) {
                toggleClassSelection(select);
            });
        });
    </script>
</body>
</html>