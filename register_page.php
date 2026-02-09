<?php
/**
 * POLchatka — Strona rejestracji
 */
if (session_status() === PHP_SESSION_NONE) session_start();

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
<?php render_page_start('Rejestracja | POLchatka'); ?>

<div class="content-card">
    <h1 class="section-title">🚀 Rejestracja w POLchatce</h1>

    <?php if ($error): ?>
        <div class="flash error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="flash success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="wizard-steps">
        <span class="step-badge active">1. Dane</span>
        <span class="step-sep">→</span>
        <span class="step-badge">2. Gotowe!</span>
    </div>

    <form method="POST" action="/php/register.php" class="polchatka-form" id="registerForm">
        <div class="form-field">
            <label for="username">Nazwa użytkownika: <span class="req">*</span></label>
            <input class="input" type="text" id="username" name="username" required
                   minlength="3" maxlength="30" pattern="[a-zA-Z0-9_]+"
                   placeholder="min. 3 znaki, litery/cyfry/_" autofocus>
        </div>

        <div class="form-field">
            <label for="email">Adres e-mail: <span class="req">*</span></label>
            <input class="input" type="email" id="email" name="email" required
                   placeholder="np. jan@example.com">
        </div>

        <div class="form-field">
            <label for="password">Hasło: <span class="req">*</span></label>
            <input class="input" type="password" id="password" name="password" required
                   minlength="6" placeholder="Min. 6 znaków">
        </div>

        <div class="form-field">
            <label for="confirmPassword">Powtórz hasło: <span class="req">*</span></label>
            <input class="input" type="password" id="confirmPassword" name="confirmPassword" required
                   minlength="6" placeholder="Powtórz hasło">
        </div>

        <div class="form-actions">
            <button class="button" type="submit">Zarejestruj się</button>
        </div>
    </form>

    <div class="form-links">
        <p>Masz już konto? <a href="/php/login_page.php">Zaloguj się</a></p>
    </div>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const pw = document.getElementById('password').value;
    const pw2 = document.getElementById('confirmPassword').value;
    if (pw !== pw2) {
        e.preventDefault();
        alert('Hasła nie są identyczne!');
    }
});
</script>

<?php render_page_end(); ?>
