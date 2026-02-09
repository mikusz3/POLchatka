<?php
/**
 * POLchatka — Ściana postów
 */
require __DIR__ . '/config.php';
require_once __DIR__ . '/../templates/header.php';

header('Content-Type: text/html; charset=utf-8', true);

$errors         = [];
$successMessage = null;
$isLoggedIn     = isset($_SESSION['user_id']);

// Obsługa dodawania posta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isLoggedIn) {
        $errors[] = 'Musisz być zalogowany, aby dodać post.';
    }
    if (!$errors && !validate_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Nieprawidłowy token CSRF. Odśwież stronę.';
    }
    $content = sanitize_text($_POST['content'] ?? '');
    if (!$errors && $content === '') {
        $errors[] = 'Treść posta nie może być pusta.';
    }
    if (!$errors) {
        $content = mb_substr($content, 0, 5000);
        $pdo->prepare('INSERT INTO posts (user_id, content) VALUES (?, ?)')->execute([$_SESSION['user_id'], $content]);
        $successMessage = 'Post opublikowany! 🎉';
        // PRG — Post/Redirect/Get
        $_SESSION['flash_success'] = $successMessage;
        header('Location: /php/wall.php');
        exit;
    }
}

// Flash z sesji
if (!$successMessage && isset($_SESSION['flash_success'])) {
    $successMessage = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

// Pobierz posty
$stmt = $pdo->query('
    SELECT p.id, p.content, p.created_at, p.likes, u.id AS uid, u.username, u.avatar
    FROM posts p
    JOIN users u ON u.id = p.user_id
    ORDER BY p.created_at DESC
    LIMIT 50
');
$posts     = $stmt->fetchAll();
$csrfToken = csrf_token();
?>
<?php render_page_start('Ściana postów | POLchatka'); ?>

<div class="content-card">
    <h1 class="section-title">📋 Ściana postów</h1>

    <?php if ($errors): ?>
        <div class="flash error">
            <?php foreach ($errors as $e): ?>
                <div><?= escape($e) ?></div>
            <?php endforeach; ?>
        </div>
    <?php elseif ($successMessage): ?>
        <div class="flash success"><?= escape($successMessage) ?></div>
    <?php endif; ?>

    <form method="POST" action="/php/wall.php" class="polchatka-form post-form">
        <div class="form-field">
            <label for="content">Co słychać? 💬</label>
            <textarea class="textarea" id="content" name="content" rows="3"
                      placeholder="<?= $isLoggedIn ? 'Napisz coś na ścianie...' : 'Zaloguj się, aby pisać' ?>"
                      <?= $isLoggedIn ? '' : 'disabled' ?> required></textarea>
        </div>
        <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
        <button class="button" type="submit" <?= $isLoggedIn ? '' : 'disabled' ?>>📝 Opublikuj</button>
        <?php if (!$isLoggedIn): ?>
            <p class="hint"><a href="/php/login_page.php">Zaloguj się</a>, aby dodawać posty.</p>
        <?php endif; ?>
    </form>
</div>

<div class="content-card">
    <h2 class="section-title">🕐 Najnowsze posty</h2>
    <?php if (empty($posts)): ?>
        <div class="empty-state">
            <p>Brak postów. Bądź pierwszy! 🚀</p>
        </div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <div class="post-header">
                    <span class="post-avatar"><?= escape($post['avatar'] ?? '👤') ?></span>
                    <a href="/php/profile.php?id=<?= (int)$post['uid'] ?>" class="post-author">
                        <?= escape($post['username']) ?>
                    </a>
                    <span class="post-meta"><?= date('d.m.Y H:i', strtotime($post['created_at'])) ?></span>
                </div>
                <div class="post-content"><?= nl2br(escape($post['content'])) ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php render_page_end(); ?>
