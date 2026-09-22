<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function article_url(string $link): string
{
    return site_root() . 'article/' . rawurlencode($link);
}

function format_article_date(string $date): string
{
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }

    return date('d.m.Y', $timestamp);
}

function article_select_columns(): string
{
    return 'a.id, a.link, a.description, a.author, a.published_at, a.text, a.category_id, a.created_at,
            c.slug AS category_slug, c.title AS category_title, c.parent_id AS category_parent_id';
}

function map_article_row(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'link' => $row['link'],
        'description' => $row['description'],
        'author' => $row['author'],
        'published_at' => $row['published_at'],
        'text' => $row['text'],
        'category_id' => isset($row['category_id']) && $row['category_id'] !== null ? (int) $row['category_id'] : null,
        'created_at' => $row['created_at'],
        'category_slug' => $row['category_slug'] ?? null,
        'category_title' => $row['category_title'] ?? null,
        'category_parent_id' => isset($row['category_parent_id']) && $row['category_parent_id'] !== null
            ? (int) $row['category_parent_id']
            : null,
    ];
}

function fetch_all_articles(): array
{
    $pdo = db();
    $stmt = $pdo->query(
        'SELECT ' . article_select_columns() . '
         FROM articles a
         LEFT JOIN article_categories c ON c.id = a.category_id
         ORDER BY a.published_at DESC, a.id DESC'
    );

    return array_map('map_article_row', $stmt->fetchAll());
}

function fetch_all_articles_grouped(): array
{
    $grouped = [];
    foreach (fetch_all_articles() as $article) {
        $categoryId = $article['category_id'];
        if ($categoryId === null) {
            continue;
        }
        $grouped[$categoryId][] = $article;
    }

    return $grouped;
}

function fetch_article_by_link(string $link): ?array
{
    if (!preg_match('/^[a-z0-9-]+$/', $link)) {
        return null;
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT ' . article_select_columns() . '
         FROM articles a
         LEFT JOIN article_categories c ON c.id = a.category_id
         WHERE a.link = :link
         LIMIT 1'
    );
    $stmt->execute([':link' => $link]);

    $article = $stmt->fetch();
    return $article ? map_article_row($article) : null;
}

function fetch_article_by_id(int $articleId): ?array
{
    if ($articleId <= 0) {
        return null;
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT ' . article_select_columns() . '
         FROM articles a
         LEFT JOIN article_categories c ON c.id = a.category_id
         WHERE a.id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $articleId]);

    $article = $stmt->fetch();
    return $article ? map_article_row($article) : null;
}

function insert_article(
    string $link,
    string $description,
    string $author,
    string $publishedAt,
    string $text,
    ?int $categoryId = null
): int {
    if (!preg_match('/^[a-z0-9-]+$/', $link)) {
        throw new InvalidArgumentException('Article link must contain only lowercase letters, numbers and hyphens.');
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO articles (link, description, author, published_at, text, category_id)
         VALUES (:link, :description, :author, :published_at, :text, :category_id)'
    );
    $stmt->execute([
        ':link' => $link,
        ':description' => $description,
        ':author' => $author,
        ':published_at' => $publishedAt,
        ':text' => $text,
        ':category_id' => $categoryId,
    ]);

    return (int) $pdo->lastInsertId();
}

function update_article(
    int $id,
    string $link,
    string $description,
    string $author,
    string $publishedAt,
    string $text,
    ?int $categoryId = null
): void {
    if (!preg_match('/^[a-z0-9-]+$/', $link)) {
        throw new InvalidArgumentException('Article link must contain only lowercase letters, numbers and hyphens.');
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'UPDATE articles
         SET link = :link, description = :description, author = :author,
             published_at = :published_at, text = :text, category_id = :category_id
         WHERE id = :id'
    );
    $stmt->execute([
        ':id' => $id,
        ':link' => $link,
        ':description' => $description,
        ':author' => $author,
        ':published_at' => $publishedAt,
        ':text' => $text,
        ':category_id' => $categoryId,
    ]);
}

function delete_article(int $id): void
{
    $pdo = db();
    $stmt = $pdo->prepare('DELETE FROM articles WHERE id = :id');
    $stmt->execute([':id' => $id]);
}
