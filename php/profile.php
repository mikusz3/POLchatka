<?php
require 'config.php';

// Wymaganie logowania
requireLogin();

header('Content-Type: application/json; charset=utf-8');

// Logowanie aktywności
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

            if (!$profile['profile_public'] && $targetUserId != $userId && !$_SESSION['is_admin']) {
                echo json_encode(['success' => false, 'error' => 'Profil jest prywatny']);
                exit;
            }

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

                    $currentYear = date('Y');
                    if ($birthYear !== null && ($birthYear < ($currentYear - 80) || $birthYear > ($currentYear - 13))) {
                        echo json_encode(['success' => false, 'error' => 'Nieprawidłowy rok urodzenia']);
                        exit;
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

                    if (strlen($newPassword) < 6) {
                        echo json_encode(['success' => false, 'error' => 'Nowe hasło jest za krótkie']);
                        exit;
                    }

                    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();

                    if (!$user || !password_verify($currentPassword, $user['password'])) {
                        echo json_encode(['success' => false, 'error' => 'Nieprawidłowe aktualne hasło']);
                        exit;
                    }

                    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                    $result = $stmt->execute([$newPasswordHash, $userId]);

                    if ($result) {
                        logActivity('password_changed', 'User changed password', $userId);
                        echo json_encode(['success' => true, 'message' => 'Hasło zostało zmienione']);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Błąd podczas zmiany hasła']);
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

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Wystąpił błąd serwera']);
}
?>
