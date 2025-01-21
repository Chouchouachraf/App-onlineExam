<?php
session_start();

$host = 'localhost';
$dbname = 'schemase';
$user = 'root';
$pass = '';

// Include the db_utils file
require_once '../config/db_utils.php';

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

        // Validate input
        $errors = [];
        
        if (empty($nom)) $errors[] = "Last name is required";
        if (empty($prenom)) $errors[] = "First name is required";
        if (empty($email)) $errors[] = "Email is required";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
        if (empty($password)) $errors[] = "Password is required";
        if ($password !== $confirm_password) $errors[] = "Passwords do not match";
        if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters long";

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

                // Check if approval-related columns exist
                $insert_query = "";
                $params = [$nom, $prenom, $email, $hashed_password];

                try {
                    // Add role column check and insertion
                    if (columnExists($conn, 'users', 'role')) {
                        $insert_query = "INSERT INTO users (nom, prenom, email, password, role, is_approved) VALUES (?, ?, ?, ?, ?, ?)";
                        $params = [
                            $nom, 
                            $prenom, 
                            $email, 
                            $hashed_password, 
                            'etudiant', 
                            0
                        ];
                    } elseif (columnExists($conn, 'users', 'is_approved')) {
                        $insert_query = "INSERT INTO users (nom, prenom, email, password, is_approved) VALUES (?, ?, ?, ?, ?)";
                        $params = [
                            $nom, 
                            $prenom, 
                            $email, 
                            $hashed_password, 
                            0
                        ];
                    } elseif (columnExists($conn, 'users', 'status')) {
                        $insert_query = "INSERT INTO users (nom, prenom, email, password, status) VALUES (?, ?, ?, ?, ?)";
                        $params = [
                            $nom, 
                            $prenom, 
                            $email, 
                            $hashed_password, 
                            'inactive'
                        ];
                    } else {
                        // If no approval-related column exists, log an error
                        error_log("No approval column found in users table");
                        throw new PDOException("Cannot insert user: no approval column");
                    }

                    // Prepare and execute the insert statement
                    $stmt = $conn->prepare($insert_query);
                    $result = $stmt->execute($params);

                    if ($result) {
                        $conn->commit();
                        $_SESSION['signup_message'] = "Account created successfully. Waiting for admin approval.";
                        header("Location: signup_success.php");
                        exit();
                    } else {
                        $conn->rollBack();
                        $_SESSION['signup_errors'] = ["Error creating account. Please try again."];
                        header("Location: signup.php");
                        exit();
                    }
                } catch(PDOException $e) {
                    $conn->rollBack();
                    $errors[] = "Database error: " . $e->getMessage();
                    error_log("Signup Error: " . $e->getMessage());
                }
            } catch (PDOException $e) {
                $conn->rollBack();
                $errors[] = "Database error: " . $e->getMessage();
                error_log("Signup Error: " . $e->getMessage());
            }
        }

        if (!empty($errors)) {
            $_SESSION['signup_errors'] = $errors;
            $_SESSION['signup_form_data'] = [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email
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
