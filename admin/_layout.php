<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/auth.php';
require_once dirname(__DIR__) . '/includes/admin/flash.php';

function admin_render_start(string $title, string $active = ''): void
{
    $admin = current_admin();
    ?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> — Админка ИнтелАв</title>
  <link rel="stylesheet" href="/admin/assets/admin.css?v=2" />
</head>
<body class="admin-body">
  <div class="admin-shell">
    <div class="admin-top">
      <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
      <nav class="admin-nav" aria-label="Админ-меню">
        <a href="/admin/" class="<?= $active === 'dashboard' ? 'is-active' : '' ?>">Главная</a>
        <a href="/admin/categories.php" class="<?= $active === 'categories' ? 'is-active' : '' ?>">Разделы</a>
        <a href="/admin/articles.php" class="<?= $active === 'articles' ? 'is-active' : '' ?>">Статьи</a>
        <a href="/admin/replies.php" class="<?= $active === 'replies' ? 'is-active' : '' ?>">Ответы</a>
        <a href="/admin/users.php" class="<?= $active === 'users' ? 'is-active' : '' ?>">Пользователи</a>
        <a href="/" target="_blank" rel="noopener">Сайт</a>
        <a href="/admin/logout.php">Выход</a>
      </nav>
    </div>
    <?php if ($admin): ?>
      <p class="admin-muted">Вы вошли как <?= htmlspecialchars($admin['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($admin['login'], ENT_QUOTES, 'UTF-8') ?>)</p>
    <?php endif; ?>
    <?php $flash = flash_get(); if ($flash): ?>
      <div class="flash flash-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>
<?php
}

function admin_render_end(): void
{
    ?>
  </div>
</body>
</html>
<?php
}

function admin_csrf_field(): void
{
    echo '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '" />';
}
