<?php
require_once '../config/connection.php';

try {
    // Check exams table
    $stmt = $conn->query("DESCRIBE exams");
    echo "<h3>Exams Table Structure:</h3>";
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";

    // Count exams
    $stmt = $conn->query("SELECT COUNT(*) as count FROM exams");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total exams: " . $count['count'] . "</p>";

    // Show some exam data
    $stmt = $conn->query("SELECT id, title, created_by, class_id, start_date, end_date FROM exams LIMIT 5");
    echo "<h3>Sample Exams:</h3>";
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
