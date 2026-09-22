<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

echo 'PHP: ' . PHP_VERSION . PHP_EOL;
echo 'PDO: ' . (extension_loaded('pdo') ? 'yes' : 'no') . PHP_EOL;
echo 'PDO MySQL: ' . (extension_loaded('pdo_mysql') ? 'yes' : 'no') . PHP_EOL;
echo 'PDO SQLite: ' . (extension_loaded('pdo_sqlite') ? 'yes' : 'no') . PHP_EOL;
echo 'MySQLi: ' . (extension_loaded('mysqli') ? 'yes' : 'no') . PHP_EOL;

$configPath = dirname(__DIR__) . '/config.local.php';
echo 'config.local.php: ' . (is_file($configPath) ? 'yes' : 'no') . PHP_EOL;

try {
    require_once dirname(__DIR__) . '/includes/db.php';
    $pdo = db();
    $driver = db_driver(app_config());
    echo 'DB driver: ' . $driver . PHP_EOL;
    echo 'DB OK' . PHP_EOL;
} catch (Throwable $e) {
    echo 'DB ERROR: ' . $e->getMessage() . PHP_EOL;
}
