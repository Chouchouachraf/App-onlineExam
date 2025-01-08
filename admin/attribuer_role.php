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
    $search_condition = "WHERE firstname LIKE ? OR lastname LIKE ? OR email LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

// Get total users for pagination
$count_query = "SELECT COUNT(*) FROM users " . $search_condition;
$stmt = $conn->prepare($count_query);
$stmt->execute($params);
$total_users = $stmt->fetchColumn();
$total_pages = ceil($total_users / $users_per_page);

// Get users
$query = "SELECT id, firstname, lastname, email, role FROM users $search_condition 
          ORDER BY id DESC LIMIT $users_per_page OFFSET $offset";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Update role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    
    $update_query = "UPDATE users SET role = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    
    try {
        $stmt->execute([$new_role, $user_id]);
        $_SESSION['success_message'] = "Le rôle a été mis à jour avec succès.";
    } catch(PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de la mise à jour du rôle.";
    }
    
    header("Location: attribuer_role.php");
    exit();
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
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <div class="brand">ExamMaster</div>
            <form method="POST" action="../auth/logout.php" style="display: inline;">
                <button type="submit" name="logout" class="logout-btn">
                    Déconnexion
                </button>
            </form>
        </div>
    </nav>

    <div class="container">
        <div class="role-management">
            <h2>Attribution des Rôles</h2>

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
                        <th>Nouveau rôle</th>
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
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <select name="new_role" class="role-select">
                                        <option value="Administrateur" <?php echo $user['role'] === 'Administrateur' ? 'selected' : ''; ?>>Administrateur</option>
                                        <option value="Enseignant" <?php echo $user['role'] === 'Enseignant' ? 'selected' : ''; ?>>Enseignant</option>
                                        <option value="Etudiant" <?php echo $user['role'] === 'Etudiant' ? 'selected' : ''; ?>>Etudiant</option>
                                    </select>
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
</body>
</html>
