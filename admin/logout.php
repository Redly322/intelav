<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin/auth.php';

admin_logout();
header('Location: /admin/login.php');
exit;
