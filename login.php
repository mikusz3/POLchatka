<?php
/**
 * POLchatka — Logowanie (backend)
 */
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_error('Niedozwolona metoda', 405, '/php/login_page.php');
}

$data       = !empty($_POST) ? $_POST : json_input();
$identifier = sanitize_text($data['username'] ?? ($data['email'] ?? ''));
$password   = $data['password'] ?? '';

if ($identifier === '' || $password === '') {
    respond_error('Wypełnij login i hasło', 400, '/php/login_page.php');
}

$stmt = $pdo->prepare("SELECT id, username, password_hash, is_banned, is_admin FROM users WHERE username = ? OR email = ? LIMIT 1");
$stmt->execute([$identifier, $identifier]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    respond_error('Nieprawidłowy login lub hasło', 401, '/php/login_page.php');
}

if (intval($user['is_banned']) === 1) {
    respond_error('Konto zostało zablokowane przez administrację', 403, '/php/login_page.php');
}

// Ustaw sesję
$_SESSION['user_id']  = (int)$user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['is_admin'] = (bool)$user['is_admin'];

// Aktualizuj last_login (jeśli kolumna istnieje)
try {
    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
} catch (PDOException $e) {
    // kolumna może nie istnieć — ignoruj
}

respond_success([
    'success'  => true,
    'message'  => 'Zalogowano pomyślnie!',
    'user'     => ['id' => (int)$user['id'], 'username' => $user['username']],
], '/php/wall.php');
