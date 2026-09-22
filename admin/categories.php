<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/categories.php';
require_once dirname(__DIR__) . '/includes/admin/slug.php';
require_once __DIR__ . '/_layout.php';

require_admin();

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editCategory = $editId > 0 ? fetch_category_by_id($editId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        flash_set('error', 'Неверный CSRF-токен.');
        header('Location: /admin/categories.php');
        exit;
    }

    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'delete') {
            delete_category((int) ($_POST['id'] ?? 0));
            flash_set('success', 'Раздел удалён.');
        } else {
            $title = trim((string) ($_POST['title'] ?? ''));
            $slugInput = trim((string) ($_POST['slug'] ?? ''));
            $slug = $slugInput !== '' ? normalize_slug($slugInput) : normalize_slug($title);
            $parentRaw = trim((string) ($_POST['parent_id'] ?? ''));
            $parentId = $parentRaw === '' ? null : (int) $parentRaw;
            $sortOrder = (int) ($_POST['sort_order'] ?? 0);
            $id = (int) ($_POST['id'] ?? 0);

            if ($title === '') {
                throw new InvalidArgumentException('Название обязательно.');
            }
            if (!validate_slug($slug)) {
                throw new InvalidArgumentException('Slug должен содержать только a-z, 0-9 и дефис.');
            }
            if ($parentId !== null && $parentId === $id) {
                throw new InvalidArgumentException('Раздел не может быть родителем сам себе.');
            }

            if ($action === 'update' && $id > 0) {
                update_category($id, $slug, $title, $parentId, $sortOrder);
                flash_set('success', 'Раздел обновлён.');
            } else {
                insert_category($slug, $title, $parentId, $sortOrder);
                flash_set('success', 'Раздел создан.');
            }
        }
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }

    header('Location: /admin/categories.php' . ($editId > 0 && $action !== 'delete' ? '?edit=' . $editId : ''));
    exit;
}

$categories = fetch_all_categories();

admin_render_start('Разделы', 'categories');
?>
<div class="admin-grid">
  <div class="admin-card">
    <h2><?= $editCategory ? 'Редактировать раздел' : 'Новый раздел' ?></h2>
    <form class="admin-form" method="post">
      <?php admin_csrf_field(); ?>
      <input type="hidden" name="action" value="<?= $editCategory ? 'update' : 'create' ?>" />
      <?php if ($editCategory): ?>
        <input type="hidden" name="id" value="<?= (int) $editCategory['id'] ?>" />
      <?php endif; ?>

      <label>
        Название
        <input type="text" name="title" required value="<?= htmlspecialchars((string) ($editCategory['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
      </label>

      <label>
        Slug (URL)
        <input type="text" name="slug" placeholder="авто из названия" value="<?= htmlspecialchars((string) ($editCategory['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
      </label>

      <label>
        Родительский раздел
        <select name="parent_id">
          <option value="">— корневой —</option>
          <?php foreach ($categories as $category): ?>
            <?php if ($editCategory && (int) $category['id'] === (int) $editCategory['id']) continue; ?>
            <option value="<?= (int) $category['id'] ?>"
              <?= isset($editCategory['parent_id']) && (int) $editCategory['parent_id'] === (int) $category['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($category['title'], ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>
        Порядок сортировки
        <input type="number" name="sort_order" value="<?= (int) ($editCategory['sort_order'] ?? 0) ?>" />
      </label>

      <div class="admin-actions">
        <button type="submit" class="btn btn-primary"><?= $editCategory ? 'Сохранить' : 'Создать' ?></button>
        <?php if ($editCategory): ?>
          <a class="btn btn-secondary" href="/admin/categories.php">Отмена</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="admin-card">
    <h2>Все разделы</h2>
    <table class="admin-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Название</th>
          <th>Slug</th>
          <th>Родитель</th>
          <th>Порядок</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if ($categories === []): ?>
          <tr><td colspan="6" class="admin-muted">Разделов пока нет.</td></tr>
        <?php else: ?>
          <?php
          $byId = [];
          foreach ($categories as $category) {
              $byId[(int) $category['id']] = $category;
          }
          foreach ($categories as $category):
          ?>
            <tr>
              <td><?= (int) $category['id'] ?></td>
              <td><?= htmlspecialchars($category['title'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($category['slug'], ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <?php
                $parentId = $category['parent_id'] !== null ? (int) $category['parent_id'] : null;
                echo $parentId && isset($byId[$parentId])
                    ? htmlspecialchars($byId[$parentId]['title'], ENT_QUOTES, 'UTF-8')
                    : '—';
                ?>
              </td>
              <td><?= (int) $category['sort_order'] ?></td>
              <td>
                <div class="admin-actions">
                  <a class="btn btn-secondary" href="/admin/categories.php?edit=<?= (int) $category['id'] ?>">Изменить</a>
                  <form class="admin-inline-form" method="post" onsubmit="return confirm('Удалить раздел?');">
                    <?php admin_csrf_field(); ?>
                    <input type="hidden" name="action" value="delete" />
                    <input type="hidden" name="id" value="<?= (int) $category['id'] ?>" />
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
</div>
<?php
admin_render_end();
