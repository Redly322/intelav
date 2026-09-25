<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/articles.php';
require_once __DIR__ . '/_layout.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        flash_set('error', 'Неверный CSRF-токен.');
        header('Location: /admin/articles.php');
        exit;
    }

    if (($_POST['action'] ?? '') === 'delete') {
        try {
            delete_article((int) ($_POST['id'] ?? 0));
            flash_set('success', 'Статья удалена.');
        } catch (Throwable $e) {
            flash_set('error', $e->getMessage());
        }
    }

    header('Location: /admin/articles.php');
    exit;
}

$articles = fetch_all_articles();

admin_render_start('Статьи', 'articles');
?>
<div class="admin-card">
  <div class="admin-actions" style="margin-bottom: 16px;">
    <a class="btn btn-primary" href="/admin/article-edit.php">Новая статья</a>
  </div>

  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Заголовок</th>
        <th>Раздел</th>
        <th>Автор</th>
        <th>Дата</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if ($articles === []): ?>
        <tr><td colspan="6" class="admin-muted">Статей пока нет.</td></tr>
      <?php else: ?>
        <?php foreach ($articles as $article): ?>
          <tr>
            <td><?= (int) $article['id'] ?></td>
            <td>
              <a href="<?= htmlspecialchars(article_url($article['link']), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                <?= htmlspecialchars($article['description'], ENT_QUOTES, 'UTF-8') ?>
              </a>
            </td>
            <td><?= htmlspecialchars((string) ($article['category_title'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($article['author'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars(format_article_date($article['published_at']), ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <div class="admin-actions">
                <a class="btn btn-secondary" href="/admin/article-edit.php?id=<?= (int) $article['id'] ?>">Изменить</a>
                <form class="admin-inline-form" method="post" onsubmit="return confirm('Удалить статью?');">
                  <?php admin_csrf_field(); ?>
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="id" value="<?= (int) $article['id'] ?>" />
                  <button type="submit" class="btn btn-danger">Удалить</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php
admin_render_end();
