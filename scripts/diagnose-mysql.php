<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

if (php_sapi_name() !== 'cli' && (($_GET['key'] ?? '') !== 'setup-once')) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

require_once dirname(__DIR__) . '/includes/config.php';

$config = app_config();
$host = (string) ($config['db_host'] ?? 'localhost');
$port = (int) ($config['db_port'] ?? 3306);
$user = (string) ($config['db_user'] ?? '');
$pass = (string) ($config['db_pass'] ?? '');
$charset = (string) ($config['db_charset'] ?? 'utf8mb4');
$configuredDb = (string) ($config['db_name'] ?? '');

echo "Configured db_name: {$configuredDb}\n";
echo "Configured db_user: {$user}\n\n";

if (!extension_loaded('pdo_mysql')) {
    echo "ERROR: pdo_mysql is not enabled\n";
    exit(1);
}

try {
    $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $host, $port, $charset);
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "Login OK\n\n";
    echo "Available databases:\n";

    $databases = $pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($databases as $database) {
        $marker = ($database === $configuredDb) ? ' <-- configured' : '';
        echo " - {$database}{$marker}\n";
    }

    if ($configuredDb !== '' && in_array($configuredDb, $databases, true)) {
        echo "\nConfigured database exists and is accessible.\n";
    } elseif ($configuredDb !== '') {
        echo "\nConfigured database '{$configuredDb}' is NOT in the list.\n";
        echo "Fix config.local.php: set db_name to one of the databases above.\n";
    }
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
