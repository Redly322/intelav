<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/article_replies.php';
require_once dirname(__DIR__) . '/includes/articles.php';
require_once __DIR__ . '/_layout.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        flash_set('error', 'Неверный CSRF-токен.');
        header('Location: /admin/replies.php');
        exit;
    }

    if (($_POST['action'] ?? '') === 'delete') {
        try {
            delete_article_reply((int) ($_POST['id'] ?? 0));
            flash_set('success', 'Ответ удалён.');
        } catch (Throwable $e) {
            flash_set('error', $e->getMessage());
        }
    }

    header('Location: /admin/replies.php');
    exit;
}

$replies = fetch_all_replies_admin();

admin_render_start('Ответы', 'replies');
?>
<div class="admin-card">
  <p class="admin-muted">Ответы публикуются сразу после отправки. Здесь можно просмотреть и удалить.</p>

  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Статья</th>
        <th>Имя</th>
        <th>Email</th>
        <th>Сообщение</th>
        <th>Дата</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if ($replies === []): ?>
        <tr><td colspan="7" class="admin-muted">Ответов пока нет.</td></tr>
      <?php else: ?>
        <?php foreach ($replies as $reply): ?>
          <tr>
            <td><?= (int) $reply['id'] ?></td>
            <td>
              <a href="<?= htmlspecialchars(article_url($reply['article_link']), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                <?= htmlspecialchars($reply['article_title'], ENT_QUOTES, 'UTF-8') ?>
              </a>
            </td>
            <td><?= htmlspecialchars($reply['name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($reply['email'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= nl2br(htmlspecialchars($reply['message'], ENT_QUOTES, 'UTF-8')) ?></td>
            <td><?= htmlspecialchars($reply['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <form class="admin-inline-form" method="post" onsubmit="return confirm('Удалить ответ?');">
                <?php admin_csrf_field(); ?>
                <input type="hidden" name="action" value="delete" />
                <input type="hidden" name="id" value="<?= (int) $reply['id'] ?>" />
                <button type="submit" class="btn btn-danger">Удалить</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php
admin_render_end();
