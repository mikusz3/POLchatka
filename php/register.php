<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_error('Niedozwolona metoda', 405, '/register.html');
}

$data = !empty($_POST) ? $_POST : json_input();
$username = sanitize_text($data['username'] ?? '');
$email = sanitize_text($data['email'] ?? '');
$password = $data['password'] ?? '';
$confirm = $data['confirmPassword'] ?? ($data['confirm_password'] ?? '');

if ($username === '' || $email === '' || $password === '' || $confirm === '') {
    respond_error('Brak wymaganych danych', 400, '/register.html');
}

if (mb_strlen($username) < 3) {
    respond_error('Nazwa użytkownika musi mieć co najmniej 3 znaki', 400, '/register.html');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond_error('Nieprawidłowy e-mail', 400, '/register.html');
}

if (mb_strlen($password) < 6) {
    respond_error('Hasło musi mieć co najmniej 6 znaków', 400, '/register.html');
}

if ($password !== $confirm) {
    respond_error('Hasła nie są identyczne', 400, '/register.html');
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $email]);
if ($stmt->fetch()) {
    respond_error('Użytkownik o podanej nazwie lub e-mailu już istnieje', 409, '/register.html');
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
$stmt->execute([$username, $email, $hash]);

$_SESSION['user_id'] = (int)$pdo->lastInsertId();

respond_success(['success' => true, 'user_id' => $_SESSION['user_id']], '/profile.php');
