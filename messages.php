<?php
/**
 * POLchatka — Wiadomości prywatne
 */
require __DIR__ . '/config.php';
require_login();
require_once __DIR__ . '/../templates/header.php';

header('Content-Type: text/html; charset=utf-8', true);

$uid = (int)$_SESSION['user_id'];
$toId = isset($_GET['to']) ? (int)$_GET['to'] : 0;

// Flash messages
$error   = $_SESSION['flash_error']   ?? '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

// Wysyłanie wiadomości
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Nieprawidłowy token CSRF';
    } else {
        $msgTo      = (int)($_POST['to_user_id'] ?? 0);
        $msgContent = sanitize_text($_POST['content'] ?? '');

        if ($msgTo <= 0 || $msgTo === $uid) {
            $error = 'Nieprawidłowy odbiorca';
        } elseif ($msgContent === '') {
            $error = 'Wiadomość nie może być pusta';
        } else {
            $msgContent = mb_substr($msgContent, 0, 2000);
            $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)')
                ->execute([$uid, $msgTo, $msgContent]);
            $success = 'Wiadomość wysłana! ✉️';
            $toId = $msgTo; // zostań w konwersacji
        }
    }
}

// Lista konwersacji (unikalne osoby, z którymi wymieniono wiadomości)
$stmtConvos = $pdo->prepare("
    SELECT u.id, u.username, u.avatar,
           MAX(m.created_at) AS last_msg_at,
           SUM(CASE WHEN m.receiver_id = ? AND m.is_read = 0 THEN 1 ELSE 0 END) AS unread
    FROM messages m
    JOIN users u ON u.id = IF(m.sender_id = ?, m.receiver_id, m.sender_id)
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY u.id
    ORDER BY last_msg_at DESC
");
$stmtConvos->execute([$uid, $uid, $uid, $uid]);
$conversations = $stmtConvos->fetchAll();

// Aktualna konwersacja
$chatMessages = [];
$chatUser     = null;
if ($toId > 0) {
    // Pobierz dane rozmówcy
    $stmtChatUser = $pdo->prepare('SELECT id, username, avatar FROM users WHERE id = ?');
    $stmtChatUser->execute([$toId]);
    $chatUser = $stmtChatUser->fetch();

    if ($chatUser) {
        // Oznacz jako przeczytane
        $pdo->prepare('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0')
            ->execute([$toId, $uid]);

        // Pobierz wiadomości
        $stmtMsgs = $pdo->prepare("
            SELECT m.*, u.username AS sender_name, u.avatar AS sender_avatar
            FROM messages m
            JOIN users u ON u.id = m.sender_id
            WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC
            LIMIT 100
        ");
        $stmtMsgs->execute([$uid, $toId, $toId, $uid]);
        $chatMessages = $stmtMsgs->fetchAll();
    }
}

$csrfToken = csrf_token();
?>
<?php render_page_start('Wiadomości | POLchatka'); ?>

<?php if ($error): ?>
    <div class="flash error"><?= escape($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="flash success"><?= escape($success) ?></div>
<?php endif; ?>

<div class="messages-layout">
    <!-- Panel konwersacji -->
    <div class="content-card convos-panel">
        <h2 class="section-title">✉️ Konwersacje</h2>
        <?php if (empty($conversations)): ?>
            <div class="empty-state"><p>Brak wiadomości. Napisz do kogoś!</p></div>
        <?php else: ?>
            <?php foreach ($conversations as $c): ?>
                <a href="/php/messages.php?to=<?= (int)$c['id'] ?>"
                   class="convo-item <?= $toId === (int)$c['id'] ? 'convo-active' : '' ?>">
                    <span class="post-avatar"><?= escape($c['avatar'] ?? '👤') ?></span>
                    <span class="convo-name"><?= escape($c['username']) ?></span>
                    <?php if ((int)$c['unread'] > 0): ?>
                        <span class="badge badge-unread"><?= (int)$c['unread'] ?></span>
                    <?php endif; ?>
                    <span class="convo-time"><?= date('d.m H:i', strtotime($c['last_msg_at'])) ?></span>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Panel czatu -->
    <div class="content-card chat-panel">
        <?php if ($chatUser): ?>
            <h2 class="section-title">
                <?= escape($chatUser['avatar'] ?? '👤') ?>
                Rozmowa z <a href="/php/profile.php?id=<?= (int)$chatUser['id'] ?>"><?= escape($chatUser['username']) ?></a>
            </h2>

            <div class="chat-messages" id="chatMessages">
                <?php if (empty($chatMessages)): ?>
                    <div class="empty-state"><p>Rozpocznij rozmowę! 💬</p></div>
                <?php else: ?>
                    <?php foreach ($chatMessages as $m): ?>
                        <div class="chat-bubble <?= (int)$m['sender_id'] === $uid ? 'chat-mine' : 'chat-theirs' ?>">
                            <div class="chat-text"><?= nl2br(escape($m['content'])) ?></div>
                            <div class="chat-time"><?= date('d.m H:i', strtotime($m['created_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <form method="POST" action="/php/messages.php?to=<?= (int)$chatUser['id'] ?>" class="chat-form">
                <input type="hidden" name="to_user_id" value="<?= (int)$chatUser['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                <textarea class="textarea chat-input" name="content" rows="2"
                          placeholder="Napisz wiadomość..." required></textarea>
                <button class="button" type="submit">📨 Wyślij</button>
            </form>
        <?php else: ?>
            <div class="empty-state">
                <h2 class="section-title">✉️ Wiadomości</h2>
                <p>Wybierz konwersację z listy lub <a href="/php/friends_page.php">napisz do znajomego</a>.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-scroll do końca wiadomości
const chatBox = document.getElementById('chatMessages');
if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
</script>

<?php render_page_end(); ?>
