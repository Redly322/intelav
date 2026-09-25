<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/admin/users.php';
require_once dirname(__DIR__) . '/includes/article_replies.php';
require_once __DIR__ . '/_layout.php';

require_admin();

$pdo = db();
$stats = [
    'articles' => (int) $pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn(),
    'categories' => (int) $pdo->query('SELECT COUNT(*) FROM article_categories')->fetchColumn(),
    'replies' => (int) $pdo->query('SELECT COUNT(*) FROM article_replies')->fetchColumn(),
    'admins' => count_admin_users(),
];

admin_render_start('Главная', 'dashboard');
?>
<div class="admin-grid">
  <div class="admin-stats">
    <div class="admin-stat">
      <strong><?= $stats['articles'] ?></strong>
      <span>Статей</span>
    </div>
    <div class="admin-stat">
      <strong><?= $stats['categories'] ?></strong>
      <span>Разделов</span>
    </div>
    <div class="admin-stat">
      <strong><?= $stats['replies'] ?></strong>
      <span>Ответов</span>
    </div>
    <div class="admin-stat">
      <strong><?= $stats['admins'] ?></strong>
      <span>Администраторов</span>
    </div>
  </div>

  <div class="admin-card">
    <h2>Быстрые действия</h2>
    <div class="admin-actions" style="margin-top: 14px;">
      <a class="btn btn-primary" href="/admin/article-edit.php">Новая статья</a>
      <a class="btn btn-secondary" href="/admin/categories.php">Разделы</a>
      <a class="btn btn-secondary" href="/admin/replies.php">Ответы</a>
    </div>
  </div>
</div>
<?php
admin_render_end();
