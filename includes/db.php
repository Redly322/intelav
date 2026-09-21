<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = app_config();
    $dbPath = $config['db_path'];

    $dir = dirname($dbPath);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create database directory.');
    }

    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS feedback (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT NOT NULL,
            email TEXT,
            message TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\', \'localtime\'))
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS article_categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug TEXT NOT NULL UNIQUE,
            title TEXT NOT NULL,
            parent_id INTEGER,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\', \'localtime\')),
            FOREIGN KEY (parent_id) REFERENCES article_categories(id) ON DELETE SET NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS articles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            link TEXT NOT NULL UNIQUE,
            description TEXT NOT NULL,
            author TEXT NOT NULL,
            published_at TEXT NOT NULL,
            text TEXT NOT NULL,
            category_id INTEGER,
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\', \'localtime\')),
            FOREIGN KEY (category_id) REFERENCES article_categories(id) ON DELETE SET NULL
        )'
    );

    ensure_articles_category_column($pdo);

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS article_replies (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            article_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            message TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\', \'localtime\')),
            FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE INDEX IF NOT EXISTS idx_article_replies_article_id
         ON article_replies (article_id)'
    );

    return $pdo;
}

function ensure_articles_category_column(PDO $pdo): void
{
    $columns = $pdo->query('PRAGMA table_info(articles)')->fetchAll();
    $hasCategory = false;

    foreach ($columns as $column) {
        if (($column['name'] ?? '') === 'category_id') {
            $hasCategory = true;
            break;
        }
    }

    if (!$hasCategory) {
        $pdo->exec('ALTER TABLE articles ADD COLUMN category_id INTEGER REFERENCES article_categories(id)');
    }
}

function save_feedback(string $name, string $phone, ?string $email, string $message): array
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO feedback (name, phone, email, message) VALUES (:name, :phone, :email, :message)'
    );
    $stmt->execute([
        ':name' => $name,
        ':phone' => $phone,
        ':email' => $email,
        ':message' => $message,
    ]);

    $id = (int) $pdo->lastInsertId();
    $row = $pdo->prepare('SELECT id, name, phone, email, message, created_at FROM feedback WHERE id = :id');
    $row->execute([':id' => $id]);

    $feedback = $row->fetch();
    if (!$feedback) {
        throw new RuntimeException('Saved feedback row was not found.');
    }

    return $feedback;
}
