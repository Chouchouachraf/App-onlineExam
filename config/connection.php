<?php
$host = 'localhost';
$dbname = 'exammaster';
$user = 'exammaster';
$password = 'exammaster';

try {
    $conn  = new PDO("mysql:host=$host;port=3306;dbname=$dbname", $user, $password);
    $conn ->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>