<?php
require __DIR__ . '/config.php';
require_once __DIR__ . '/../templates/header.php';

// Override JSON headers from config for HTML output
header('Content-Type: text/html; charset=utf-8');

function ensureCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

$errors = [];
$successMessage = null;
$isLoggedIn = isset($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isLoggedIn) {
        http_response_code(401);
        $errors[] = 'Musisz być zalogowany, aby dodać post.';
    }

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!$errors && !validateCsrfToken($csrfToken)) {
        http_response_code(400);
        $errors[] = 'Nieprawidłowy token CSRF.';
    }

    $content = sanitize_text($_POST['content'] ?? '');
    if (!$errors && $content === '') {
        $errors[] = 'Treść posta nie może być pusta.';
    }

    if (!$errors) {
        $content = mb_substr($content, 0, 5000);
        $stmt = $pdo->prepare('INSERT INTO posts (user_id, content) VALUES (?, ?)');
        $stmt->execute([$_SESSION['user_id'], $content]);
        $successMessage = 'Post został dodany pomyślnie.';
    }
}

$stmt = $pdo->query('SELECT p.content, p.created_at, u.username FROM posts p JOIN users u ON u.id = p.user_id ORDER BY p.created_at DESC');
$posts = $stmt->fetchAll();
$csrfToken = ensureCsrfToken();
?>
<?php render_page_start('Ściana postów'); ?>
<div class="content-card">
    <h1 class="section-title">Ściana postów</h1>

    <?php if ($errors): ?>
        <div class="flash error">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php elseif ($successMessage): ?>
        <div class="flash success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" action="wall.php">
        <div class="form-field">
            <label for="content">Nowy post:</label>
            <textarea class="textarea" id="content" name="content" required <?= $isLoggedIn ? '' : 'disabled' ?>></textarea>
        </div>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <button class="button" type="submit" <?= $isLoggedIn ? '' : 'disabled' ?>>Opublikuj</button>
        <?php if (!$isLoggedIn): ?>
            <p>Aby dodać post, zaloguj się.</p>
        <?php endif; ?>
    </form>

    <h2>Ostatnie posty</h2>
    <?php if (empty($posts)): ?>
        <p>Brak postów.</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <div class="post-meta">
                    <strong><?= htmlspecialchars($post['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <span> — <?= htmlspecialchars($post['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="post-content"><?= nl2br(htmlspecialchars($post['content'], ENT_QUOTES, 'UTF-8')) ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php render_page_end(); ?>
