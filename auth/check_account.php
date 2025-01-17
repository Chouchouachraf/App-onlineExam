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

    // Get all users
    $stmt = $conn->query("SELECT id, email, status, role, password FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>User Accounts Status:</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Email</th><th>Status</th><th>Role</th><th>Password Hash Length</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['id']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . htmlspecialchars($user['status']) . "</td>";
        echo "<td>" . htmlspecialchars($user['role']) . "</td>";
        echo "<td>" . strlen($user['password']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
