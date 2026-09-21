<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/article_replies.php';
require_once dirname(__DIR__) . '/includes/events.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $articleId = (int) ($_GET['article_id'] ?? 0);
    $article = fetch_article_by_id($articleId);

    if ($article === null) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Статья не найдена.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $replies = array_map(
        static fn(array $reply): array => public_reply_payload($reply),
        fetch_replies_by_article_id($articleId)
    );

    echo json_encode(['ok' => true, 'items' => $replies], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input') ?: '';
$body = json_decode($raw, true);
if (!is_array($body)) {
    $body = $_POST;
}

$data = validate_article_reply_input($body);
if (isset($data['error'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $data['error']], JSON_UNESCAPED_UNICODE);
    exit;
}

$article = fetch_article_by_id($data['article_id']);
if ($article === null) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Статья не найдена.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $reply = save_article_reply(
        $data['article_id'],
        $data['name'],
        $data['email'],
        $data['message']
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Не удалось сохранить ответ. Попробуйте позже.'], JSON_UNESCAPED_UNICODE);
    exit;
}

dispatch_article_reply_created($reply, $article);

http_response_code(201);
echo json_encode([
    'ok' => true,
    'message' => 'Ответ добавлен.',
    'item' => public_reply_payload($reply),
], JSON_UNESCAPED_UNICODE);
