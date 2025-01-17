<?php
$host = 'localhost';
$dbname = 'exammaster';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Modify the question_type column to accept our values
    $sql = "ALTER TABLE questions MODIFY COLUMN question_type ENUM('mcq', 'true_false', 'open') NOT NULL";
    $conn->exec($sql);
    echo "Successfully modified question_type column in questions table";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
