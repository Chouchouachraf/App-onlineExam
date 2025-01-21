<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // Debug info
        error_log("Login attempt - Email: " . $email);

        // Validate input
        if (empty($email) || empty($password)) {
            $_SESSION['login_error'] = "All fields are required";
            header("Location: login.php");
            exit();
        }

        // Check user credentials
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Debug info
        error_log("User found in database: " . ($user ? 'Yes' : 'No'));
        if ($user) {
            error_log("User status: " . $user['status']);
            error_log("Stored password hash: " . $user['password']);
            error_log("Password verification result: " . (password_verify($password, $user['password']) ? 'True' : 'False'));
        }

        if ($user) {
            if (password_verify($password, $user['password'])) {
                // Check if approval-related columns exist
                $is_approved = true;
                try {
                    // Use the columnExists function from config.php
                    if (columnExists($conn, 'users', 'is_approved')) {
                        $is_approved = $user['is_approved'] == 1;
                    } elseif (columnExists($conn, 'users', 'status')) {
                        $is_approved = $user['status'] === 'active';
                    } else {
                        // If no approval-related column exists, log an error
                        error_log("No approval column found in users table");
                    }
                } catch(PDOException $e) {
                    error_log("Error checking user approval: " . $e->getMessage());
                    $is_approved = false;
                }

                if (!$is_approved) {
                    $_SESSION['login_error'] = "Your account is pending admin approval. Please wait.";
                } else if ($user['status'] === 'deleted') {
                    $_SESSION['login_error'] = "This account has been deleted. Please contact administrator.";
                } else {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];

                    // Redirect based on role
                    switch ($user['role']) {
                        case 'admin':
                            header("Location: ../admin/dashboard.php");
                            break;
                        case 'enseignant':
                            header("Location: ../teacher/dashboard.php");
                            break;
                        case 'etudiant':
                            header("Location: ../student/dashboard.php");
                            break;
                        default:
                            header("Location: ../index.php");
                    }
                    exit();
                }
            } else {
                $_SESSION['login_error'] = "Invalid email or password";
                error_log("Login failed: Password verification failed for email " . $email);
            }
        } else {
            $_SESSION['login_error'] = "Invalid email or password";
            error_log("Login failed: User not found for email " . $email);
        }

        if (isset($_SESSION['login_error'])) {
            header("Location: login.php");
            exit();
        }
    }
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['login_error'] = "An error occurred. Please try again later. Error: " . $e->getMessage();
    header("Location: login.php");
    exit();
}
?>
