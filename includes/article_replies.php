<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/articles.php';

function validate_article_reply_input(array $body): array
{
    $articleId = (int) ($body['article_id'] ?? 0);
    $name = trim((string) ($body['name'] ?? ''));
    $email = trim((string) ($body['email'] ?? ''));
    $message = trim((string) ($body['message'] ?? ''));

    if ($articleId <= 0) {
        return ['error' => 'Укажите статью.'];
    }

    if ($name === '' || mb_strlen($name) > 100) {
        return ['error' => 'Укажите имя (до 100 символов).'];
    }

    if ($email === '' || mb_strlen($email) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['error' => 'Укажите корректный email.'];
    }

    if ($message === '' || mb_strlen($message) > 2000) {
        return ['error' => 'Напишите сообщение (до 2000 символов).'];
    }

    return [
        'article_id' => $articleId,
        'name' => $name,
        'email' => $email,
        'message' => $message,
    ];
}

function fetch_replies_by_article_id(int $articleId): array
{
    if ($articleId <= 0) {
        return [];
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT id, article_id, name, email, message, created_at
         FROM article_replies
         WHERE article_id = :article_id
         ORDER BY id ASC'
    );
    $stmt->execute([':article_id' => $articleId]);

    return $stmt->fetchAll();
}

function save_article_reply(int $articleId, string $name, string $email, string $message): array
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO article_replies (article_id, name, email, message)
         VALUES (:article_id, :name, :email, :message)'
    );
    $stmt->execute([
        ':article_id' => $articleId,
        ':name' => $name,
        ':email' => $email,
        ':message' => $message,
    ]);

    $id = (int) $pdo->lastInsertId();
    $row = $pdo->prepare(
        'SELECT id, article_id, name, email, message, created_at
         FROM article_replies
         WHERE id = :id'
    );
    $row->execute([':id' => $id]);

    $reply = $row->fetch();
    if (!$reply) {
        throw new RuntimeException('Saved reply row was not found.');
    }

    return $reply;
}

function format_reply_datetime(string $datetime): string
{
    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return $datetime;
    }

    return date('d.m.Y H:i', $timestamp);
}

function public_reply_payload(array $reply): array
{
    return [
        'id' => (int) $reply['id'],
        'article_id' => (int) $reply['article_id'],
        'name' => $reply['name'],
        'message' => $reply['message'],
        'created_at' => $reply['created_at'],
        'created_at_label' => format_reply_datetime($reply['created_at']),
    ];
}
