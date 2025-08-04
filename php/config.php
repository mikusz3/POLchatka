<?php
// Konfiguracja bazy danych
$host = 'localhost';
$dbname = 'polchatka_db';
$username = 'root';
$password = '';

// Opcje PDO dla lepszego bezpieczeństwa
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    // Ustawienie charset na utf8mb4 dla pełnego wsparcia Unicode
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_polish_ci"
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
} catch (PDOException $e) {
    // W produkcji nie pokazuj szczegółów błędu
    error_log("Database connection failed: " . $e->getMessage());
    die("Błąd połączenia z bazą danych. Spróbuj ponownie później.");
}

// Funkcje pomocnicze
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidUsername($username) {
    // Nazwa użytkownika: 3-30 znaków, tylko litery, cyfry i podkreślnik
    return preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username);
}

function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Konfiguracja sesji
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Ustaw na 1 jeśli używasz HTTPS

// Rozpocznij sesję jeśli nie jest aktywna
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Funkcja sprawdzająca czy użytkownik jest zalogowany
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Funkcja wymagająca logowania
function requireLogin($redirectTo = '../content/login.html') {
    if (!isLoggedIn()) {
        header("Location: $redirectTo");
        exit();
    }
}

// Ochrona przed CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateSecureToken();
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>