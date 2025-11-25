<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/config.php';

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
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twój profil | POLchatka</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f7f7;
            color: #222;
            margin: 0;
            padding: 0;
        }
        header {
            background: #004c97;
            color: #fff;
            padding: 16px;
        }
        nav a {
            color: #fff;
            margin-right: 16px;
            text-decoration: none;
            font-weight: bold;
        }
        .container {
            max-width: 720px;
            margin: 32px auto;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .profile-card {
            display: flex;
            padding: 24px;
            gap: 16px;
            align-items: center;
        }
        .avatar {
            font-size: 48px;
            width: 72px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eef4ff;
            border-radius: 50%;
            border: 2px solid #004c97;
        }
        .details p {
            margin: 6px 0;
        }
        .details strong {
            display: inline-block;
            width: 140px;
        }
        .empty-state {
            padding: 24px;
        }
    </style>
</head>
<body>
<header>
    <h1>Twój profil</h1>
    <nav>
        <a href="/content/sciana/sciana.html">🧱 Wall</a>
        <a href="/content/profil.html#messages">✉️ Wiadomości</a>
        <a href="logout.php">🚪 Wyloguj</a>
    </nav>
</header>
<main class="container">
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
</main>
</body>
</html>
