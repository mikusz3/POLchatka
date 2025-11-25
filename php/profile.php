<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/config.php';
require_once __DIR__ . '/../templates/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

header('Content-Type: text/html; charset=utf-8', true);

$userId = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT username, email, created_at, avatar FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$avatar = trim((string)($user['avatar'] ?? ''));
$registeredAt = $user && !empty($user['created_at'])
    ? date('Y-m-d H:i', strtotime($user['created_at']))
    : 'Nieznana';
?>
<?php render_page_start('Twój profil | POLchatka'); ?>
<div class="content-card">
    <h1 class="section-title">Twój profil</h1>
    <?php if ($user): ?>
        <section class="profile-card">
            <div class="avatar"><?php echo $avatar !== '' ? escape($avatar) : '👤'; ?></div>
            <div class="details">
                <p><strong>Login:</strong> <?php echo escape($user['username']); ?></p>
                <p><strong>Email:</strong> <?php echo escape($user['email']); ?></p>
                <p><strong>Data rejestracji:</strong> <?php echo escape($registeredAt); ?></p>
            </div>
        </section>
    <?php else: ?>
        <div class="empty-state">
            <p>Nie udało się pobrać danych profilu.</p>
            <p><a href="login.php">Przejdź do logowania</a></p>
        </div>
    <?php endif; ?>
</div>
<?php render_page_end(); ?>
