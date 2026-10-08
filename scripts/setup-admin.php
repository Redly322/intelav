<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/security.php';
require_cli();
require_once dirname(__DIR__) . '/includes/admin/users.php';

$login = $argv[1] ?? '';
$password = $argv[2] ?? '';
$name = $argv[3] ?? '';

if ($login === '' || $password === '' || $name === '') {
    fwrite(STDERR, "Usage: php scripts/setup-admin.php <login> <password> \"<name>\"\n");
    exit(1);
}

try {
    $id = create_admin_user($login, $password, $name);
    echo "Admin created: id={$id}, login={$login}\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
