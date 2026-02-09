<?php
/**
 * POLchatka — Strona logowania
 */
if (session_status() === PHP_SESSION_NONE) session_start();

// Już zalogowany? Przekieruj
if (isset($_SESSION['user_id'])) {
    header('Location: /php/wall.php');
    exit;
}

require_once __DIR__ . '/../templates/header.php';
header('Content-Type: text/html; charset=utf-8', true);

$error   = $_GET['error']   ?? ($_SESSION['flash_error']   ?? '');
$success = $_GET['success'] ?? ($_SESSION['flash_success'] ?? '');
unset($_SESSION['flash_error'], $_SESSION['flash_success']);
?>
<?php render_page_start('Logowanie | POLchatka'); ?>

<div class="content-card">
    <h1 class="section-title">🔑 Logowanie do POLchatki</h1>

    <?php if ($error): ?>
        <div class="flash error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="flash success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="/php/login.php" class="polchatka-form">
        <div class="form-field">
            <label for="username">Login lub e-mail:</label>
            <input class="input" type="text" id="username" name="username" required
                   placeholder="np. janek lub janek@example.com" autofocus>
        </div>

        <div class="form-field">
            <label for="password">Hasło:</label>
            <input class="input" type="password" id="password" name="password" required
                   placeholder="Min. 6 znaków">
        </div>

        <div class="form-actions">
            <button class="button" type="submit">Zaloguj się</button>
        </div>
    </form>

    <div class="form-links">
        <p>Nie masz konta? <a href="/php/register_page.php">Zarejestruj się</a></p>
    </div>

    <div class="content-card demo-box">
        <h3 class="section-title">🧪 Konta testowe</h3>
        <p class="demo-hint">Hasło do wszystkich: <code>password</code></p>
        <div class="demo-accounts-grid">
            <button type="button" class="demo-btn" onclick="fillDemo('admin','password')">👨‍💼 admin</button>
            <button type="button" class="demo-btn" onclick="fillDemo('janek','password')">👨 janek</button>
            <button type="button" class="demo-btn" onclick="fillDemo('ania','password')">👩 ania</button>
            <button type="button" class="demo-btn" onclick="fillDemo('kasia','password')">👧 kasia</button>
            <button type="button" class="demo-btn" onclick="fillDemo('bartek','password')">🧑 bartek</button>
        </div>
    </div>
</div>

<script>
function fillDemo(u, p) {
    document.getElementById('username').value = u;
    document.getElementById('password').value = p;
}
</script>

<?php render_page_end(); ?>
