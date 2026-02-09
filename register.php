<?php
/**
 * POLchatka — Rejestracja (backend)
 * Obsługuje POST z formularza lub JSON API.
 */
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_error('Niedozwolona metoda', 405, '/php/register_page.php');
}

$data     = !empty($_POST) ? $_POST : json_input();
$username = sanitize_text($data['username'] ?? '');
$email    = sanitize_text($data['email'] ?? '');
$password = $data['password'] ?? '';
$confirm  = $data['confirmPassword'] ?? ($data['confirm_password'] ?? '');

// Walidacja
if ($username === '' || $email === '' || $password === '' || $confirm === '') {
    respond_error('Wypełnij wszystkie pola', 400, '/php/register_page.php');
}
if (mb_strlen($username) < 3 || mb_strlen($username) > 30) {
    respond_error('Nazwa użytkownika: 3-30 znaków', 400, '/php/register_page.php');
}
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    respond_error('Nazwa użytkownika może zawierać tylko litery, cyfry i _', 400, '/php/register_page.php');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond_error('Nieprawidłowy adres e-mail', 400, '/php/register_page.php');
}
if (mb_strlen($password) < 6) {
    respond_error('Hasło musi mieć co najmniej 6 znaków', 400, '/php/register_page.php');
}
if ($password !== $confirm) {
    respond_error('Hasła nie są identyczne', 400, '/php/register_page.php');
}

// Sprawdź czy istnieje
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $email]);
if ($stmt->fetch()) {
    respond_error('Użytkownik o podanej nazwie lub e-mailu już istnieje', 409, '/php/register_page.php');
}

// Utwórz konto
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
$stmt->execute([$username, $email, $hash]);

$userId = (int)$pdo->lastInsertId();
$_SESSION['user_id']  = $userId;
$_SESSION['username'] = $username;

respond_success(['success' => true, 'user_id' => $userId, 'message' => 'Konto utworzone!'], '/php/profile.php');
