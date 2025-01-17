<?php
// Session is already started in config.php

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['role'] ?? '';
}

function isAdmin() {
    return getUserRole() === 'Admin';
}

function isTeacher() {
    return getUserRole() === 'Teacher';
}

function isStudent() {
    return getUserRole() === 'Student';
}

function getUserData($userId = null) {
    if (!$userId && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }
    
    if (!$userId) {
        return null;
    }

    global $conn;
    try {
        $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error fetching user data: " . $e->getMessage());
        return null;
    }
}

function redirect($path) {
    header("Location: $path");
    exit();
}

function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

function displayFlashMessage() {
    $message = getFlashMessage();
    if ($message) {
        $type = $message['type'];
        $text = $message['message'];
        echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
                $text
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
    }
}

// Debug function - remove in production
function debug($data) {
    echo "<pre>";
    var_dump($data);
    echo "</pre>";
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
