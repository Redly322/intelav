<?php

declare(strict_types=1);

function app_config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $defaults = require dirname(__DIR__) . '/config.example.php';
    $localPath = dirname(__DIR__) . '/config.local.php';

    if (is_file($localPath)) {
        $local = require $localPath;
        if (!is_array($local)) {
            throw new RuntimeException('config.local.php must return an array.');
        }
        $config = array_merge($defaults, $local);
    } else {
        $config = $defaults;
    }

    return $config;
}

function mail_is_configured(array $config): bool
{
    return !empty($config['smtp_host']) && !empty($config['mail_to']);
}

function db_driver(array $config): string
{
    $driver = strtolower((string) ($config['db_driver'] ?? 'sqlite'));

    if ($driver !== 'mysql') {
        return 'sqlite';
    }

    if (empty($config['db_host']) || empty($config['db_name']) || empty($config['db_user'])) {
        return 'sqlite';
    }

    return 'mysql';
}
