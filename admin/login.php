<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/auth.php';
require_once dirname(__DIR__) . '/includes/admin/flash.php';
require_once dirname(__DIR__) . '/includes/rate_limit.php';

if (is_admin_logged_in()) {
    header('Location: /admin/');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim((string) ($_POST['login'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $token = (string) ($_POST['csrf_token'] ?? '');

    if (rate_limit_exceeded('admin-login', 8, 900)) {
        $error = 'Слишком много попыток. Подождите несколько минут.';
    } elseif (!verify_csrf($token)) {
        $error = 'Неверный логин или пароль.';
    } elseif ($login === '' || $password === '') {
        $error = 'Введите логин и пароль.';
    } elseif (admin_login($login, $password)) {
        header('Location: /admin/');
        exit;
    } else {
        $error = 'Неверный логин или пароль.';
    }
}

header('X-Robots-Tag: noindex, nofollow');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex, nofollow" />
  <title>Вход — Админка ИнтелАв</title>
  <link rel="stylesheet" href="/admin/assets/admin.css?v=1" />
</head>
<body class="admin-body admin-login">
  <div class="admin-card">
    <h1>Вход в админку</h1>
    <?php if ($error !== ''): ?>
      <div class="flash flash-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <form class="admin-form" method="post" action="/admin/login.php">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>" />
      <label>
        Логин
        <input type="text" name="login" autocomplete="username" required />
      </label>
      <label>
        Пароль
        <input type="password" name="password" autocomplete="current-password" required />
      </label>
      <div class="admin-actions">
        <button type="submit" class="btn btn-primary">Войти</button>
      </div>
    </form>
  </div>
</body>
</html>
