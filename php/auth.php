<?php
// ============================================
// POLchatka - API: Autoryzacja
// POST /php/auth.php?action=register|login|logout|delete_account
// ============================================

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'register':
        handleRegister();
        break;
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'delete_account':
        handleDeleteAccount();
        break;
    case 'me':
        handleMe();
        break;
    default:
        jsonResponse(['success' => false, 'error' => 'Nieznana akcja'], 400);
}

// ============================================
// REJESTRACJA
// ============================================
function handleRegister(): void {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $birthYear = (int)($_POST['birth_year'] ?? 0);
    $gender = $_POST['gender'] ?? '';
    $avatar = $_POST['avatar'] ?? '👤';

    // Walidacja
    if (strlen($username) < 3 || strlen($username) > 30) {
        jsonResponse(['success' => false, 'error' => 'Nazwa użytkownika musi mieć 3-30 znaków']);
    }
    if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
        jsonResponse(['success' => false, 'error' => 'Nazwa użytkownika może zawierać tylko litery, cyfry, _, ., -']);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'error' => 'Nieprawidłowy adres email']);
    }
    if (strlen($password) < 6) {
        jsonResponse(['success' => false, 'error' => 'Hasło musi mieć co najmniej 6 znaków']);
    }
    if (!in_array($gender, ['M', 'K', ''])) {
        $gender = null;
    }
    if ($birthYear && ($birthYear < 1900 || $birthYear > date('Y'))) {
        jsonResponse(['success' => false, 'error' => 'Nieprawidłowy rok urodzenia']);
    }

    $db = getDB();

    // Sprawdź czy username/email już istnieje
    $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'error' => 'Nazwa użytkownika lub email jest już zajęty']);
    }

    // Utwórz konto
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare('
        INSERT INTO users (username, email, password, first_name, last_name, city, birth_year, gender, avatar) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $username, $email, $hash,
        $firstName ?: null, $lastName ?: null,
        $city ?: null, $birthYear ?: null,
        $gender ?: null, $avatar
    ]);
    $userId = $db->lastInsertId();

    // Auto-login po rejestracji
    $_SESSION['user_id'] = (int)$userId;
    $_SESSION['username'] = $username;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    jsonResponse(['success' => true, 'message' => 'Konto utworzone!', 'username' => $username]);
}

// ============================================
// LOGOWANIE
// ============================================
function handleLogin(): void {
    $login = sanitize($_POST['login'] ?? ''); // username lub email
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        jsonResponse(['success' => false, 'error' => 'Podaj login i hasło']);
    }

    $db = getDB();
    $stmt = $db->prepare('SELECT id, username, password, is_active FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        jsonResponse(['success' => false, 'error' => 'Nieprawidłowy login lub hasło']);
    }
    if (!$user['is_active']) {
        jsonResponse(['success' => false, 'error' => 'Konto jest zablokowane']);
    }

    // Aktualizuj last_login
    $db->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    jsonResponse(['success' => true, 'message' => 'Zalogowano!', 'username' => $user['username']]);
}

// ============================================
// WYLOGOWANIE
// ============================================
function handleLogout(): void {
    session_destroy();
    jsonResponse(['success' => true, 'message' => 'Wylogowano']);
}

// ============================================
// USUNIĘCIE KONTA
// ============================================
function handleDeleteAccount(): void {
    $userId = requireAuth();
    checkCSRF();

    $password = $_POST['password'] ?? '';

    $db = getDB();
    $stmt = $db->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!password_verify($password, $user['password'])) {
        jsonResponse(['success' => false, 'error' => 'Nieprawidłowe hasło']);
    }

    // Usuń konto (CASCADE usuwa znajomych, wiadomości, posty)
    $db->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
    session_destroy();

    jsonResponse(['success' => true, 'message' => 'Konto zostało usunięte']);
}

// ============================================
// DANE ZALOGOWANEGO UŻYTKOWNIKA
// ============================================
function handleMe(): void {
    $userId = getCurrentUserId();
    if (!$userId) {
        jsonResponse(['success' => false, 'logged_in' => false]);
    }

    $db = getDB();
    $stmt = $db->prepare('
        SELECT id, username, email, first_name, last_name, city, birth_year, gender, avatar, bio, created_at,
               (SELECT COUNT(*) FROM friendships WHERE (sender_id = u.id OR receiver_id = u.id) AND status = "accepted") as friends_count,
               (SELECT COUNT(*) FROM messages WHERE receiver_id = u.id AND is_read = 0) as unread_messages,
               (SELECT COUNT(*) FROM notifications WHERE user_id = u.id AND is_read = 0) as unread_notifications
        FROM users u WHERE u.id = ?
    ');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    jsonResponse([
        'success' => true,
        'logged_in' => true,
        'user' => $user,
        'csrf_token' => getCSRFToken()
    ]);
}
