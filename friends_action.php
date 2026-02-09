<?php
/**
 * POLchatka — Akcje znajomych (POST form handler)
 */
require __DIR__ . '/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /php/friends_page.php');
    exit;
}

if (!validate_csrf($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = 'Nieprawidłowy token CSRF';
    header('Location: /php/friends_page.php');
    exit;
}

$uid    = (int)$_SESSION['user_id'];
$action = sanitize_text($_POST['action'] ?? '');

switch ($action) {
    case 'add':
        $toId = (int)($_POST['to_user_id'] ?? 0);
        if ($toId <= 0 || $toId === $uid) {
            $_SESSION['flash_error'] = 'Nieprawidłowy użytkownik';
            break;
        }
        // Sprawdź czy relacja istnieje
        $s = $pdo->prepare("SELECT id FROM friendships WHERE (user_id=? AND friend_id=?) OR (user_id=? AND friend_id=?)");
        $s->execute([$uid, $toId, $toId, $uid]);
        if ($s->fetch()) {
            $_SESSION['flash_error'] = 'Zaproszenie już wysłane lub już jesteście znajomymi';
            break;
        }
        $pdo->prepare("INSERT INTO friendships (user_id, friend_id, status) VALUES (?, ?, 'pending')")->execute([$uid, $toId]);
        $_SESSION['flash_success'] = 'Zaproszenie wysłane! 📨';
        break;

    case 'accept':
        $reqId = (int)($_POST['request_id'] ?? 0);
        $s = $pdo->prepare("SELECT id FROM friendships WHERE id=? AND friend_id=? AND status='pending'");
        $s->execute([$reqId, $uid]);
        if (!$s->fetch()) {
            $_SESSION['flash_error'] = 'Nie znaleziono zaproszenia';
            break;
        }
        $pdo->prepare("UPDATE friendships SET status='accepted', accepted_at=NOW() WHERE id=?")->execute([$reqId]);
        $_SESSION['flash_success'] = 'Znajomy zaakceptowany! 🎉';
        break;

    case 'reject':
        $reqId = (int)($_POST['request_id'] ?? 0);
        $pdo->prepare("DELETE FROM friendships WHERE id=? AND friend_id=? AND status='pending'")->execute([$reqId, $uid]);
        $_SESSION['flash_success'] = 'Zaproszenie odrzucone';
        break;

    default:
        $_SESSION['flash_error'] = 'Nieznana akcja';
}

$redirect = $_POST['redirect'] ?? '/php/friends_page.php';
header('Location: ' . $redirect);
exit;
