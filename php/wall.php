<?php
require __DIR__ . '/config.php';

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
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Ściana postów</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; background: #f7f7f7; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        form textarea { width: 100%; min-height: 120px; padding: 0.5rem; }
        form button { margin-top: 0.5rem; padding: 0.5rem 1rem; }
        .flash { padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem; }
        .flash.error { background: #ffe1e1; color: #a10000; }
        .flash.success { background: #e3ffe5; color: #046c28; }
        .post { border-bottom: 1px solid #ddd; padding: 0.75rem 0; }
        .post:last-child { border-bottom: none; }
        .post .meta { color: #666; font-size: 0.9rem; margin-bottom: 0.25rem; }
        .post .content { white-space: pre-wrap; }
    </style>
</head>
<body>
<div class="container">
    <h1>Ściana postów</h1>

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
        <label for="content">Nowy post:</label>
        <textarea id="content" name="content" required <?= $isLoggedIn ? '' : 'disabled' ?>></textarea>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" <?= $isLoggedIn ? '' : 'disabled' ?>>Opublikuj</button>
        <?php if (!$isLoggedIn): ?>
            <p>Aby dodać post, zaloguj się.</p>
        <?php endif; ?>
    </form>

    <h2>Ostatnie posty</h2>
    <?php if (empty($posts)): ?>
        <p>Brak postów.</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="post">
                <div class="meta">
                    <strong><?= htmlspecialchars($post['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <span>— <?= htmlspecialchars($post['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="content"><?= nl2br(htmlspecialchars($post['content'], ENT_QUOTES, 'UTF-8')) ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
