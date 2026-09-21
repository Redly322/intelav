<?php

declare(strict_types=1);

return [
    'smtp_host' => 'smtp.yandex.ru',
    'smtp_port' => 465,
    'smtp_secure' => true,
    'smtp_user' => 'your-mail@yandex.ru',
    'smtp_pass' => 'your-app-password',
    'mail_to' => 'info@intelav.ru',
    'mail_from' => 'your-mail@yandex.ru',

    // sqlite — для локальной разработки
    // mysql — для хостинга
    'db_driver' => 'sqlite',
    'db_path' => __DIR__ . '/data/feedback.db',

    'db_host' => '127.0.0.1',
    'db_port' => 3306,
    'db_name' => 'intelav',
    'db_user' => 'intelav_user',
    'db_pass' => 'your-db-password',
    'db_charset' => 'utf8mb4',
];
