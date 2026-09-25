<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/articles.php';
require_once dirname(__DIR__) . '/includes/article_images.php';
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

    $action = (string) ($_POST['action'] ?? 'save');

    try {
        if ($action === 'delete_image') {
            if ($article === null) {
                throw new InvalidArgumentException('Сначала сохраните статью.');
            }
            delete_article_image((int) ($_POST['image_id'] ?? 0), $id);
            flash_set('success', 'Изображение удалено.');
            header('Location: /admin/article-edit.php?id=' . $id);
            exit;
        }

        $description = trim((string) ($_POST['description'] ?? ''));
        $author = trim((string) ($_POST['author'] ?? ''));
        $publishedAt = trim((string) ($_POST['published_at'] ?? ''));
        $text = article_normalize_for_storage((string) ($_POST['text'] ?? ''));
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
            $savedId = $id;
            $message = 'Статья обновлена.';
        } else {
            $savedId = insert_article($link, $description, $author, $publishedAt, $text, $categoryId);
            $message = 'Статья создана.';
        }

        update_article_image_captions($savedId, $_POST['captions'] ?? []);
        $upload = store_uploaded_article_images($savedId, $_FILES['images'] ?? []);

        if ($upload['uploaded'] > 0) {
            $message .= ' Загружено изображений: ' . $upload['uploaded'] . '.';
        }
        if ($upload['errors'] !== []) {
            $message .= ' ' . implode(' ', $upload['errors']);
            flash_set($upload['uploaded'] > 0 ? 'success' : 'error', $message);
        } else {
            flash_set('success', $message);
        }

        header('Location: /admin/article-edit.php?id=' . $savedId);
        exit;
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
        header('Location: /admin/article-edit.php' . ($id > 0 ? '?id=' . $id : ''));
        exit;
    }
}

$categories = fetch_all_categories();
$images = $article ? fetch_article_images((int) $article['id']) : [];
$pageTitle = $article ? 'Редактировать статью' : 'Новая статья';

admin_render_start($pageTitle, 'articles');
?>
<div class="admin-card">
  <form class="admin-form" method="post" enctype="multipart/form-data">
    <?php admin_csrf_field(); ?>
    <input type="hidden" name="action" value="save" />

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
      Текст
      <textarea id="article-text" name="text" rows="18" placeholder="Пишите обычным текстом. Новый абзац — с новой строки. Список — строки, которые начинаются с - "><?= htmlspecialchars(article_edit_plain((string) ($article['text'] ?? '')), ENT_QUOTES, 'UTF-8') ?></textarea>
    </label>
    <p class="admin-muted">HTML писать не нужно. Каждый абзац с новой строки. Чтобы сделать список, начните строку с «- ».</p>

    <div class="admin-images">
      <h2>Изображения</h2>
      <p class="admin-muted">JPG, PNG, WEBP или GIF, до 10 МБ. Прикреплённые файлы появятся в статье. Чтобы поставить фото в нужное место, нажмите «Вставить в текст».</p>

      <?php if ($images !== []): ?>
        <div class="admin-image-grid">
          <?php foreach ($images as $image): ?>
            <div class="admin-image-card">
              <a href="<?= htmlspecialchars($image['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                <img src="<?= htmlspecialchars($image['url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($image['original_name'], ENT_QUOTES, 'UTF-8') ?>" />
              </a>
              <label>
                Подпись
                <input type="text" name="captions[<?= (int) $image['id'] ?>]" value="<?= htmlspecialchars($image['caption'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Подпись под картинкой" />
              </label>
              <div class="admin-actions">
                <button
                  type="button"
                  class="btn btn-secondary js-insert-image"
                  data-src="<?= htmlspecialchars($image['url'], ENT_QUOTES, 'UTF-8') ?>"
                  data-caption="<?= htmlspecialchars($image['caption'], ENT_QUOTES, 'UTF-8') ?>"
                  data-alt="<?= htmlspecialchars($image['caption'] !== '' ? $image['caption'] : $image['original_name'], ENT_QUOTES, 'UTF-8') ?>"
                >Вставить в текст</button>
                <button type="submit" class="btn btn-danger" form="delete-image-<?= (int) $image['id'] ?>" onclick="return confirm('Удалить изображение?');">Удалить</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php elseif ($article): ?>
        <p class="admin-muted">К статье пока ничего не прикреплено.</p>
      <?php endif; ?>

      <label>
        Прикрепить изображения
        <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple />
      </label>
    </div>

    <div class="admin-actions">
      <button type="submit" class="btn btn-primary">Сохранить</button>
      <a class="btn btn-secondary" href="/admin/articles.php">К списку</a>
      <?php if ($article): ?>
        <a class="btn btn-secondary" href="<?= htmlspecialchars(article_url($article['link']), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Открыть на сайте</a>
      <?php endif; ?>
    </div>
  </form>

  <?php foreach ($images as $image): ?>
    <form id="delete-image-<?= (int) $image['id'] ?>" method="post" hidden>
      <?php admin_csrf_field(); ?>
      <input type="hidden" name="action" value="delete_image" />
      <input type="hidden" name="image_id" value="<?= (int) $image['id'] ?>" />
    </form>
  <?php endforeach; ?>
</div>
<script>
(() => {
  const textarea = document.getElementById("article-text");
  if (!textarea) {
    return;
  }

  document.querySelectorAll(".js-insert-image").forEach((button) => {
    button.addEventListener("click", () => {
      const src = button.getAttribute("data-src") || "";
      const caption = (button.getAttribute("data-caption") || "").replaceAll("[", "").replaceAll("]", "");
      const marker = caption ? `[картинка: ${src} | ${caption}]` : `[картинка: ${src}]`;

      const start = textarea.selectionStart ?? textarea.value.length;
      const end = textarea.selectionEnd ?? start;
      const before = textarea.value.slice(0, start);
      const after = textarea.value.slice(end);
      const prefix = before && !before.endsWith("\n") ? "\n" : "";
      textarea.value = before + prefix + marker + "\n" + after;
      textarea.focus();
      const cursor = (before + prefix + marker + "\n").length;
      textarea.setSelectionRange(cursor, cursor);
    });
  });
})();
</script>
<?php
admin_render_end();
