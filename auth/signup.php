<?php
session_start();
require_once '../config/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $error = '';

    // Validation de base
    if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format d'email invalide.";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } else {
        try {
            // Vérifier si l'email existe déjà
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $error = "Cet email est déjà utilisé.";
            } else {
                // Insérer le nouvel utilisateur avec le statut 'pending'
                $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, password, role, status) VALUES (:firstname, :lastname, :email, :password, 'Etudiant', 'pending')");
                $stmt->bindParam(':firstname', $firstname, PDO::PARAM_STR);
                $stmt->bindParam(':lastname', $lastname, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':password', $password, PDO::PARAM_STR);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Votre compte a été créé avec succès. Veuillez attendre l'approbation de l'administrateur.";
                    header("Location: login.php");
                    exit();
                } else {
                    $error = "Une erreur est survenue lors de l'inscription.";
                }
            }
        } catch (PDOException $e) {
            $error = "Erreur de base de données: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - ExamMaster</title>
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
            max-width: 600px;
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
            margin-bottom: 20px;
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

        .success {
            background-color: #51cf66;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: fadeIn 0.5s;
            text-align: center;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .login-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .name-group {
            display: flex;
            gap: 20px;
        }

        .name-group .form-group {
            flex: 1;
        }

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

            .name-group {
                flex-direction: column;
                gap: 0;
            }
        }

        .password-requirements {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="form-section">
            <div class="welcome">
                <h1>Inscription à ExamMaster</h1>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="name-group">
                    <div class="form-group">
                        <label for="firstname">Prénom</label>
                        <input 
                            type="text" 
                            id="firstname" 
                            name="firstname" 
                            value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>"
                            required
                        >
                    </div>
                    <div class="form-group">
                        <label for="lastname">Nom</label>
                        <input 
                            type="text" 
                            id="lastname" 
                            name="lastname" 
                            value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="exemple@email.com"
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
                        placeholder="Minimum 8 caractères"
                        required
                    >
                    <div class="password-requirements">
                        Le mot de passe doit contenir au moins 8 caractères
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        placeholder="Confirmez votre mot de passe"
                        required
                    >
                </div>

                <button type="submit" class="connect-button">S'inscrire</button>

                <div class="login-link">
                    Déjà inscrit? <a href="login.php">Se connecter</a>
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