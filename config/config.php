<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'exammaster');

// URL Root
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
define('URLROOT', $protocol . $host . '/App-onlineExam1');

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper functions for authentication
function checkUserStatus($email) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn();
    } catch(PDOException $e) {
        error_log("Error checking user status: " . $e->getMessage());
        return false;
    }
}

function createUser($username, $email, $password, $role) {
    global $conn;
    try {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Cette adresse email est déjà utilisée.'];
        }

        // Insert new user
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password, role, status, created_at) 
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt->execute([$username, $email, $hashed_password, strtolower($role)]);
        
        return ['success' => true, 'message' => 'Inscription réussie! Votre compte est en attente d\'approbation.'];
    } catch(PDOException $e) {
        error_log("Error creating user: " . $e->getMessage());
        return ['success' => false, 'message' => 'Une erreur est survenue lors de l\'inscription.'];
    }
}

function loginUser($email, $password, $role = '') {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Verify role if specified
            if (!empty($role) && strtolower($user['role']) !== $role) {
                return ['success' => false, 'message' => 'Vous n\'avez pas les permissions nécessaires pour ce rôle.'];
            }

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = ucfirst($user['role']);
            $_SESSION['name'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['last_activity'] = time();

            return ['success' => true, 'user' => $user];
        } else {
            if ($user && $user['status'] !== 'active') {
                return ['success' => false, 'message' => 'Votre compte est désactivé. Veuillez contacter l\'administrateur.'];
            }
            return ['success' => false, 'message' => 'Email ou mot de passe invalide.'];
        }
    } catch(PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Une erreur est survenue. Veuillez réessayer.'];
    }
}

// Include helper functions
require_once __DIR__ . '/functions.php';
