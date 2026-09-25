<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/db.php';
require_once __DIR__ . '/users.php';

function admin_session_start(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function is_admin_logged_in(): bool
{
    admin_session_start();
    return isset($_SESSION['admin_user_id']) && (int) $_SESSION['admin_user_id'] > 0;
}

function current_admin(): ?array
{
    if (!is_admin_logged_in()) {
        return null;
    }

    return fetch_admin_user_by_id((int) $_SESSION['admin_user_id']);
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function admin_login(string $login, string $password): bool
{
    $user = fetch_admin_user_by_login(trim($login));
    if ($user === null || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    admin_session_start();
    session_regenerate_id(true);
    $_SESSION['admin_user_id'] = (int) $user['id'];

    return true;
}

function admin_logout(): void
{
    admin_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function csrf_token(): string
{
    admin_session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    admin_session_start();
    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function admin_url(string $path = ''): string
{
    return '/admin/' . ltrim($path, '/');
}
