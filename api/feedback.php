<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/validation.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/mail.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
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

$data = validate_feedback_input($body);
if (isset($data['error'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $data['error']], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $feedback = save_feedback($data['name'], $data['phone'], $data['email'], $data['message']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Не удалось сохранить заявку. Попробуйте позже.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if (mail_is_configured(app_config())) {
        send_feedback_email($feedback);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Заявка сохранена, но не удалось отправить уведомление на почту. Мы уже работаем над этим.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(201);
echo json_encode([
    'ok' => true,
    'message' => 'Заявка отправлена. Мы свяжемся с вами в ближайшее время.',
    'id' => $feedback['id'],
], JSON_UNESCAPED_UNICODE);
