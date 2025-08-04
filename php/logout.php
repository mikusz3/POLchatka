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

try {
    $userId = $_SESSION['user_id'] ?? null;
    $username = $_SESSION['username'] ?? 'unknown';

    // Usuń tokeny "zapamiętaj mnie" z bazy danych
    if ($userId) {
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Logowanie wylogowania
        logActivity('logout', "User logged out", $userId);
    }

    // Usuń ciasteczko "zapamiętaj mnie"
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }

    // Zniszcz wszystkie dane sesji
    $_SESSION = array();

    // Zniszcz ciasteczko sesji
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Zniszcz sesję
    session_destroy();

    echo json_encode([
        'success' => true, 
        'message' => 'Zostałeś pomyślnie wylogowany',
        'redirect' => '../content/index.html'
    ]);

} catch (Exception $e) {
    error_log("Error in logout.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Błąd podczas wylogowywania',
        'redirect' => '../content/index.html' // Przekieruj mimo błędu
    ]);
}
?>