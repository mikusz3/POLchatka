<?php
require 'config.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    die("Musisz być zalogowany, aby edytować profil.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = $_POST["username"];
    $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
    if ($stmt->execute([$new_username, $_SESSION["user_id"]])) {
        $_SESSION["username"] = $new_username;
        echo "Nazwa użytkownika zaktualizowana.";
    } else {
        echo "Błąd aktualizacji.";
    }
}
?>