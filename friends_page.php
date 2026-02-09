<?php
/**
 * POLchatka — Lista znajomych
 */
require __DIR__ . '/config.php';
require_login();
require_once __DIR__ . '/../templates/header.php';

header('Content-Type: text/html; charset=utf-8', true);

$uid = (int)$_SESSION['user_id'];

// Flash messages
$error   = $_SESSION['flash_error']   ?? '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

// Znajomi (zaakceptowani)
$stmtFriends = $pdo->prepare("
    SELECT u.id, u.username, u.avatar, f.accepted_at
    FROM friendships f
    JOIN users u ON u.id = IF(f.user_id = ?, f.friend_id, f.user_id)
    WHERE (f.user_id = ? OR f.friend_id = ?) AND f.status = 'accepted'
    ORDER BY f.accepted_at DESC
");
$stmtFriends->execute([$uid, $uid, $uid]);
$friends = $stmtFriends->fetchAll();

// Zaproszenia oczekujące (do mnie)
$stmtInvites = $pdo->prepare("
    SELECT f.id AS request_id, u.id AS user_id, u.username, u.avatar
    FROM friendships f
    JOIN users u ON u.id = f.user_id
    WHERE f.friend_id = ? AND f.status = 'pending'
    ORDER BY f.created_at DESC
");
$stmtInvites->execute([$uid]);
$invites = $stmtInvites->fetchAll();

// Wszyscy użytkownicy (do dodawania) — bez siebie i bez istniejących relacji
$stmtOthers = $pdo->prepare("
    SELECT u.id, u.username, u.avatar
    FROM users u
    WHERE u.id != ?
      AND u.id NOT IN (
          SELECT IF(f.user_id = ?, f.friend_id, f.user_id)
          FROM friendships f
          WHERE f.user_id = ? OR f.friend_id = ?
      )
    ORDER BY u.username ASC
    LIMIT 50
");
$stmtOthers->execute([$uid, $uid, $uid, $uid]);
$otherUsers = $stmtOthers->fetchAll();

$csrfToken = csrf_token();
?>
<?php render_page_start('Znajomi | POLchatka'); ?>

<?php if ($error): ?>
    <div class="flash error"><?= escape($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="flash success"><?= escape($success) ?></div>
<?php endif; ?>

<!-- Zaproszenia oczekujące -->
<?php if (!empty($invites)): ?>
<div class="content-card">
    <h2 class="section-title">📨 Zaproszenia do znajomych (<?= count($invites) ?>)</h2>
    <?php foreach ($invites as $inv): ?>
        <div class="friend-row">
            <span class="post-avatar"><?= escape($inv['avatar'] ?? '👤') ?></span>
            <a href="/php/profile.php?id=<?= (int)$inv['user_id'] ?>"><?= escape($inv['username']) ?></a>
            <div class="friend-actions">
                <form method="POST" action="/php/friends_action.php" style="display:inline">
                    <input type="hidden" name="action" value="accept">
                    <input type="hidden" name="request_id" value="<?= (int)$inv['request_id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                    <button class="button btn-sm" type="submit">✅ Akceptuj</button>
                </form>
                <form method="POST" action="/php/friends_action.php" style="display:inline">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="request_id" value="<?= (int)$inv['request_id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                    <button class="button btn-sm btn-danger" type="submit">❌ Odrzuć</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Moi znajomi -->
<div class="content-card">
    <h2 class="section-title">👥 Moi znajomi (<?= count($friends) ?>)</h2>
    <?php if (empty($friends)): ?>
        <div class="empty-state"><p>Nie masz jeszcze znajomych. Dodaj kogoś! 👇</p></div>
    <?php else: ?>
        <?php foreach ($friends as $f): ?>
            <div class="friend-row">
                <span class="post-avatar"><?= escape($f['avatar'] ?? '👤') ?></span>
                <a href="/php/profile.php?id=<?= (int)$f['id'] ?>"><?= escape($f['username']) ?></a>
                <div class="friend-actions">
                    <a href="/php/messages.php?to=<?= (int)$f['id'] ?>" class="button btn-sm">✉️ Wiadomość</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Inni użytkownicy -->
<?php if (!empty($otherUsers)): ?>
<div class="content-card">
    <h2 class="section-title">🔍 Inni użytkownicy</h2>
    <?php foreach ($otherUsers as $ou): ?>
        <div class="friend-row">
            <span class="post-avatar"><?= escape($ou['avatar'] ?? '👤') ?></span>
            <a href="/php/profile.php?id=<?= (int)$ou['id'] ?>"><?= escape($ou['username']) ?></a>
            <div class="friend-actions">
                <form method="POST" action="/php/friends_action.php" style="display:inline">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="to_user_id" value="<?= (int)$ou['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                    <button class="button btn-sm" type="submit">👥 Dodaj</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php render_page_end(); ?>
