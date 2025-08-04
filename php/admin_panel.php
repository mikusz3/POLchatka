<?php
require 'config.php';

// Wymaganie logowania i uprawnień administracyjnych
requireLogin();

if (!$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Brak uprawnień administratora']);
    exit;
}

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
    $adminId = $_SESSION['user_id'];

    switch ($method) {
        case 'GET':
            $action = $_GET['action'] ?? 'dashboard';

            switch ($action) {
                case 'dashboard':
                    // Statystyki główne
                    $stats = [];
                    
                    // Liczba użytkowników
                    $stmt = $pdo->query("SELECT COUNT(*) as total, COUNT(CASE WHEN is_active = 1 THEN 1 END) as active FROM users");
                    $userStats = $stmt->fetch();
                    $stats['users'] = $userStats;
                    
                    // Użytkownicy online (logowani w ciągu ostatnich 15 minut)
                    $stmt = $pdo->query("SELECT COUNT(*) as online FROM users WHERE last_login > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
                    $stats['online'] = $stmt->fetchColumn();
                    
                    // Liczba postów
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM wall_posts");
                    $stats['posts'] = $stmt->fetchColumn();
                    
                    // Liczba grup
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM groups");
                    $stats['groups'] = $stmt->fetchColumn();
                    
                    // Rejestracje dzisiaj
                    $stmt = $pdo->query("SELECT COUNT(*) as today FROM users WHERE DATE(created_at) = CURDATE()");
                    $stats['registrations_today'] = $stmt->fetchColumn();
                    
                    // Ostatnie logowania
                    $stmt = $pdo->query("
                        SELECT username, last_login, city 
                        FROM users 
                        WHERE last_login IS NOT NULL 
                        ORDER BY last_login DESC 
                        LIMIT 10
                    ");
                    $stats['recent_logins'] = $stmt->fetchAll();

                    echo json_encode(['success' => true, 'stats' => $stats]);
                    break;

                case 'users':
                    $page = intval($_GET['page'] ?? 1);
                    $limit = 20;
                    $offset = ($page - 1) * $limit;
                    $search = sanitizeInput($_GET['search'] ?? '');

                    $whereClause = '';
                    $params = [];
                    
                    if ($search) {
                        $whereClause = "WHERE username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?";
                        $searchParam = "%$search%";
                        $params = [$searchParam, $searchParam, $searchParam, $searchParam];
                    }

                    // Pobierz użytkowników
                    $stmt = $pdo->prepare("
                        SELECT id, username, email, first_name, last_name, city, 
                               is_active, is_admin, last_login, created_at
                        FROM users 
                        $whereClause
                        ORDER BY created_at DESC 
                        LIMIT $limit OFFSET $offset
                    ");
                    $stmt->execute($params);
                    $users = $stmt->fetchAll();

                    // Policz całkowitą liczbę użytkowników
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereClause");
                    $stmt->execute($params);
                    $totalUsers = $stmt->fetchColumn();

                    echo json_encode([
                        'success' => true,
                        'users' => $users,
                        'pagination' => [
                            'current_page' => $page,
                            'total_pages' => ceil($totalUsers / $limit),
                            'total_users' => $totalUsers
                        ]
                    ]);
                    break;

                case 'user_details':
                    $userId = intval($_GET['user_id'] ?? 0);
                    
                    if (!$userId) {
                        echo json_encode(['success' => false, 'error' => 'Nieprawidłowe ID użytkownika']);
                        exit;
                    }

                    // Pobierz szczegóły użytkownika
                    $stmt = $pdo->prepare("
                        SELECT id, username, email, first_name, last_name, city, birth_year,
                               gender, avatar, profile_public, newsletter, is_active, is_admin,
                               last_login, created_at, updated_at,
                               (SELECT COUNT(*) FROM wall_posts WHERE user_id = ?) as post_count
                        FROM users 
                        WHERE id = ?
                    ");
                    $stmt->execute([$userId, $userId]);
                    $user = $stmt->fetch();

                    if (!$user) {
                        echo json_encode(['success' => false, 'error' => 'Użytkownik nie został znaleziony']);
                        exit;
                    }

                    // Pobierz ostatnie posty użytkownika
                    $stmt = $pdo->prepare("
                        SELECT id, content, created_at 
                        FROM wall_posts 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC 
                        LIMIT 5
                    ");
                    $stmt->execute([$userId]);
                    $user['recent_posts'] = $stmt->fetchAll();

                    // Pobierz logi aktywności użytkownika
                    $stmt = $pdo->prepare("
                        SELECT action, details, ip_address, created_at 
                        FROM activity_logs 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC 
                        LIMIT 10
                    ");
                    $stmt->execute([$userId]);
                    $user['activity_logs'] = $stmt->fetchAll();

                    echo json_encode(['success' => true, 'user' => $user]);
                    break;

                case 'activity_logs':
                    $page = intval($_GET['page'] ?? 1);
                    $limit = 50;
                    $offset = ($page - 1) * $limit;

                    $stmt = $pdo->prepare("
                        SELECT al.*, u.username 
                        FROM activity_logs al
                        LEFT JOIN users u ON al.user_id = u.id
                        ORDER BY al.created_at DESC 
                        LIMIT $limit OFFSET $offset
                    ");
                    $stmt->execute();
                    $logs = $stmt->fetchAll();

                    // Policz całkowitą liczbę logów
                    $stmt = $pdo->query("SELECT COUNT(*) FROM activity_logs");
                    $totalLogs = $stmt->fetchColumn();

                    echo json_encode([
                        'success' => true,
                        'logs' => $logs,
                        'pagination' => [
                            'current_page' => $page,
                            'total_pages' => ceil($totalLogs / $limit),
                            'total_logs' => $totalLogs
                        ]
                    ]);
                    break;

                default:
                    echo json_encode(['success' => false, 'error' => 'Nieznana akcja']);
                    break;
            }
            break;

        case 'POST':
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'error' => 'Nieprawidłowy token CSRF']);
                exit;
            }

            $action = $_POST['action'] ?? '';

            switch ($action) {
                case 'toggle_user_status':
                    $userId = intval($_POST['user_id'] ?? 0);
                    
                    if (!$userId || $userId == $adminId) {
                        echo json_encode(['success' => false, 'error' => 'Nie można zmienić statusu tego użytkownika']);
                        exit;
                    }

                    $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
                    
                    if ($stmt->execute([$userId])) {
                        // Pobierz nowy status
                        $stmt = $pdo->prepare("SELECT is_active, username FROM users WHERE id = ?");
                        $stmt->execute([$userId]);
                        $user = $stmt->fetch();
                        
                        $status = $user['is_active'] ? 'activated' : 'deactivated';
                        logActivity('admin_user_status_changed', "User {$user['username']} $status", $adminId);
                        
                        echo json_encode(['success' => true, 'message' => 'Status użytkownika został zmieniony', 'new_status' => $user['is_active']]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Błąd zmiany statusu użytkownika']);
                    }
                    break;

                case 'toggle_admin_status':
                    $userId = intval($_POST['user_id'] ?? 0);
                    
                    if (!$userId || $userId == $adminId) {
                        echo json_encode(['success' => false, 'error' => 'Nie można zmienić uprawnień tego użytkownika']);
                        exit;
                    }

                    $stmt = $pdo->prepare("UPDATE users SET is_admin = NOT is_admin WHERE id = ?");
                    
                    if ($stmt->execute([$userId])) {
                        // Pobierz nowy status
                        $stmt = $pdo->prepare("SELECT is_admin, username FROM users WHERE id = ?");
                        $stmt->execute([$userId]);
                        $user = $stmt->fetch();
                        
                        $status = $user['is_admin'] ? 'granted' : 'revoked';
                        logActivity('admin_privileges_changed', "Admin privileges $status for {$user['username']}", $adminId);
                        
                        echo json_encode(['success' => true, 'message' => 'Uprawnienia użytkownika zostały zmienione', 'new_admin_status' => $user['is_admin']]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Błąd zmiany uprawnień użytkownika']);
                    }
                    break;

                case 'delete_user':
                    $userId = intval($_POST['user_id'] ?? 0);
                    
                    if (!$userId || $userId == $adminId) {
                        echo json_encode(['success' => false, 'error' => 'Nie można usunąć tego użytkownika']);
                        exit;
                    }

                    // Pobierz dane użytkownika przed usunięciem
                    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();

                    if (!$user) {
                        echo json_encode(['success' => false, 'error' => 'Użytkownik nie został znaleziony']);
                        exit;
                    }

                    // Usuń użytkownika (CASCADE usunie powiązane dane)
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    
                    if ($stmt->execute([$userId])) {
                        logActivity('admin_user_deleted', "User {$user['username']} deleted permanently", $adminId);
                        echo json_encode(['success' => true, 'message' => 'Użytkownik został usunięty']);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Błąd usuwania użytkownika']);
                    }
                    break;

                case 'delete_post':
                    $postId = intval($_POST['post_id'] ?? 0);
                    
                    if (!$postId) {
                        echo json_encode(['success' => false, 'error' => 'Nieprawidłowe ID posta']);
                        exit;
                    }

                    // Pobierz informacje o poście
                    $stmt = $pdo->prepare("SELECT wp.*, u.username FROM wall_posts wp JOIN users u ON wp.user_id = u.id WHERE wp.id = ?");
                    $stmt->execute([$postId]);
                    $post = $stmt->fetch();

                    if (!$post) {
                        echo json_encode(['success' => false, 'error' => 'Post nie został znaleziony']);
                        exit;
                    }

                    $stmt = $pdo->prepare("DELETE FROM wall_posts WHERE id = ?");
                    
                    if ($stmt->execute([$postId])) {
                        logActivity('admin_post_deleted', "Post by {$post['username']} deleted", $adminId);
                        echo json_encode(['success' => true, 'message' => 'Post został usunięty']);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Błąd usuwania posta']);
                    }
                    break;

                default:
                    echo json_encode(['success' => false, 'error' => 'Nieznana akcja']);
                    break;
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Metoda nieobsługiwana']);
            break;
    }

} catch (PDOException $e) {
    error_log("Database error in admin_panel.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Błąd bazy danych. Spróbuj ponownie później.']);
    logActivity('admin_error', "Database error: " . $e->getMessage(), $adminId ?? null);
} catch (Exception $e) {
    error_log("General error in admin_panel.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Nieoczekiwany błąd. Spróbuj ponownie.']);
    logActivity('admin_error', "General error: " . $e->getMessage(), $adminId ?? null);
}
?>