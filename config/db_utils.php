<?php
function columnExists($conn, $table, $column) {
    try {
        $stmt = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        return $stmt->fetch() !== false;
    } catch(PDOException $e) {
        error_log("Error checking column existence: " . $e->getMessage());
        return false;
    }
}
?>
