<?php
require 'config.php';

// Wymaganie logowania
requireLogin();

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
    $userId = $_SESSION['user_id'];

    switch ($method) {
        case 'GET':
            // Pobieranie danych profilu
            $targetUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : $userId;
            
            $stmt = $pdo->prepare("
                SELECT id, username, email, first_name, last_name, city, birth_year, 
                       gender, avatar, profile_public, last_login, created_at,
                       (SELECT COUNT(*) FROM wall_posts WHERE user_id = ?) as post_count
                FROM users 
                WHERE id = ? AND is_active = 1
            ");
            $stmt->execute([$targetUserId, $targetUserId]);
            $profile = $stmt->fetch();

            if (!$profile) {
                echo json_encode(['success' => false, 'error' => 'Profil nie został znaleziony']);
                exit;
            }

            // Sprawdź czy profil jest publiczny lub to własny profil
            if (!$profile['profile_public'] && $targetUserId != $userId && !$_SESSION['is_admin']) {
                echo json_encode(['success' => false, 'error' => 'Profil jest prywatny']);
                exit;
            }

            // Ukryj wrażliwe dane jeśli to nie własny profil
            if ($targetUserId != $userId && !$_SESSION['is_admin']) {
                unset($profile['email']);
            }

            // Pobierz ostatnie posty użytkownika
            $stmt = $pdo->prepare("
                SELECT id, content, created_at, updated_at 
                FROM wall_posts 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute([$targetUserId]);
            $posts = $stmt->fetchAll();

            $profile['posts'] = $posts;
            $profile['is_own_profile'] = ($targetUserId == $userId);

            echo json_encode(['success' => true, 'profile' => $profile]);
            break;

        case 'POST':
            // Aktualizacja profilu
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'error' => 'Nieprawidłowy token CSRF']);
                exit;
            }

            $action = $_POST['action'] ?? '';

            switch ($action) {
                case 'update_profile':
                    $firstName = sanitizeInput($_POST['first_name'] ?? '');
                    $lastName = sanitizeInput($_POST['last_name'] ?? '');
                    $city = sanitizeInput($_POST['city'] ?? '');
                    $birthYear = !empty($_POST['birth_year']) ? intval($_POST['birth_year']) : null;
                    $gender = in_array($_POST['gender'] ?? '', ['M', 'K']) ? $_POST['gender'] : null;
                    $avatar = sanitizeInput($_POST['avatar'] ?? '👤');
                    $profilePublic = isset($_POST['profile_public']);

                    // Walidacja roku urodzenia
                    if ($birthYear !== null) {
                        $currentYear = date('Y');
                        if ($birthYear < ($currentYear - 80) || $birthYear > ($currentYear - 13)) {
                            echo json_encode(['success' => false, 'error' => 'Nieprawidłowy rok urodzenia']);
                            exit;
                        }
                    }

                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET first_name = ?, last_name = ?, city = ?, birth_year = ?, 
                            gender = ?, avatar = ?, profile_public = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    
                    $result = $stmt->execute([
                        $firstName ?: null,
                        $lastName ?: null, 
                        $city ?: null,
                        $birthYear,
                        $gender,
                        $avatar,
                        $profilePublic,
                        $userId
                    ]);

                    if ($result) {
                        logActivity('profile_updated', 'Profile information updated', $userId);
                        echo json_encode(['success' => true, 'message' => 'Profil został zaktualizowany']);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Błąd aktualizacji profilu']);
                    }
                    break;

                case 'change_password':
                    $currentPassword = $_POST['current_password'] ?? '';
                    $newPassword = $_POST['new_password'] ?? '';
                    $confirmPassword = $_POST['confirm_password'] ?? '';

                    // Walidacja
                    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                        echo json_encode(['success' => false, 'error' => 'Wypełnij wszystkie pola']);
                        exit;
                    }

                    if ($newPassword !== $confirmPassword) {
                        echo json_encode(['success' => false, 'error' => 'Nowe hasła nie są identyczne']);
                        exit;
                    }

                    if (strlen($newPassword) < 6) {
                        echo json_encode(['success' => false, 'error' => 'Nowe hasło musi mieć minimum 6 znaków']);
                        exit;
                    }

                    // Sprawdź obecne hasło
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();

                    if (!password_verify($currentPassword, $user['password'])) {
                        echo json_encode(['success' => false, 'error' => 'Nieprawidłowe obecne hasło']);
                        logActivity('password_change_failed', 'Wrong current password', $userId);
                        exit;
                    }

                    // Aktualizuj hasło
                    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                    
                    if ($stmt->execute([$newPasswordHash, $userId])) {
                        logActivity('password_changed', 'Password successfully changed', $userId);
                        echo json_encode(['success' => true, 'message' => 'Hasło zostało zmienione']);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Błąd zmiany hasła']);
                    }
                    break;

                case 'add_wall_post':
                    $content = trim($_POST['content'] ?? '');
                    
                    if (empty($content)) {
                        echo json_encode(['success' => false, 'error' => 'Treść posta nie może być pusta']);
                        exit;
                    }

                    if (strlen($content) > 1000) {
                        echo json_encode(['success' => false, 'error' => 'Post jest za długi (max 1000 znaków)']);
                        exit;
                    }

                    $stmt = $pdo->prepare("INSERT INTO wall_posts (user_id, content) VALUES (?, ?)");
                    
                    if ($stmt->execute([$userId, $content])) {
                        $postId = $pdo->lastInsertId();
                        logActivity('wall_post_added', "Post ID: $postId", $userId);
                        echo json_encode(['success' => true, 'message' => 'Post został dodany', 'post_id' => $postId]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Błąd dodawania posta']);
                    }
                    break;

                case 'delete_wall_post':
                    $postId = intval($_POST['post_id'] ?? 0);
                    
                    // Sprawdź czy post należy do użytkownika lub czy użytkownik jest adminem
                    $stmt = $pdo->prepare("SELECT user_id FROM wall_posts WHERE id = ?");
                    $stmt->execute([$postId]);
                    $post = $stmt->fetch();

                    if (!$post) {
                        echo json_encode(['success' => false, 'error' => 'Post nie został znaleziony']);
                        exit;
                    }

                    if ($post['user_id'] != $userId && !$_SESSION['is_admin']) {
                        echo json_encode(['success' => false, 'error' => 'Brak uprawnień do usunięcia tego posta']);
                        exit;
                    }

                    $stmt = $pdo->prepare("DELETE FROM wall_posts WHERE id = ?");
                    
                    if ($stmt->execute([$postId])) {
                        logActivity('wall_post_deleted', "Post ID: $postId", $userId);
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

        case 'DELETE':
            // Usunięcie konta (dezaktywacja)
            if (!validateCSRFToken($_GET['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'error' => 'Nieprawidłowy token CSRF']);
                exit;
            }

            // Dezaktywuj konto zamiast usuwać (soft delete)
            $stmt = $pdo->prepare("UPDATE users SET is_active = 0, updated_at = NOW() WHERE id = ?");
            
            if ($stmt->execute([$userId])) {
                // Usuń sesje użytkownika
                $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
                $stmt->execute([$userId]);
                
                logActivity('account_deactivated', 'User deactivated their account', $userId);
                
                // Zniszcz sesję
                session_destroy();
                
                echo json_encode(['success' => true, 'message' => 'Konto zostało dezaktywowane']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Błąd dezaktywacji konta']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Metoda nieobsługiwana']);
            break;
    }

} catch (PDOException $e) {
    error_log("Database error in profile.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Błąd bazy danych. Spróbuj ponownie później.']);
    logActivity('profile_error', "Database error: " . $e->getMessage(), $userId ?? null);
} catch (Exception $e) {
    error_log("General error in profile.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Nieoczekiwany błąd. Spróbuj ponownie.']);
    logActivity('profile_error', "General error: " . $e->getMessage(), $userId ?? null);
}
?>
                    