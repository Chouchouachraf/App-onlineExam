<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'exammaster';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Default password for all accounts will be "password123"
    $default_password = "password123";
    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

    // Update all accounts with proper password hash
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE LENGTH(password) != 60");
    $result = $stmt->execute([$hashed_password]);

    if ($result) {
        echo "<h2>Password Reset Successful</h2>";
        echo "<p>All accounts have been updated with the default password: <strong>password123</strong></p>";
        echo "<p>Please log in with your email and this default password, then change your password.</p>";
        echo "<p><a href='login.php'>Go to Login Page</a></p>";
    } else {
        echo "<h2>Error</h2>";
        echo "<p>Failed to update passwords.</p>";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
