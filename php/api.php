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
    $method = $_SERVER['REQUEST_METHOD'];
    $endpoint = $_GET['endpoint'] ?? '';

    switch ($endpoint) {
        case 'check_username':
            // Sprawdzanie dostępności nazwy użytkownika
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Metoda nieobsługiwana']);
                exit;
            }

            $username = sanitizeInput($_GET['username'] ?? '');
            
            if (empty($username)) {
                echo json_encode(['success' => false, 'error' => 'Nazwa użytkownika nie może być pusta']);
                exit;
            }

            if (!isValidUsername($username)) {
                echo json_encode(['success' => false, 'available' => false, 'error' => 'Nieprawidłowa nazwa użytkownika']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $exists = $stmt->fetchColumn() > 0;

            echo json_encode([
                'success' => true,
                'available' => !$exists,
                'message' => $exists ? 'Nazwa użytkownika jest zajęta' : 'Nazwa użytkownika jest dostępna'
            ]);
            break;

        case 'check_email':
            // Sprawdzanie dostępności adresu email
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Metoda nieobsługiwana']);
                exit;
            }

            $email = sanitizeInput($_GET['email'] ?? '');
            
            if (empty($email)) {
                echo json_encode(['success' => false, 'error' => 'Adres e-mail nie może być pusty']);
                exit;
            }

            if (!isValidEmail($email)) {
                echo json_encode(['success' => false, 'available' => false, 'error' => 'Nieprawidłowy format adresu e-mail']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $exists = $stmt->fetchColumn() > 0;

            echo json_encode([
                'success' => true,
                'available' => !$exists,
                'message' => $exists ? 'Adres e-mail jest już zarejestrowany' : 'Adres e-mail jest dostępny'
            ]);
            break;

        case 'online_users':
            // Lista użytkowników online
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Metoda nieobsługiwana']);
                exit;
            }

            $stmt = $pdo->query("
                SELECT username, city, avatar, last_login
                FROM users 
                WHERE last_login > DATE_SUB(NOW(), INTERVAL 15 MINUTE) 
                  AND is_active = 1
                ORDER BY last_login DESC
                LIMIT 20
            ");
            $onlineUsers = $stmt->fetchAll();

            echo json_encode(['success' => true, 'users' => $onlineUsers]);
            break;

        case 'recent_posts':
            // Najnowsze posty ze ściany
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Metoda nieobsługiwana']);
                exit;
            }

            $limit = min(intval($_GET['limit'] ?? 10), 50); // Max 50 postów
            
            $stmt = $pdo->prepare("
                SELECT wp.id, wp.content, wp.created_at, 
                       u.username, u.avatar, u.city
                FROM wall_posts wp
                JOIN users u ON wp.user_id = u.id
                WHERE u.is_active = 1 AND u.profile_public = 1
                ORDER BY wp.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $posts = $stmt->fetchAll();

            echo json_encode(['success' => true, 'posts' => $posts]);
            break;

        case 'top_groups':
            // Top grupy według liczby członków
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Metoda nieobsługiwana']);
                exit;
            }

            $stmt = $pdo->query("
                SELECT id, name, description, icon, member_count
                FROM groups
                WHERE is_public = 1
                ORDER BY member_count DESC
                LIMIT 10
            ");
            $groups = $stmt->fetchAll();

            echo json_encode(['success' => true, 'groups' => $groups]);
            break;

        case 'search_users':
            // Wyszukiwanie użytkowników
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Metoda nieobsługiwana']);
                exit;
            }

            $query = sanitizeInput($_GET['q'] ?? '');
            $limit = min(intval($_GET['limit'] ?? 10), 20);
            
            if (strlen($query) < 2) {
                echo json_encode(['success' => false, 'error' => 'Zapytanie musi mieć minimum 2 znaki']);
                exit;
            }

            $searchParam = "%$query%";
            $stmt = $pdo->prepare("
                SELECT username, first_name, last_name, city, avatar
                FROM users
                WHERE (username LIKE ? OR first_name LIKE ? OR last_name LIKE ?)
                  AND is_active = 1 AND profile_public = 1
                ORDER BY 
                  CASE WHEN username LIKE ? THEN 1 ELSE 2 END,
                  username
                LIMIT ?
            ");
            $stmt->execute([$searchParam, $searchParam, $searchParam, "$query%", $limit]);
            $users = $stmt->fetchAll();

            echo json_encode(['success' => true, 'users' => $users]);
            break;

        case 'site_stats':
            // Statystyki strony
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Metoda nieobsługiwana']);
                exit;
            }

            $stats = [];
            
            // Liczba użytkowników
            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
            $stats['total_users'] = $stmt->fetchColumn();
            
            // Użytkownicy online
            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE last_login > DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND is_active = 1");
            $stats['online_users'] = $stmt->fetchColumn();
            
            // Liczba postów
            $stmt = $pdo->query("SELECT COUNT(*) FROM wall_posts");
            $stats['total_posts'] = $stmt->fetchColumn();
            
            // Liczba grup
            $stmt = $pdo->query("SELECT COUNT(*) FROM groups WHERE is_public = 1");
            $stats['total_groups'] = $stmt->fetchColumn();
            
            // Rejestracje dzisiaj
            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");
            $stats['registrations_today'] = $stmt->fetchColumn();

            echo json_encode(['success' => true, 'stats' => $stats]);
            break;

        case 'user_status':
            // Status zalogowania użytkownika
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Metoda nieobsługiwana']);
                exit;
            }

            if (isLoggedIn()) {
                echo json_encode([
                    'success' => true,
                    'logged_in' => true,
                    'user' => [
                        'id' => $_SESSION['user_id'],
                        'username' => $_SESSION['username'],
                        'is_admin' => $_SESSION['is_admin']
                    ]
                ]);
            } else {
                echo json_encode(['success' => true, 'logged_in' => false]);
            }
            break;

        case 'upload_avatar':
            // Upload avatara (w przyszłości)
            requireLogin();
            
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Metoda nieobsługiwana']);
                exit;
            }

            // Placeholder - w przyszłości można dodać upload plików
            echo json_encode(['success' => false, 'error' => 'Funkcja będzie dostępna wkrótce']);
            break;

        case 'report_content':
            // Zgłaszanie nieodpowiednich treści
            requireLogin();
            
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Metoda nieobsługiwana']);
                exit;
            }

            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'error' => 'Nieprawidłowy token CSRF']);
                exit;
            }

            $contentType = sanitizeInput($_POST['content_type'] ?? ''); // 'post', 'user', 'group'
            $contentId = intval($_POST['content_id'] ?? 0);
            $reason = sanitizeInput($_POST['reason'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');

            if (empty($contentType) || !$contentId || empty($reason)) {
                echo json_encode(['success' => false, 'error' => 'Wypełnij wszystkie wymagane pola']);
                exit;
            }

            // Logowanie zgłoszenia
            $details = json_encode([
                'content_type' => $contentType,
                'content_id' => $contentId,
                'reason' => $reason,
                'description' => $description
            ]);
            
            logActivity('content_reported', $details, $_SESSION['user_id']);
            
            echo json_encode(['success' => true, 'message' => 'Zgłoszenie zostało wysłane']);
            break;

        case 'heartbeat':
            // Aktualizacja czasu ostatniej aktywności (heartbeat)
            requireLogin();
            
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Metoda nieobsługiwana']);
                exit;
            }

            // Aktualizuj ostatnią aktywność użytkownika
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);

            echo json_encode(['success' => true, 'timestamp' => time()]);
            break;

        case 'csrf_token':
            // Pobranie tokenu CSRF
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Metoda nieobsługiwana']);
                exit;
            }

            echo json_encode(['success' => true, 'csrf_token' => generateCSRFToken()]);
            break;

        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Nieznany endpoint']);
            break;
    }

} catch (PDOException $e) {
    error_log("Database error in api.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Błąd bazy danych. Spróbuj ponownie później.']);
    logActivity('api_error', "Database error in endpoint $endpoint: " . $e->getMessage(), $_SESSION['user_id'] ?? null);
} catch (Exception $e) {
    error_log("General error in api.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Nieoczekiwany błąd. Spróbuj ponownie.']);
    logActivity('api_error', "General error in endpoint $endpoint: " . $e->getMessage(), $_SESSION['user_id'] ?? null);
}
?>