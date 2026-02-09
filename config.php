<?php
/**
 * POLchatka — konfiguracja i helpery
 * Wersja Beta 0.9
 */

// Sesja
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly'  => true,
        'samesite'  => 'Lax',
    ]);
    session_start();
}

// Nagłówki bezpieczeństwa (nadpisywane przez strony HTML)
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

// CORS (dev)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(200);
    exit;
}

// Baza danych
$DB_HOST = getenv('POLCHATKA_DB_HOST') ?: 'localhost';
$DB_NAME = getenv('POLCHATKA_DB_NAME') ?: 'polchatka_db';
$DB_USER = getenv('POLCHATKA_DB_USER') ?: 'root';
$DB_PASS = getenv('POLCHATKA_DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Błąd połączenia z bazą danych']);
    exit;
}

// ---------- Helpery ----------

function json_input(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $d = json_decode($raw, true);
    return is_array($d) ? $d : [];
}

function is_json_request(): bool
{
    return isset($_SERVER['CONTENT_TYPE'])
        && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false;
}

function sanitize_text(?string $s): string
{
    $s = trim($s ?? '');
    return preg_replace('/[\x00-\x1F\x7F]/u', '', $s);
}

function append_query(string $location, array $params): string
{
    $query = http_build_query($params);
    if ($query === '') return $location;
    return $location . (str_contains($location, '?') ? '&' : '?') . $query;
}

function flash_message(string $key, string $message): void
{
    $_SESSION[$key] = $message;
}

function respond_error(string $message, int $status = 400, ?string $redirect = null, array $extra = []): void
{
    if ($redirect !== null && !is_json_request()) {
        flash_message('flash_error', $message);
        header('Location: ' . append_query($redirect, ['error' => $message]));
        exit;
    }
    http_response_code($status);
    echo json_encode(array_merge(['error' => $message], $extra));
    exit;
}

function respond_success(array $payload = [], ?string $redirect = null): void
{
    if ($redirect !== null && !is_json_request()) {
        flash_message('flash_success', $payload['message'] ?? 'OK');
        header('Location: ' . append_query($redirect, ['success' => $payload['message'] ?? '1']));
        exit;
    }
    echo json_encode($payload === [] ? ['success' => true] : $payload);
    exit;
}

function require_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        if (is_json_request()) {
            http_response_code(401);
            echo json_encode(['error' => 'Nie zalogowano']);
            exit;
        }
        header('Location: /php/login_page.php');
        exit;
    }
}

function require_admin(PDO $pdo): void
{
    require_login();
    $s = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $s->execute([$_SESSION['user_id']]);
    $r = $s->fetch();
    if (!$r || intval($r['is_admin']) !== 1) {
        http_response_code(403);
        echo json_encode(['error' => 'Brak uprawnień']);
        exit;
    }
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
