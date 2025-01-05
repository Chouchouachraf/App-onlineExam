<?php
    session_start();
    require_once '../config/connection.php';

    // Check if form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $error = '';

        // Validate email and password
        if (empty($email) || empty($password)) {
            $error = "Veuillez remplir tous les champs.";
        } else {
            try {
                // Prepare SQL query to fetch user
                $stmt = $conn->prepare("SELECT id, firstname, lastname, email, password, role FROM users WHERE email = :email");
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->execute();

                // Check if user exists
                if ($stmt->rowCount() === 1) {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Verify the password (plain-text comparison)
                    if ($password === $user['password']) {
                        // Set up session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['firstname'] . ' ' . $user['lastname'];
                        $_SESSION['user_role'] = $user['role'];

                        // Redirect based on role
                        switch ($user['role']) {
                            case 'Administrateur':
                                header("Location: ../admin/dashboard.php");
                                break;
                            case 'Enseignant':
                                header("Location: ../enseignant/dashboard.php");
                                break;
                            case 'Etudiant':
                                header("Location: ../etudiant/dashboard.php");
                                break;
                            default:
                                $error = "Rôle utilisateur invalide.";
                                break;
                            }
                        exit();
                    } else {
                        $error = "Email ou mot de passe incorrect.";
                    }
                } else {
                    $error = "Utilisateur introuvable.";
                }
            } catch (PDOException $e) {
                $error = "Une erreur s'est produite : " . $e->getMessage();
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamMaster</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="main-container">
        <div class="form-section">
            <div class="welcome">
                <h1>Connexion à ExamMaster</h1>
            </div>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Exemple@email.com" required>
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="Au moins 8 caractères" required>
                </div>
                <button type="submit" class="connect-button">Se connecter</button>
            </form>
        </div>

        <div class="photo-container">
            <div class="photo-frame">
                <img src="../assets/images/examination_tools 1.png" alt="Student Reading">
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
