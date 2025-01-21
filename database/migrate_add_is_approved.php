<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'schemase';
$user = 'root';
$pass = '';

try {
    // Create PDO connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if users table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    $table_exists = $stmt->fetch();

    if (!$table_exists) {
        echo "Error: 'users' table does not exist.<br>";
        exit();
    }

    // Get existing columns
    $stmt = $conn->query("SHOW COLUMNS FROM users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Check if is_approved column exists
    if (!in_array('is_approved', $columns)) {
        try {
            // Safely add is_approved column
            $alter_query = "ALTER TABLE users ADD COLUMN is_approved TINYINT(1) DEFAULT 0 AFTER status";
            $conn->exec($alter_query);
            echo "Successfully added is_approved column to users table.<br>";

            // Update existing users to be approved if they have an active status
            $update_query = "UPDATE users SET is_approved = 1 WHERE status = 'active'";
            $updated_rows = $conn->exec($update_query);
            echo "Updated $updated_rows existing active users to approved status.<br>";

            // Optional: Log the migration
            $log_query = "CREATE TABLE IF NOT EXISTS migration_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration_name VARCHAR(255),
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $conn->exec($log_query);

            $insert_log_query = "INSERT INTO migration_log (migration_name) VALUES ('add_is_approved')";
            $conn->exec($insert_log_query);
        } catch (PDOException $e) {
            echo "Migration Error: " . $e->getMessage() . "<br>";
            error_log("Migration Error: " . $e->getMessage());
        }
    } else {
        echo "is_approved column already exists in users table.<br>";
    }

} catch(PDOException $e) {
    echo "Connection Error: " . $e->getMessage() . "<br>";
    error_log("Connection Error: " . $e->getMessage());
}
?>
