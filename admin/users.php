<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/users.php';
require_once __DIR__ . '/_layout.php';

require_admin();

$current = current_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        flash_set('error', 'Неверный CSRF-токен.');
        header('Location: /admin/users.php');
        exit;
    }

    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'delete') {
            $deleteId = (int) ($_POST['id'] ?? 0);
            if ($current && $deleteId === (int) $current['id']) {
                throw new RuntimeException('Нельзя удалить свой аккаунт.');
            }
            if (count_admin_users() <= 1) {
                throw new RuntimeException('Нельзя удалить последнего администратора.');
            }
            delete_admin_user($deleteId);
            flash_set('success', 'Пользователь удалён.');
        } elseif ($action === 'create') {
            create_admin_user(
                (string) ($_POST['login'] ?? ''),
                (string) ($_POST['password'] ?? ''),
                (string) ($_POST['name'] ?? '')
            );
            flash_set('success', 'Пользователь создан.');
        }
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }

    header('Location: /admin/users.php');
    exit;
}

$users = fetch_all_admin_users();

admin_render_start('Пользователи', 'users');
?>
<div class="admin-grid">
  <div class="admin-card">
    <h2>Новый администратор</h2>
    <form class="admin-form" method="post">
      <?php admin_csrf_field(); ?>
      <input type="hidden" name="action" value="create" />

      <label>
        Логин
        <input type="text" name="login" required maxlength="60" autocomplete="off" />
      </label>

      <label>
        Имя
        <input type="text" name="name" required maxlength="100" />
      </label>

      <label>
        Пароль (мин. 8 символов)
        <input type="password" name="password" required minlength="8" autocomplete="new-password" />
      </label>

      <div class="admin-actions">
        <button type="submit" class="btn btn-primary">Создать</button>
      </div>
    </form>
  </div>

  <div class="admin-card">
    <h2>Все администраторы</h2>
    <table class="admin-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Логин</th>
          <th>Имя</th>
          <th>Создан</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
          <tr>
            <td><?= (int) $user['id'] ?></td>
            <td><?= htmlspecialchars($user['login'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($user['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?php if (!$current || (int) $user['id'] !== (int) $current['id']): ?>
                <form class="admin-inline-form" method="post" onsubmit="return confirm('Удалить пользователя?');">
                  <?php admin_csrf_field(); ?>
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="id" value="<?= (int) $user['id'] ?>" />
                  <button type="submit" class="btn btn-danger">Удалить</button>
                </form>
              <?php else: ?>
                <span class="admin-muted">это вы</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
admin_render_end();
