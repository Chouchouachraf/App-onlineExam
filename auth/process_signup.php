<?php
session_start();

$host = 'localhost';
$dbname = 'exammaster';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Get form data
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $role = trim($_POST['role']);

        // Validate input
        $errors = [];
        
        if (empty($nom)) $errors[] = "Last name is required";
        if (empty($prenom)) $errors[] = "First name is required";
        if (empty($email)) $errors[] = "Email is required";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
        if (empty($password)) $errors[] = "Password is required";
        if ($password !== $confirm_password) $errors[] = "Passwords do not match";
        if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters long";
        if (empty($role)) $errors[] = "Role is required";
        if (!in_array($role, ['etudiant', 'enseignant', 'admin'])) $errors[] = "Invalid role selected";

        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email already exists";
        }

        if (empty($errors)) {
            // Hash password with proper algorithm
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Verify the hash was created correctly
            if (!$hashed_password || strlen($hashed_password) < 60) {
                $_SESSION['signup_errors'] = ["Error creating secure password hash. Please try again."];
                header("Location: signup.php");
                exit();
            }

            try {
                $conn->beginTransaction();

                // Get class and department IDs if provided
                $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;
                $department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : null;

                // For students, class is required
                if ($role === 'etudiant' && empty($class_id)) {
                    $errors[] = "Class selection is required for students";
                }

                // For teachers, department is required
                if ($role === 'enseignant' && empty($department_id)) {
                    $errors[] = "Department selection is required for teachers";
                }

                if (empty($errors)) {
                    // Insert user
                    $stmt = $conn->prepare("
                        INSERT INTO users (email, password, nom, prenom, role, class_id, department_id, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
                    ");
                    $stmt->execute([$email, $hashed_password, $nom, $prenom, $role, $class_id, $department_id]);

                    $conn->commit();
                    $_SESSION['signup_success'] = true;
                    header("Location: signup_success.php");
                    exit();
                }
            } catch (PDOException $e) {
                $conn->rollBack();
                $errors[] = "Database error: " . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            $_SESSION['signup_errors'] = $errors;
            $_SESSION['signup_form_data'] = [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'role' => $role
            ];
            header("Location: signup.php");
            exit();
        }
    } else {
        // If accessed directly without POST, redirect to signup page
        header("Location: signup.php");
        exit();
    }
} catch(PDOException $e) {
    $_SESSION['signup_errors'] = ["An error occurred. Please try again later."];
    header("Location: signup.php");
    exit();
}
?>
