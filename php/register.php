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
    // Pobranie i walidacja danych
    $username = sanitizeInput($_POST["username"] ?? '');
    $email = sanitizeInput($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';
    $confirmPassword = $_POST["confirmPassword"] ?? '';
    $firstName = sanitizeInput($_POST["firstName"] ?? '');
    $lastName = sanitizeInput($_POST["lastName"] ?? '');
    $city = sanitizeInput($_POST["city"] ?? '');
    $birthYear = !empty($_POST["birthYear"]) ? intval($_POST["birthYear"]) : null;
    $gender = in_array($_POST["gender"] ?? '', ['M', 'K']) ? $_POST["gender"] : null;
    $avatar = sanitizeInput($_POST["avatar"] ?? '👤');
    $profilePublic = isset($_POST["profile_public"]);
    $newsletter = isset($_POST["newsletter"]);
    $terms = isset($_POST["terms"]);
    $ageConfirm = isset($_POST["age_confirm"]);

    // Walidacja wymaganych pól
    $errors = [];

    if (empty($username)) {
        $errors[] = 'Nazwa użytkownika jest wymagana';
    } elseif (!isValidUsername($username)) {
        $errors[] = 'Nieprawidłowa nazwa użytkownika (3-30 znaków, tylko litery, cyfry i podkreślnik)';
    }

    if (empty($email)) {
        $errors[] = 'Adres e-mail jest wymagany';
    } elseif (!isValidEmail($email)) {
        $errors[] = 'Nieprawidłowy format adresu e-mail';
    }

    if (empty($password)) {
        $errors[] = 'Hasło jest wymagane';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Hasło musi mieć minimum 6 znaków';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Hasła nie są identyczne';
    }

    if (!$terms) {
        $errors[] = 'Musisz zaakceptować regulamin';
    }

    if (!$ageConfirm) {
        $errors[] = 'Musisz potwierdzić, że masz ukończone 13 lat';
    }

    // Walidacja roku urodzenia
    if ($birthYear !== null) {
        $currentYear = date('Y');
        if ($birthYear < ($currentYear - 80) || $birthYear > ($currentYear - 13)) {
            $errors[] = 'Nieprawidłowy rok urodzenia';
        }
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    // Sprawdzenie czy nazwa użytkownika już istnieje
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'errors' => ['Nazwa użytkownika jest już zajęta']]);
        logActivity('register_failed', "Username already exists: $username");
        exit;
    }

    // Sprawdzenie czy email już istnieje
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'errors' => ['Adres e-mail jest już zarejestrowany']]);
        logActivity('register_failed', "Email already exists: $email");
        exit;
    }

    // Hashowanie hasła
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Wstawienie nowego użytkownika
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, first_name, last_name, city, birth_year, gender, avatar, profile_public, newsletter) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $username, 
        $email, 
        $passwordHash, 
        $firstName ?: null, 
        $lastName ?: null, 
        $city ?: null, 
        $birthYear, 
        $gender, 
        $avatar, 
        $profilePublic,
        $newsletter
    ]);

    if ($result) {
        $userId = $pdo->lastInsertId();
        
        // Logowanie aktywności
        logActivity('register_success', "New user registered: $username", $userId);
        
        // Automatyczne logowanie po rejestracji
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['is_admin'] = false;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Rejestracja zakończona sukcesem! Zostałeś automatycznie zalogowany.',
            'redirect' => '../content/profile.html'
        ]);
    } else {
        echo json_encode(['success' => false, 'errors' => ['Błąd przy tworzeniu konta. Spróbuj ponownie.']]);
        logActivity('register_failed', "Database error for username: $username");
    }

} catch (PDOException $e) {
    error_log("Database error in register.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'errors' => ['Błąd bazy danych. Spróbuj ponownie później.']]);
    logActivity('register_error', "Database error: " . $e->getMessage());
} catch (Exception $e) {
    error_log("General error in register.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'errors' => ['Nieoczekiwany błąd. Spróbuj ponownie.']]);
    logActivity('register_error', "General error: " . $e->getMessage());
}
?>