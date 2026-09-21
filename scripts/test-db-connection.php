<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

try {
    $config = app_config();
    $pdo = db();
    $driver = db_driver($config);
    $table = $driver === 'mysql' ? 'SHOW TABLES' : "SELECT name FROM sqlite_master WHERE type='table'";
    $rows = $pdo->query($table)->fetchAll();

    echo "OK: connected using {$driver}\n";
    echo 'Tables: ' . count($rows) . "\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'DB connection failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
