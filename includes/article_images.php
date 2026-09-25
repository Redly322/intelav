<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

const ARTICLE_IMAGE_MAX_BYTES = 10485760;
const ARTICLE_IMAGE_MIME_MAP = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
];

function article_uploads_root(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'articles';
}

function article_images_dir(int $articleId): string
{
    return article_uploads_root() . DIRECTORY_SEPARATOR . $articleId;
}

function article_image_url(int $articleId, string $filename): string
{
    return site_root() . 'uploads/articles/' . $articleId . '/' . rawurlencode($filename);
}

function map_article_image_row(array $row): array
{
    $articleId = (int) $row['article_id'];
    $filename = (string) $row['filename'];

    return [
        'id' => (int) $row['id'],
        'article_id' => $articleId,
        'filename' => $filename,
        'original_name' => (string) $row['original_name'],
        'caption' => (string) $row['caption'],
        'sort_order' => (int) $row['sort_order'],
        'created_at' => (string) ($row['created_at'] ?? ''),
        'url' => article_image_url($articleId, $filename),
        'path' => article_images_dir($articleId) . DIRECTORY_SEPARATOR . $filename,
    ];
}

function fetch_article_images(int $articleId): array
{
    if ($articleId <= 0) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT id, article_id, filename, original_name, caption, sort_order, created_at
         FROM article_images
         WHERE article_id = :article_id
         ORDER BY sort_order ASC, id ASC'
    );
    $stmt->execute([':article_id' => $articleId]);

    return array_map('map_article_image_row', $stmt->fetchAll());
}

function fetch_article_image(int $imageId): ?array
{
    if ($imageId <= 0) {
        return null;
    }

    $stmt = db()->prepare(
        'SELECT id, article_id, filename, original_name, caption, sort_order, created_at
         FROM article_images
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $imageId]);
    $row = $stmt->fetch();

    return $row ? map_article_image_row($row) : null;
}

function article_images_not_in_text(array $images, string $html): array
{
    return array_values(array_filter(
        $images,
        static fn (array $image): bool => !str_contains($html, $image['filename'])
    ));
}

function update_article_image_captions(int $articleId, array $captions): void
{
    $stmt = db()->prepare(
        'UPDATE article_images
         SET caption = :caption
         WHERE id = :id AND article_id = :article_id'
    );

    foreach ($captions as $imageId => $caption) {
        $id = (int) $imageId;
        if ($id <= 0) {
            continue;
        }

        $stmt->execute([
            ':caption' => mb_substr(trim((string) $caption), 0, 255),
            ':id' => $id,
            ':article_id' => $articleId,
        ]);
    }
}

function delete_article_image(int $imageId, int $articleId): void
{
    $image = fetch_article_image($imageId);
    if ($image === null || $image['article_id'] !== $articleId) {
        throw new InvalidArgumentException('Изображение не найдено.');
    }

    $stmt = db()->prepare('DELETE FROM article_images WHERE id = :id AND article_id = :article_id');
    $stmt->execute([
        ':id' => $imageId,
        ':article_id' => $articleId,
    ]);

    if (is_file($image['path'])) {
        unlink($image['path']);
    }
}

function delete_article_image_files(int $articleId): void
{
    $dir = article_images_dir($articleId);
    if (!is_dir($dir)) {
        return;
    }

    $items = scandir($dir);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_file($path)) {
            unlink($path);
        }
    }

    @rmdir($dir);
}

function store_uploaded_article_images(int $articleId, array $fileBag): array
{
    $uploaded = 0;
    $errors = [];

    foreach (normalize_uploaded_files($fileBag) as $file) {
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        try {
            store_one_article_image($articleId, $file);
            $uploaded++;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    return [
        'uploaded' => $uploaded,
        'errors' => $errors,
    ];
}

function normalize_uploaded_files(array $fileBag): array
{
    if ($fileBag === [] || !isset($fileBag['name'])) {
        return [];
    }

    if (!is_array($fileBag['name'])) {
        return [$fileBag];
    }

    $files = [];
    foreach ($fileBag['name'] as $index => $name) {
        $files[] = [
            'name' => (string) $name,
            'type' => (string) ($fileBag['type'][$index] ?? ''),
            'tmp_name' => (string) ($fileBag['tmp_name'][$index] ?? ''),
            'error' => (int) ($fileBag['error'][$index] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int) ($fileBag['size'][$index] ?? 0),
        ];
    }

    return $files;
}

function store_one_article_image(int $articleId, array $file): void
{
    $originalName = trim((string) ($file['name'] ?? ''));
    $label = $originalName !== '' ? $originalName : 'файл';
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException(upload_error_message($label, $error));
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new InvalidArgumentException('Не удалось принять файл «' . $label . '».');
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > ARTICLE_IMAGE_MAX_BYTES) {
        throw new InvalidArgumentException('Файл «' . $label . '» больше 10 МБ или пустой.');
    }

    $mime = uploaded_image_mime($tmpName);
    if ($mime === null || !isset(ARTICLE_IMAGE_MIME_MAP[$mime])) {
        throw new InvalidArgumentException('Файл «' . $label . '» не является изображением JPG, PNG, WEBP или GIF.');
    }

    $extension = ARTICLE_IMAGE_MIME_MAP[$mime];
    $filename = bin2hex(random_bytes(8)) . '-' . sanitize_upload_basename($originalName, $extension);
    $dir = article_images_dir($articleId);

    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Не удалось создать папку для изображений. Проверьте права на uploads/.');
    }

    $target = $dir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($tmpName, $target)) {
        throw new RuntimeException('Не удалось сохранить файл «' . $label . '».');
    }

    $sortOrder = next_article_image_sort($articleId);
    $stmt = db()->prepare(
        'INSERT INTO article_images (article_id, filename, original_name, caption, sort_order)
         VALUES (:article_id, :filename, :original_name, :caption, :sort_order)'
    );
    $stmt->execute([
        ':article_id' => $articleId,
        ':filename' => $filename,
        ':original_name' => mb_substr($originalName !== '' ? $originalName : $filename, 0, 255),
        ':caption' => '',
        ':sort_order' => $sortOrder,
    ]);
}

function next_article_image_sort(int $articleId): int
{
    $stmt = db()->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM article_images WHERE article_id = :article_id');
    $stmt->execute([':article_id' => $articleId]);

    return ((int) $stmt->fetchColumn()) + 1;
}

function uploaded_image_mime(string $tmpName): ?string
{
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpName);
        if (is_string($mime) && $mime !== '') {
            return $mime;
        }
    }

    $info = @getimagesize($tmpName);
    if (is_array($info) && isset($info['mime']) && is_string($info['mime'])) {
        return $info['mime'];
    }

    return null;
}

function sanitize_upload_basename(string $originalName, string $extension): string
{
    $base = pathinfo($originalName, PATHINFO_FILENAME);
    $base = strtolower($base);
    $base = preg_replace('/[^a-z0-9]+/', '-', $base) ?? '';
    $base = trim($base, '-');
    if ($base === '') {
        $base = 'image';
    }

    return substr($base, 0, 60) . '.' . $extension;
}

function upload_error_message(string $label, int $error): string
{
    return match ($error) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Файл «' . $label . '» слишком большой для сервера.',
        UPLOAD_ERR_PARTIAL => 'Файл «' . $label . '» загрузился не полностью.',
        UPLOAD_ERR_NO_TMP_DIR => 'На сервере нет временной папки для загрузки.',
        UPLOAD_ERR_CANT_WRITE => 'Сервер не смог записать файл «' . $label . '».',
        UPLOAD_ERR_EXTENSION => 'Загрузка файла «' . $label . '» была остановлена.',
        default => 'Не удалось загрузить файл «' . $label . '».',
    };
}

function article_image_figure_html(array $image): string
{
    $url = htmlspecialchars($image['url'], ENT_QUOTES, 'UTF-8');
    $alt = htmlspecialchars($image['caption'] !== '' ? $image['caption'] : $image['original_name'], ENT_QUOTES, 'UTF-8');
    $html = '<figure class="blog-figure">' . "\n";
    $html .= '  <img src="' . $url . '" alt="' . $alt . '" loading="lazy" />' . "\n";
    if ($image['caption'] !== '') {
        $html .= '  <figcaption>' . htmlspecialchars($image['caption'], ENT_QUOTES, 'UTF-8') . '</figcaption>' . "\n";
    }
    $html .= '</figure>';

    return $html;
}
