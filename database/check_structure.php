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

    echo "<h2>Current Database Structure:</h2>";

    // Check users table structure
    $stmt = $conn->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Users Table Columns:</h3>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";

    // Check if exams table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'exams'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        $stmt = $conn->query("DESCRIBE exams");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Exams Table Columns:</h3>";
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
    } else {
        echo "<p>Exams table does not exist!</p>";
    }

    // Display some sample data
    echo "<h3>Sample Users:</h3>";
    $stmt = $conn->query("SELECT * FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($users);
    echo "</pre>";

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
