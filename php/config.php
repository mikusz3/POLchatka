<?php
$host = 'localhost';
$dbname = 'polchatka_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
} catch (PDOException $e) {
    die("Połączenie nieudane: " . $e->getMessage());
}
?>