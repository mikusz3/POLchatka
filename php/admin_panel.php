<?php
require 'config.php';
session_start();

// Zakładamy, że użytkownik o ID 1 to administrator
if (!isset($_SESSION["user_id"]) || $_SESSION["user_id"] != 1) {
    die("Brak dostępu. Tylko administrator może tutaj wejść.");
}

// Pobieramy listę użytkowników
$stmt = $pdo->query("SELECT id, username FROM users");
$users = $stmt->fetchAll();
?>

<h2>Panel Administratora POLchatki</h2>

<table border="1">
    <tr><th>ID</th><th>Nazwa użytkownika</th></tr>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user["id"]) ?></td>
            <td><?= htmlspecialchars($user["username"]) ?></td>
        </tr>
    <?php endforeach; ?>
</table>
