<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/categories.php';

header('Content-Type: text/plain; charset=utf-8');

if (php_sapi_name() !== 'cli' && (($_GET['key'] ?? '') !== 'setup-once')) {
    http_response_code(403);
    echo "Forbidden. Run from CLI or add ?key=setup-once once, then remove this file.\n";
    exit;
}

try {
    $pdo = db();
    $driver = db_driver(app_config());
    echo "DB OK ({$driver})\n";

    $tables = $driver === 'mysql'
        ? $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN)
        : $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);

    echo 'Tables: ' . implode(', ', $tables) . "\n";

    $articles = fetch_all_articles();
    if ($articles === []) {
        $sourcePath = dirname(__DIR__) . '/_github_index.html';
        if (!is_file($sourcePath)) {
            echo "No articles yet. Upload _github_index.html and run:\n";
            echo "php scripts/import-github-articles.php\n";
            exit(0);
        }

        require dirname(__DIR__) . '/scripts/import-github-articles.php';
        exit(0);
    }

    echo 'Articles: ' . count($articles) . "\n";
    echo "Setup complete.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Setup failed: ' . $e->getMessage() . "\n";
    exit(1);
}
