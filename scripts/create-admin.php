<?php

declare(strict_types=1);

/**
 * CLI: php scripts/create-admin.php login password "Display Name"
 */

require_once dirname(__DIR__) . '/includes/admin/users.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Run from CLI only.\n";
    exit(1);
}

$login = $argv[1] ?? '';
$password = $argv[2] ?? '';
$name = $argv[3] ?? '';

if ($login === '' || $password === '' || $name === '') {
    fwrite(STDERR, "Usage: php scripts/create-admin.php <login> <password> \"<name>\"\n");
    exit(1);
}

try {
    $id = create_admin_user($login, $password, $name);
    echo "Admin user created with id={$id}, login={$login}\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
