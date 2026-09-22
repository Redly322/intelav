<?php

declare(strict_types=1);

// Скопируйте на хостинг как config.local.php
return [
    'smtp_host' => 'smtp.yandex.ru',
    'smtp_port' => 465,
    'smtp_secure' => true,
    'smtp_user' => 'zaharov.vkontakte@yandex.ru',
    'smtp_pass' => 'your-app-password',
    'mail_to' => 'redly62@gmail.com',
    'mail_from' => 'zaharov.vkontakte@yandex.ru',

    'db_driver' => 'mysql',
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => 'p668753_sqllite',
    'db_user' => 'p668753_p668753',
    'db_pass' => 'your-db-password',
    'db_charset' => 'utf8mb4',
];
