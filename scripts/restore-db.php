<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/security.php';
require_cli();
require_once dirname(__DIR__) . '/includes/backup.php';

$file = $argv[1] ?? '';
if ($file === '' || !is_file($file)) {
    fwrite(STDERR, "Usage: php scripts/restore-db.php /path/to/uchet-YYYYMMDD-HHMMSS.sql.gz\n");
    exit(1);
}

if (!preg_match('/\.sql\.gz$/', $file)) {
    fwrite(STDERR, "Only gzipped SQL dumps produced by backup-db.php are supported.\n");
    exit(1);
}

$sql = gzfile($file);
if ($sql === false) {
    fwrite(STDERR, "Unable to read dump.\n");
    exit(1);
}

$pdo = db();
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
$buffer = '';

try {
    foreach ($sql as $line) {
        $trim = ltrim($line);
        if ($trim === '' || str_starts_with($trim, '--')) {
            continue;
        }
        $buffer .= $line;
        if (!str_ends_with(rtrim($buffer), ';')) {
            continue;
        }
        $pdo->exec($buffer);
        $buffer = '';
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
} catch (Throwable $e) {
    fwrite(STDERR, 'Restore failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo "Restore complete: {$file}\n";
