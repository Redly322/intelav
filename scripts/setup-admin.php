<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/users.php';

const SETUP_KEY = 'setup-once';

if (php_sapi_name() === 'cli') {
    $login = $argv[1] ?? '';
    $password = $argv[2] ?? '';
    $name = $argv[3] ?? '';

    if ($login === '' || $password === '' || $name === '') {
        fwrite(STDERR, "Usage: php scripts/setup-admin.php <login> <password> \"<name>\"\n");
        exit(1);
    }

    try {
        $id = create_admin_user($login, $password, $name);
        echo "Admin created: id={$id}, login={$login}\n";
        exit(0);
    } catch (Throwable $e) {
        fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
        exit(1);
    }
}

if (($_GET['key'] ?? '') !== SETUP_KEY) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden. Open with ?key=" . SETUP_KEY . " once, then delete this file.\n";
    exit;
}

header('Content-Type: text/html; charset=utf-8');

$message = '';
$error = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim((string) ($_POST['login'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $name = trim((string) ($_POST['name'] ?? ''));

    try {
        $id = create_admin_user($login, $password, $name);
        $message = "Администратор создан (id={$id}). Удалите scripts/setup-admin.php с сервера.";
        $done = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$existing = count_admin_users();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Создание администратора</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 420px; margin: 40px auto; padding: 0 16px; }
    label { display: block; margin: 12px 0 6px; font-weight: 600; }
    input { width: 100%; padding: 10px; box-sizing: border-box; }
    button { margin-top: 16px; padding: 10px 16px; font-weight: 600; cursor: pointer; }
    .ok { color: #027a48; background: #ecfdf3; padding: 12px; border-radius: 8px; }
    .err { color: #b42318; background: #fef3f2; padding: 12px; border-radius: 8px; }
    .muted { color: #667085; font-size: 0.9rem; }
  </style>
</head>
<body>
  <h1>Первый администратор</h1>
  <p class="muted">Администраторов в базе: <?= (int) $existing ?>. После создания удалите этот файл.</p>

  <?php if ($message !== ''): ?>
    <p class="ok"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
    <p><a href="/admin/login.php">Перейти ко входу</a></p>
  <?php elseif (!$done): ?>
    <?php if ($error !== ''): ?>
      <p class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <form method="post">
      <label>Логин<input type="text" name="login" required maxlength="60" value="admin" /></label>
      <label>Пароль<input type="password" name="password" required minlength="8" /></label>
      <label>Имя<input type="text" name="name" required maxlength="100" value="ИнтелАв" /></label>
      <button type="submit">Создать</button>
    </form>
  <?php endif; ?>
</body>
</html>
