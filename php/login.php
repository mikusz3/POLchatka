<?php
require 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Funkcja logowania aktywności
function logActivity($action, $details = null, $user_id = null) {
    global $pdo;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $details, $ip, $user_agent]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metoda nieobsługiwana']);
    exit;
}

try {
    // Pobranie danych z formularza
    $username = sanitizeInput($_POST["username"] ?? '');
    $password = $_POST["password"] ?? '';
    $remember = isset($_POST["remember"]);

    // Walidacja podstawowa
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Wypełnij wszystkie pola']);
        logActivity('login_failed', "Empty credentials for username: $username");
        exit;
    }

    // Sprawdzenie czy użytkownik istnieje i jest aktywny
    $stmt = $pdo->prepare("
        SELECT id, username, password, email, first_name, last_name, is_active, is_admin, last_login 
        FROM users 
        WHERE username = ? AND is_active = 1
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'Nieprawidłowy login lub hasło']);
        logActivity('login_failed', "User not found or inactive: $username");
        // Dodaj krótkie opóźnienie żeby utrudnić ataki brute force
        sleep(1);
        exit;
    }

    // Weryfikacja hasła
    if (!password_verify($password, $user["password"])) {
        echo json_encode(['success' => false, 'error' => 'Nieprawidłowy login lub hasło']);
        logActivity('login_failed', "Wrong password for user: $username", $user['id']);
        // Dodaj krótkie opóźnienie żeby utrudnić ataki brute force
        sleep(1);
        exit;
    }

    // Regeneracja ID sesji dla bezpieczeństwa
    session_regenerate_id(true);

    // Ustawienie zmiennych sesyjnych
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['is_admin'] = (bool)$user['is_admin'];
    $_SESSION['login_time'] = time();
    $_SESSION['csrf_token'] = generateSecureToken();

    // Aktualizacja ostatniego logowania
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);

    // Obsługa opcji "Zapamiętaj mnie"
    if ($remember) {
        // Utwórz bezpieczny token dla ciasteczka
        $rememberToken = generateSecureToken(64);
        $expires = time() + (30 * 24 * 60 * 60); // 30 dni
        
        // Zapisz token w bazie danych
        $stmt = $pdo->prepare("
            INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, FROM_UNIXTIME(?))
            ON DUPLICATE KEY UPDATE 
            session_token = VALUES(session_token), 
            expires_at = VALUES(expires_at)
        ");
        $stmt->execute([
            $user['id'], 
            $rememberToken, 
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            $expires
        ]);
        
        // Ustaw ciasteczko (w produkcji użyj secure=true dla HTTPS)
        setcookie('remember_token', $rememberToken, $expires, '/', '', false, true);
    }

    // Logowanie udanego logowania
    logActivity('login_success', "Successful login", $user['id']);

    // Przygotowanie odpowiedzi
    $response = [
        'success' => true,
        'message' => 'Zalogowano pomyślnie!',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'is_admin' => (bool)$user['is_admin']
        ]
    ];

    // Przekierowanie w zależności od roli
    if ($user['is_admin']) {
        $response['redirect'] = '../content/admin_panel.html';
    } else {
        $response['redirect'] = '../content/profile.html';
    }

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Database error in session_login.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Błąd bazy danych. Spróbuj ponownie później.']);
    logActivity('login_error', "Database error: " . $e->getMessage());
} catch (Exception $e) {
    error_log("General error in session_login.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Nieoczekiwany błąd. Spróbuj ponownie.']);
    logActivity('login_error', "General error: " . $e->getMessage());
}
?>