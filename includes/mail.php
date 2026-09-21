<?php

declare(strict_types=1);

function build_mail_text(array $feedback): string
{
    return implode("\n", [
        'Новая заявка с сайта ИнтелАв',
        '',
        'ID: ' . $feedback['id'],
        'Имя: ' . $feedback['name'],
        'Телефон: ' . $feedback['phone'],
        'Email: ' . ($feedback['email'] ?: 'не указан'),
        '',
        'Сообщение:',
        $feedback['message'],
        '',
        'Дата: ' . $feedback['created_at'],
    ]);
}

function send_feedback_email(array $feedback): bool
{
    $config = app_config();

    if (!mail_is_configured($config)) {
        return false;
    }

    $host = (string) $config['smtp_host'];
    $port = (int) ($config['smtp_port'] ?? 465);
    $secure = (bool) ($config['smtp_secure'] ?? true);
    $user = (string) ($config['smtp_user'] ?? '');
    $pass = (string) ($config['smtp_pass'] ?? '');
    $to = (string) $config['mail_to'];
    $from = (string) ($config['mail_from'] ?: $user ?: $to);
    $subject = 'Новая заявка с сайта: ' . $feedback['name'];
    $body = build_mail_text($feedback);
    $replyTo = $feedback['email'] ?: null;

    $transport = $secure ? 'ssl://' . $host : $host;
    $socket = @stream_socket_client(
        $transport . ':' . $port,
        $errno,
        $errstr,
        20,
        STREAM_CLIENT_CONNECT
    );

    if (!$socket) {
        throw new RuntimeException('SMTP connection failed: ' . $errstr);
    }

    stream_set_timeout($socket, 20);

    smtp_expect($socket, [220]);
    smtp_cmd($socket, 'EHLO intelav.local', [250]);

    if ($user !== '' && $pass !== '') {
        smtp_cmd($socket, 'AUTH LOGIN', [334]);
        smtp_cmd($socket, base64_encode($user), [334]);
        smtp_cmd($socket, base64_encode($pass), [235]);
    }

    smtp_cmd($socket, 'MAIL FROM:<' . $from . '>', [250]);
    smtp_cmd($socket, 'RCPT TO:<' . $to . '>', [250]);
    smtp_cmd($socket, 'DATA', [354]);

    $headers = [
        'From: =?UTF-8?B?' . base64_encode('ИнтелАв — сайт') . '?= <' . $from . '>',
        'To: <' . $to . '>',
        'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];

    if ($replyTo) {
        $headers[] = 'Reply-To: <' . $replyTo . '>';
    }

    $payload = implode("\r\n", $headers) . "\r\n\r\n" . str_replace(["\r\n", "\r"], "\n", $body);
    $payload = str_replace("\n.", "\n..", $payload);
    $payload = str_replace("\n", "\r\n", $payload);

    fwrite($socket, $payload . "\r\n.\r\n");
    smtp_expect($socket, [250]);
    smtp_cmd($socket, 'QUIT', [221]);
    fclose($socket);

    return true;
}

function smtp_cmd($socket, string $command, array $expectedCodes): void
{
    fwrite($socket, $command . "\r\n");
    smtp_expect($socket, $expectedCodes);
}

function smtp_expect($socket, array $expectedCodes): void
{
    $response = '';

    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }

    $code = (int) substr($response, 0, 3);
    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException('Unexpected SMTP response: ' . trim($response));
    }
}
