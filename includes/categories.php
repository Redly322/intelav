<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/articles.php';

function category_url(string $slug): string
{
    return site_root() . 'articles#' . rawurlencode($slug);
}

function flatten_article_categories(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $pdo = db();
    $parentIds = $pdo->query(
        'SELECT DISTINCT parent_id FROM article_categories WHERE parent_id IS NOT NULL'
    )->fetchAll(PDO::FETCH_COLUMN);

    if ($parentIds === []) {
        return;
    }

    $pdo->exec('UPDATE article_categories SET parent_id = NULL');

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM articles WHERE category_id = :id');
    $deleteStmt = $pdo->prepare('DELETE FROM article_categories WHERE id = :id');
    foreach ($parentIds as $parentId) {
        $id = (int) $parentId;
        if ($id <= 0) {
            continue;
        }
        $countStmt->execute([':id' => $id]);
        if ((int) $countStmt->fetchColumn() === 0) {
            $deleteStmt->execute([':id' => $id]);
        }
    }
}

function insert_category(string $slug, string $title, ?int $parentId = null, int $sortOrder = 0): int
{
    if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        throw new InvalidArgumentException('Category slug must contain only lowercase letters, numbers and hyphens.');
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO article_categories (slug, title, parent_id, sort_order)
         VALUES (:slug, :title, NULL, :sort_order)'
    );
    $stmt->execute([
        ':slug' => $slug,
        ':title' => $title,
        ':sort_order' => $sortOrder,
    ]);

    return (int) $pdo->lastInsertId();
}

function fetch_category_by_id(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT id, slug, title, parent_id, sort_order
         FROM article_categories
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $id]);

    $category = $stmt->fetch();
    return $category ?: null;
}

function update_category(int $id, string $slug, string $title, ?int $parentId, int $sortOrder): void
{
    if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        throw new InvalidArgumentException('Category slug must contain only lowercase letters, numbers and hyphens.');
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'UPDATE article_categories
         SET slug = :slug, title = :title, parent_id = NULL, sort_order = :sort_order
         WHERE id = :id'
    );
    $stmt->execute([
        ':id' => $id,
        ':slug' => $slug,
        ':title' => $title,
        ':sort_order' => $sortOrder,
    ]);
}

function delete_category(int $id): void
{
    $pdo = db();

    $articleCount = $pdo->prepare('SELECT COUNT(*) FROM articles WHERE category_id = :id');
    $articleCount->execute([':id' => $id]);
    if ((int) $articleCount->fetchColumn() > 0) {
        throw new RuntimeException('Нельзя удалить раздел со статьями.');
    }

    $stmt = $pdo->prepare('DELETE FROM article_categories WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function fetch_category_by_slug(string $slug): ?array
{
    if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        return null;
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT id, slug, title, parent_id, sort_order
         FROM article_categories
         WHERE slug = :slug
         LIMIT 1'
    );
    $stmt->execute([':slug' => $slug]);

    $category = $stmt->fetch();
    return $category ?: null;
}

function fetch_all_categories(): array
{
    flatten_article_categories();
    $pdo = db();
    $stmt = $pdo->query(
        'SELECT id, slug, title, parent_id, sort_order
         FROM article_categories
         ORDER BY sort_order ASC, id ASC'
    );

    return $stmt->fetchAll();
}

function fetch_category_tree(): array
{
    $categories = fetch_all_categories();
    $articles = fetch_all_articles_grouped();

    $tree = [];
    foreach ($categories as $category) {
        $category['children'] = [];
        $category['articles'] = $articles[(int) $category['id']] ?? [];
        $tree[] = $category;
    }

    return $tree;
}

function fetch_nav_category_tree(): array
{
    return fetch_category_tree();
}

function fetch_category_breadcrumb(?int $categoryId): array
{
    if ($categoryId === null || $categoryId <= 0) {
        return [];
    }

    $categories = fetch_all_categories();
    $byId = [];
    foreach ($categories as $category) {
        $byId[(int) $category['id']] = $category;
    }

    $trail = [];
    $currentId = $categoryId;
    $guard = 0;

    while ($currentId !== null && isset($byId[$currentId]) && $guard < 10) {
        array_unshift($trail, $byId[$currentId]);
        $parentId = $byId[$currentId]['parent_id'];
        $currentId = $parentId !== null ? (int) $parentId : null;
        $guard++;
    }

    return $trail;
}
