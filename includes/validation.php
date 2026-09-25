<?php

declare(strict_types=1);

function parse_contact(string $contact): array
{
    $contact = trim($contact);

    if ($contact === '') {
        return ['phone' => '', 'email' => null];
    }

    if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
        return ['phone' => $contact, 'email' => $contact];
    }

    if (preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $contact)) {
        return ['phone' => $contact, 'email' => $contact];
    }

    return ['phone' => $contact, 'email' => null];
}

function validate_feedback_input(array $body): array
{
    $name = trim((string) ($body['name'] ?? ''));
    $contact = trim((string) ($body['contact'] ?? ''));
    $message = trim((string) ($body['message'] ?? ''));

    if ($name === '' || mb_strlen($name) > 100) {
        return ['error' => 'Укажите имя (до 100 символов).'];
    }

    if ($contact === '' || mb_strlen($contact) > 120) {
        return ['error' => 'Укажите телефон или email (до 120 символов).'];
    }

    if (mb_strlen($message) > 2000) {
        return ['error' => 'Комментарий слишком длинный (до 2000 символов).'];
    }

    if ($message === '') {
        $message = '—';
    }

    $parsed = parse_contact($contact);

    return [
        'name' => $name,
        'phone' => $parsed['phone'],
        'email' => $parsed['email'],
        'message' => $message,
    ];
}
