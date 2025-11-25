<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_error('Niedozwolona metoda', 405, '/login.html');
}

$data = !empty($_POST) ? $_POST : json_input();
$identifier = sanitize_text($data['username'] ?? ($data['email'] ?? ''));
$password = $data['password'] ?? '';

if ($identifier === '' || $password === '') {
    respond_error('Brak danych logowania', 400, '/login.html');
}

$stmt = $pdo->prepare("SELECT id, username, password_hash, is_banned FROM users WHERE username = ? OR email = ? LIMIT 1");
$stmt->execute([$identifier, $identifier]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    respond_error('Nieprawidłowy login lub hasło', 401, '/login.html');
}

if (intval($user['is_banned']) === 1) {
    respond_error('Konto zostało zablokowane', 403, '/login.html');
}

$_SESSION['user_id'] = (int)$user['id'];

respond_success([
    'success' => true,
    'user' => [
        'id' => (int)$user['id'],
        'username' => $user['username'],
    ],
], '/profile.php');
