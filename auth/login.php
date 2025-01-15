<!-- auth/login.php -->
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
            $stmt = $conn->prepare("SELECT id, firstname, lastname, email, password, role, status FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();

            // Check if user exists
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verify the password
                if ($password === $user['password']) {
                    // Check if student account is approved
                    if ($user['role'] === 'Etudiant' && $user['status'] !== 'approved') {
                        $error = "Votre compte est en attente d'approbation par l'administrateur.";
                    } else {
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
                    }
                } else {
                    $error = "Email ou mot de passe incorrect.";
                }
            } else {
                $error = "Email ou mot de passe incorrect.";
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
    <title>Connexion - ExamMaster</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            background-color: #f5f5f5;
        }

        .main-container {
            display: flex;
            min-height: 100vh;
            background-color: white;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .form-section {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-width: 500px;
            margin: 0 auto;
        }

        .welcome {
            text-align: center;
            margin-bottom: 40px;
        }

        .welcome h1 {
            color: #2C3E50;
            font-size: 2.5em;
            margin-bottom: 20px;
            animation: fadeInDown 1s;
        }

        .form-group {
            margin-bottom: 25px;
            animation: fadeInUp 0.5s;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #2C3E50;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #3498db;
        }

        .connect-button {
            width: 100%;
            padding: 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
            animation: fadeInUp 0.7s;
        }

        .connect-button:hover {
            background-color: #2980b9;
        }

        .photo-container {
            flex: 1;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .photo-frame {
            max-width: 100%;
            animation: fadeIn 1s;
        }

        .photo-frame img {
            max-width: 100%;
            height: auto;
        }

        .error {
            background-color: #ff6b6b;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: shake 0.5s;
            text-align: center;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        .signup-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .signup-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }

            .form-section {
                padding: 20px;
            }

            .photo-container {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="form-section">
            <div class="welcome">
                <h1>Connexion à ExamMaster</h1>
            </div>
            <?php if (isset($error)): ?>
                <div class="error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success">
                    <?php 
                    echo htmlspecialchars($_SESSION['success_message']);
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="Exemple@email.com"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        required
                    >
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Votre mot de passe"
                        required
                    >
                </div>
                <button type="submit" class="connect-button">Se connecter</button>
                <div class="signup-link">
                    Pas encore de compte? <a href="signup.php">S'inscrire</a>
                </div>
            </form>
        </div>

        <div class="photo-container">
            <div class="photo-frame">
                <img src="../assets/images/examination_tools 1.png" alt="Student Reading">
            </div>
        </div>
    </div>
</body>
</html>