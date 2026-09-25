<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/db.php';

function fetch_all_admin_users(): array
{
    $pdo = db();
    $stmt = $pdo->query(
        'SELECT id, login, name, created_at FROM admin_users ORDER BY id ASC'
    );

    return $stmt->fetchAll();
}

function fetch_admin_user_by_id(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT id, login, password_hash, name, created_at FROM admin_users WHERE id = :id LIMIT 1'
    );
    $stmt->execute([':id' => $id]);

    $user = $stmt->fetch();
    return $user ?: null;
}

function fetch_admin_user_by_login(string $login): ?array
{
    $login = trim($login);
    if ($login === '') {
        return null;
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT id, login, password_hash, name, created_at FROM admin_users WHERE login = :login LIMIT 1'
    );
    $stmt->execute([':login' => $login]);

    $user = $stmt->fetch();
    return $user ?: null;
}

function create_admin_user(string $login, string $password, string $name): int
{
    $login = trim($login);
    $name = trim($name);

    if ($login === '' || mb_strlen($login) > 60) {
        throw new InvalidArgumentException('Логин обязателен (до 60 символов).');
    }

    if ($password === '' || mb_strlen($password) < 8) {
        throw new InvalidArgumentException('Пароль должен быть не короче 8 символов.');
    }

    if ($name === '' || mb_strlen($name) > 100) {
        throw new InvalidArgumentException('Имя обязательно (до 100 символов).');
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO admin_users (login, password_hash, name) VALUES (:login, :password_hash, :name)'
    );
    $stmt->execute([
        ':login' => $login,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':name' => $name,
    ]);

    return (int) $pdo->lastInsertId();
}

function delete_admin_user(int $id): void
{
    if ($id <= 0) {
        throw new InvalidArgumentException('Invalid admin user id.');
    }

    $pdo = db();
    $stmt = $pdo->prepare('DELETE FROM admin_users WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function count_admin_users(): int
{
    $pdo = db();
    return (int) $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
}
