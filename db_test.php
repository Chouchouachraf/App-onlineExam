<?php
require_once 'config/config.php';

try {
    // Test database connection
    echo "Testing database connection...<br>";
    if ($conn) {
        echo "Database connection successful!<br>";
    }

    // Check if users table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "Users table exists!<br>";
        
        // Show table structure
        $stmt = $conn->query("DESCRIBE users");
        echo "<br>Table structure:<br>";
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " - " . $row['Type'] . "<br>";
        }
        
        // Count users
        $stmt = $conn->query("SELECT COUNT(*) FROM users");
        $userCount = $stmt->fetchColumn();
        echo "<br>Number of users in database: " . $userCount . "<br>";
    } else {
        echo "Users table does not exist!<br>";
        
        // Create users table
        $sql = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            prenom VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'enseignant', 'etudiant') NOT NULL DEFAULT 'etudiant',
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $conn->exec($sql);
        echo "Users table created successfully!<br>";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
