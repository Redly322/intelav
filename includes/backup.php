<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function backup_keep_days(): int
{
    $days = (int) (app_config()['backup_keep_days'] ?? 30);

    return max(7, min(365, $days));
}

function backup_directory(): string
{
    $configured = trim((string) (app_config()['backup_dir'] ?? ''));
    if ($configured !== '') {
        return rtrim($configured, '/\\');
    }

    $outsideWeb = dirname(__DIR__, 3) . '/backup/uchet.info';
    if (backup_directory_usable($outsideWeb) || backup_try_mkdir($outsideWeb)) {
        return $outsideWeb;
    }

    $insideSite = dirname(__DIR__) . '/data/backups';
    backup_try_mkdir($insideSite);

    return $insideSite;
}

function backup_directory_usable(string $dir): bool
{
    return is_dir($dir) && is_writable($dir);
}

function backup_try_mkdir(string $dir): bool
{
    if (is_dir($dir)) {
        return is_writable($dir);
    }

    return @mkdir($dir, 0750, true) && is_dir($dir);
}

function create_database_backup(): string
{
    $dir = backup_directory();
    if (!backup_try_mkdir($dir)) {
        throw new RuntimeException('Не удалось создать каталог бэкапов: ' . $dir);
    }

    $stamp = date('Ymd-His');
    $config = app_config();
    $driver = db_driver($config);

    if ($driver === 'mysql') {
        $sql = dump_mysql_database();
        $path = $dir . '/uchet-' . $stamp . '.sql.gz';
        $gz = gzopen($path, 'wb9');
        if ($gz === false) {
            throw new RuntimeException('Не удалось записать бэкап: ' . $path);
        }
        gzwrite($gz, $sql);
        gzclose($gz);
    } else {
        $dbPath = (string) ($config['db_path'] ?? '');
        if ($dbPath === '' || !is_file($dbPath)) {
            throw new RuntimeException('SQLite-файл базы не найден.');
        }
        $path = $dir . '/uchet-' . $stamp . '.sqlite.gz';
        $data = file_get_contents($dbPath);
        if ($data === false) {
            throw new RuntimeException('Не удалось прочитать SQLite.');
        }
        if (file_put_contents($path, gzencode($data, 9)) === false) {
            throw new RuntimeException('Не удалось записать бэкап: ' . $path);
        }
    }

    chmod($path, 0640);
    rotate_database_backups();

    return $path;
}

function rotate_database_backups(): void
{
    $dir = backup_directory();
    $keepAfter = time() - (backup_keep_days() * 86400);
    $files = glob($dir . '/uchet-*.{sql.gz,sqlite.gz}', GLOB_BRACE) ?: [];

    foreach ($files as $file) {
        $mtime = filemtime($file);
        if ($mtime !== false && $mtime < $keepAfter) {
            @unlink($file);
        }
    }
}

function dump_mysql_database(): string
{
    $pdo = db();
    $config = app_config();
    $name = (string) $config['db_name'];
    $charset = (string) ($config['db_charset'] ?? 'utf8mb4');

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $sql = "-- uchet.info backup " . date('c') . "\n";
    $sql .= "-- database: {$name}\n";
    $sql .= "SET NAMES {$charset};\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $sql .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";

    foreach ($tables as $table) {
        if (!is_string($table) || !preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            continue;
        }

        $create = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch(PDO::FETCH_ASSOC);
        $createSql = (string) ($create['Create Table'] ?? '');
        if ($createSql === '') {
            continue;
        }

        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= $createSql . ";\n\n";

        $rows = $pdo->query('SELECT * FROM `' . $table . '`');
        $buffer = [];
        while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
            $values = [];
            foreach ($row as $value) {
                if ($value === null) {
                    $values[] = 'NULL';
                } else {
                    $values[] = $pdo->quote((string) $value);
                }
            }
            $buffer[] = '(' . implode(',', $values) . ')';
            if (count($buffer) >= 100) {
                $sql .= insert_sql($table, $buffer);
                $buffer = [];
            }
        }
        if ($buffer !== []) {
            $sql .= insert_sql($table, $buffer);
        }
        $sql .= "\n";
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

    return $sql;
}

function insert_sql(string $table, array $rows): string
{
    return 'INSERT INTO `' . $table . '` VALUES ' . implode(",\n", $rows) . ";\n";
}

function backup_cron_line(): string
{
    $php = PHP_BINARY !== '' ? PHP_BINARY : 'php';
    $script = dirname(__DIR__) . '/scripts/backup-db.php';

    return '20 3 * * * ' . $php . ' ' . escapeshellarg($script) . ' >/dev/null 2>&1';
}

function install_backup_cron(): string
{
    $line = backup_cron_line();
    $current = '';
    $exit = 0;
    exec('crontab -l 2>/dev/null', $lines, $exit);
    $current = implode("\n", $lines);
    if (str_contains($current, 'scripts/backup-db.php')) {
        return 'Cron already installed.';
    }

    $next = trim($current);
    $next = ($next === '' ? $line : $next . "\n" . $line) . "\n";
    $tmp = tempnam(sys_get_temp_dir(), 'cron');
    if ($tmp === false || file_put_contents($tmp, $next) === false) {
        throw new RuntimeException('Не удалось подготовить crontab.');
    }

    exec('crontab ' . escapeshellarg($tmp), $out, $cronExit);
    @unlink($tmp);
    if ($cronExit !== 0) {
        throw new RuntimeException("Не удалось записать crontab. Добавьте вручную:\n" . $line);
    }

    return 'Cron installed: ' . $line;
}
