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
    $driver = db_driver($config);

    if ($driver === 'mysql') {
        $pdo = create_mysql_connection($config);
        init_mysql_schema($pdo);
    } else {
        $pdo = create_sqlite_connection($config);
        init_sqlite_schema($pdo);
    }

    return $pdo;
}

function create_mysql_connection(array $config): PDO
{
    $host = (string) ($config['db_host'] ?? '');
    $port = (int) ($config['db_port'] ?? 3306);
    $name = (string) ($config['db_name'] ?? '');
    $user = (string) ($config['db_user'] ?? '');
    $pass = (string) ($config['db_pass'] ?? '');
    $charset = (string) ($config['db_charset'] ?? 'utf8mb4');

    if ($host === '' || $name === '' || $user === '') {
        throw new RuntimeException('MySQL is not configured: set db_host, db_name and db_user in config.local.php');
    }

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $name, $charset);

    if (!extension_loaded('pdo_mysql')) {
        throw new RuntimeException('PDO MySQL is not enabled on hosting. Enable pdo_mysql in ISPmanager → PHP → Расширения.');
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $charset;
    }

    return new PDO($dsn, $user, $pass, $options);
}

function create_sqlite_connection(array $config): PDO
{
    $dbPath = (string) ($config['db_path'] ?? '');
    if ($dbPath === '') {
        throw new RuntimeException('SQLite db_path is not configured.');
    }

    $dir = dirname($dbPath);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create database directory.');
    }

    return new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function init_mysql_schema(PDO $pdo): void
{
    $schemaPath = dirname(__DIR__) . '/database/schema.mysql.sql';
    if (!is_file($schemaPath)) {
        throw new RuntimeException('MySQL schema file not found.');
    }

    $sql = file_get_contents($schemaPath);
    if ($sql === false) {
        throw new RuntimeException('Unable to read MySQL schema file.');
    }

    foreach (split_sql_statements($sql) as $statement) {
        $pdo->exec($statement);
    }
}

function init_sqlite_schema(PDO $pdo): void
{
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

    ensure_sqlite_articles_category_column($pdo);

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS article_images (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            article_id INTEGER NOT NULL,
            filename TEXT NOT NULL,
            original_name TEXT NOT NULL,
            caption TEXT NOT NULL DEFAULT \'\',
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\', \'localtime\')),
            FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE INDEX IF NOT EXISTS idx_article_images_article_id
         ON article_images (article_id)'
    );

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

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            login TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            name TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\', \'localtime\'))
        )'
    );
}

function ensure_sqlite_articles_category_column(PDO $pdo): void
{
    $columns = $pdo->query('PRAGMA table_info(articles)')->fetchAll();
    foreach ($columns as $column) {
        if (($column['name'] ?? '') === 'category_id') {
            return;
        }
    }

    $pdo->exec('ALTER TABLE articles ADD COLUMN category_id INTEGER REFERENCES article_categories(id)');
}

function split_sql_statements(string $sql): array
{
    $statements = [];
    $buffer = '';

    foreach (preg_split('/\R/u', $sql) as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '--')) {
            continue;
        }

        $buffer .= $line . PHP_EOL;
        if (str_ends_with(rtrim($line), ';')) {
            $statements[] = trim($buffer);
            $buffer = '';
        }
    }

    if (trim($buffer) !== '') {
        $statements[] = trim($buffer);
    }

    return $statements;
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
