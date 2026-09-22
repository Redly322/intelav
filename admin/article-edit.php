<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/articles.php';
require_once dirname(__DIR__) . '/includes/categories.php';
require_once dirname(__DIR__) . '/includes/admin/slug.php';
require_once __DIR__ . '/_layout.php';

require_admin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$article = $id > 0 ? fetch_article_by_id($id) : null;

if ($id > 0 && $article === null) {
    flash_set('error', 'Статья не найдена.');
    header('Location: /admin/articles.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        flash_set('error', 'Неверный CSRF-токен.');
        header('Location: /admin/article-edit.php' . ($id > 0 ? '?id=' . $id : ''));
        exit;
    }

    try {
        $description = trim((string) ($_POST['description'] ?? ''));
        $author = trim((string) ($_POST['author'] ?? ''));
        $publishedAt = trim((string) ($_POST['published_at'] ?? ''));
        $text = (string) ($_POST['text'] ?? '');
        $linkInput = trim((string) ($_POST['link'] ?? ''));
        $link = $linkInput !== '' ? normalize_slug($linkInput) : normalize_slug($description);
        $categoryRaw = trim((string) ($_POST['category_id'] ?? ''));
        $categoryId = $categoryRaw === '' ? null : (int) $categoryRaw;

        if ($description === '') {
            throw new InvalidArgumentException('Заголовок обязателен.');
        }
        if ($author === '') {
            throw new InvalidArgumentException('Автор обязателен.');
        }
        if ($publishedAt === '') {
            throw new InvalidArgumentException('Дата публикации обязательна.');
        }
        if (!validate_slug($link)) {
            throw new InvalidArgumentException('Slug должен содержать только a-z, 0-9 и дефис.');
        }

        if ($article) {
            update_article($id, $link, $description, $author, $publishedAt, $text, $categoryId);
            flash_set('success', 'Статья обновлена.');
            header('Location: /admin/article-edit.php?id=' . $id);
        } else {
            $newId = insert_article($link, $description, $author, $publishedAt, $text, $categoryId);
            flash_set('success', 'Статья создана.');
            header('Location: /admin/article-edit.php?id=' . $newId);
        }
        exit;
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
        header('Location: /admin/article-edit.php' . ($id > 0 ? '?id=' . $id : ''));
        exit;
    }
}

$categories = fetch_all_categories();
$pageTitle = $article ? 'Редактировать статью' : 'Новая статья';

admin_render_start($pageTitle, 'articles');
?>
<div class="admin-card">
  <form class="admin-form" method="post">
    <?php admin_csrf_field(); ?>

    <label>
      Заголовок
      <input type="text" name="description" required value="<?= htmlspecialchars((string) ($article['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
    </label>

    <label>
      Slug (URL)
      <input type="text" name="link" placeholder="авто из заголовка" value="<?= htmlspecialchars((string) ($article['link'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
    </label>

    <label>
      Раздел
      <select name="category_id">
        <option value="">— без раздела —</option>
        <?php foreach ($categories as $category): ?>
          <option value="<?= (int) $category['id'] ?>"
            <?= isset($article['category_id']) && (int) $article['category_id'] === (int) $category['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($category['title'], ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>
      Автор
      <input type="text" name="author" required value="<?= htmlspecialchars((string) ($article['author'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
    </label>

    <label>
      Дата публикации
      <input type="date" name="published_at" required value="<?= htmlspecialchars((string) ($article['published_at'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8') ?>" />
    </label>

    <label>
      Текст (HTML)
      <textarea name="text" rows="18"><?= htmlspecialchars((string) ($article['text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
    </label>

    <div class="admin-actions">
      <button type="submit" class="btn btn-primary">Сохранить</button>
      <a class="btn btn-secondary" href="/admin/articles.php">К списку</a>
      <?php if ($article): ?>
        <a class="btn btn-secondary" href="<?= htmlspecialchars(article_url($article['link']), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Открыть на сайте</a>
      <?php endif; ?>
    </div>
  </form>
</div>
<?php
admin_render_end();
