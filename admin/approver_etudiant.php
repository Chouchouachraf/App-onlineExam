<?php
session_start();
require_once '../config/connection.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Administrateur') {
    header("Location: ../auth/login.php");
    exit();
}

// Traitement de l'approbation/rejet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE id = :id AND role = 'Etudiant'");
            $stmt->bindParam(':id', $student_id, PDO::PARAM_INT);
            
            if($stmt->execute()) {
                $_SESSION['success_message'] = "L'étudiant a été approuvé avec succès.";
            }
        } elseif ($action === 'reject') {
            // Archiver l'utilisateur avant suppression
            $stmt = $conn->prepare("INSERT INTO archived_users (user_id, firstname, lastname, email, password, role, reason_for_deletion) 
                                  SELECT id, firstname, lastname, email, password, role, 'Rejected by admin' 
                                  FROM users WHERE id = :id");
            $stmt->bindParam(':id', $student_id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Supprimer l'utilisateur
            $stmt = $conn->prepare("DELETE FROM users WHERE id = :id AND role = 'Etudiant'");
            $stmt->bindParam(':id', $student_id, PDO::PARAM_INT);
            
            if($stmt->execute()) {
                $_SESSION['success_message'] = "L'étudiant a été rejeté et supprimé du système.";
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Une erreur est survenue: " . $e->getMessage();
    }
    
    header("Location: approver_etudiant.php");
    exit();
}

// Récupérer la liste des étudiants en attente d'approbation
try {
    $stmt = $conn->prepare("
        SELECT u.id, u.firstname, u.lastname, u.email, u.created_at, GROUP_CONCAT(c.name) as classes
        FROM users u
        LEFT JOIN class_student cs ON u.id = cs.student_id
        LEFT JOIN classes c ON cs.class_id = c.id
        WHERE u.role = 'Etudiant' AND (u.status IS NULL OR u.status = 'pending')
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $pending_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Une erreur est survenue: " . $e->getMessage();
    $pending_students = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approbation des étudiants - ExamMaster</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #2C3E50;
            margin-bottom: 20px;
            text-align: center;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
        }

        .table th, 
        .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2C3E50;
        }

        .table tr:hover {
            background-color: #f8f9fa;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .btn-approve {
            background-color: #28a745;
            color: white;
            margin-right: 5px;
        }

        .btn-approve:hover {
            background-color: #218838;
        }

        .btn-reject {
            background-color: #dc3545;
            color: white;
        }

        .btn-reject:hover {
            background-color: #c82333;
        }

        .no-students {
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .btn-back {
            background-color:hsl(208, 7.30%, 45.70%);
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
        }

        .btn-back:hover {
            background-color: #5a6268;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .table th, 
            .table td {
                padding: 8px;
            }

            .btn {
                padding: 6px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="top-bar">
            <h1>Approbation des nouveaux étudiants</h1>
            <a href="dashboard.php" class="btn-back">Retour au tableau de bord</a>
        </div>

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

        <?php if (empty($pending_students)): ?>
            <div class="no-students">
                <p>Aucun étudiant en attente d'approbation.</p>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Nom complet</th>
                        <th>Email</th>
                        <th>Date d'inscription</th>
                        <th>Classes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($student['created_at'])); ?></td>
                            <td><?php echo $student['classes'] ? htmlspecialchars($student['classes']) : 'Aucune classe'; ?></td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-approve"
                                            onclick="return confirm('Êtes-vous sûr de vouloir approuver cet étudiant ?')">
                                        Approuver
                                    </button>
                                    <button type="submit" name="action" value="reject" class="btn btn-reject"
                                            onclick="return confirm('Êtes-vous sûr de vouloir rejeter cet étudiant ? Cette action est irréversible.')">
                                        Rejeter
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>