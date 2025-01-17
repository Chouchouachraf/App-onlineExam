<?php
$host = 'localhost';
$dbname = 'exammaster';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if column exists
    $stmt = $conn->query("SHOW COLUMNS FROM questions LIKE 'image_path'");
    $column_exists = $stmt->fetch();

    if (!$column_exists) {
        // Add image_path column if it doesn't exist
        $sql = "ALTER TABLE questions ADD COLUMN image_path VARCHAR(255) NULL AFTER points";
        $conn->exec($sql);
        echo "Successfully added image_path column to questions table";
    } else {
        echo "image_path column already exists";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
