<?php
/**
 * POLchatka — Profil użytkownika
 * Wyświetla profil zalogowanego lub innego użytkownika (?id=X)
 */
require __DIR__ . '/config.php';
require_once __DIR__ . '/../templates/header.php';

header('Content-Type: text/html; charset=utf-8', true);

$loggedIn  = isset($_SESSION['user_id']);
$myId      = $loggedIn ? (int)$_SESSION['user_id'] : 0;
$viewingId = isset($_GET['id']) ? (int)$_GET['id'] : $myId;

if ($viewingId <= 0) {
    header('Location: /php/login_page.php');
    exit;
}

$isOwnProfile = ($viewingId === $myId);

// Pobierz dane użytkownika
$stmt = $pdo->prepare('SELECT id, username, email, avatar, created_at FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$viewingId]);
$user = $stmt->fetch();

if (!$user) {
    render_page_start('Nie znaleziono | POLchatka');
    echo '<div class="content-card"><h1 class="section-title">Użytkownik nie istnieje</h1><p><a href="/php/wall.php">Wróć na ścianę</a></p></div>';
    render_page_end();
    exit;
}

// Policz posty
$stmtPosts = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE user_id = ?');
$stmtPosts->execute([$viewingId]);
$postCount = (int)$stmtPosts->fetchColumn();

// Policz znajomych
$stmtFriends = $pdo->prepare('SELECT COUNT(*) FROM friendships WHERE (user_id = ? OR friend_id = ?) AND status = ?');
$stmtFriends->execute([$viewingId, $viewingId, 'accepted']);
$friendCount = (int)$stmtFriends->fetchColumn();

// Sprawdź relację z zalogowanym
$friendStatus = null;
$friendRequestId = null;
if ($loggedIn && !$isOwnProfile) {
    $stmtRel = $pdo->prepare('SELECT id, user_id, friend_id, status FROM friendships WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)');
    $stmtRel->execute([$myId, $viewingId, $viewingId, $myId]);
    $rel = $stmtRel->fetch();
    if ($rel) {
        $friendStatus    = $rel['status'];
        $friendRequestId = $rel['id'];
    }
}

// Ostatnie posty użytkownika
$stmtUserPosts = $pdo->prepare('SELECT content, created_at FROM posts WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$stmtUserPosts->execute([$viewingId]);
$userPosts = $stmtUserPosts->fetchAll();

$avatar = trim($user['avatar'] ?? '');
$registeredAt = !empty($user['created_at']) ? date('d.m.Y H:i', strtotime($user['created_at'])) : '—';
?>
<?php render_page_start(($isOwnProfile ? 'Twój profil' : escape($user['username'])) . ' | POLchatka'); ?>

<div class="content-card">
    <h1 class="section-title"><?= $isOwnProfile ? 'Twój profil' : 'Profil: ' . escape($user['username']) ?></h1>

    <section class="profile-card">
        <div class="avatar"><?= $avatar !== '' ? escape($avatar) : '👤' ?></div>
        <div class="details">
            <p><strong>Login:</strong> <?= escape($user['username']) ?></p>
            <?php if ($isOwnProfile): ?>
                <p><strong>Email:</strong> <?= escape($user['email']) ?></p>
            <?php endif; ?>
            <p><strong>Data rejestracji:</strong> <?= escape($registeredAt) ?></p>
            <p><strong>Postów:</strong> <?= $postCount ?></p>
            <p><strong>Znajomych:</strong> <?= $friendCount ?></p>
        </div>
    </section>

    <?php if ($loggedIn && !$isOwnProfile): ?>
        <div class="profile-actions">
            <?php if ($friendStatus === 'accepted'): ?>
                <span class="badge badge-success">✅ Znajomy</span>
                <a href="/php/messages.php?to=<?= $viewingId ?>" class="button">✉️ Wyślij wiadomość</a>
            <?php elseif ($friendStatus === 'pending'): ?>
                <span class="badge badge-pending">⏳ Zaproszenie wysłane</span>
            <?php else: ?>
                <form method="POST" action="/php/friends_action.php" style="display:inline">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="to_user_id" value="<?= $viewingId ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <button class="button" type="submit">👥 Dodaj do znajomych</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($userPosts)): ?>
<div class="content-card">
    <h2 class="section-title">📋 Ostatnie posty</h2>
    <?php foreach ($userPosts as $post): ?>
        <div class="post-card">
            <div class="post-content"><?= nl2br(escape($post['content'])) ?></div>
            <div class="post-meta"><?= date('d.m.Y H:i', strtotime($post['created_at'])) ?></div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php render_page_end(); ?>
