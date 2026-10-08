<?php

declare(strict_types=1);

function client_request_ip(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function rate_limit_exceeded(string $bucket, int $maxAttempts, int $windowSeconds): bool
{
    $dir = dirname(__DIR__) . '/data/rate-limit';
    if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
        return true;
    }

    $file = $dir . '/' . hash('sha256', $bucket . '|' . client_request_ip()) . '.json';
    $now = time();
    $hits = [];

    if (is_file($file)) {
        $decoded = json_decode((string) file_get_contents($file), true);
        if (is_array($decoded)) {
            foreach ($decoded as $ts) {
                if (is_numeric($ts)) {
                    $hits[] = (int) $ts;
                }
            }
        }
    }

    $hits = array_values(array_filter(
        $hits,
        static fn (int $ts): bool => ($now - $ts) < $windowSeconds
    ));

    if (count($hits) >= $maxAttempts) {
        return true;
    }

    $hits[] = $now;
    file_put_contents($file, json_encode($hits), LOCK_EX);

    return false;
}
