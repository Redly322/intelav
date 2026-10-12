<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/security.php';
require_cli();
require_once dirname(__DIR__) . '/includes/backup.php';

$command = $argv[1] ?? 'run';

try {
    if ($command === 'install-cron') {
        echo install_backup_cron() . PHP_EOL;
        exit(0);
    }

    $path = create_database_backup();
    echo 'Backup written: ' . $path . PHP_EOL;
    echo 'Keep days: ' . backup_keep_days() . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Backup failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
