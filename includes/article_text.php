<?php

declare(strict_types=1);

function article_looks_like_html(string $text): bool
{
    return (bool) preg_match('/<(p|h[1-6]|ul|ol|li|figure|div|span|br|strong|em|b|i)\b/i', $text);
}

function article_edit_plain(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    if (article_looks_like_html($text)) {
        return article_html_to_plain($text);
    }

    return $text;
}

function article_normalize_for_storage(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    if (article_looks_like_html($text)) {
        return article_html_to_plain($text);
    }

    return trim($text);
}

function article_render_html(string $text): string
{
    if (article_looks_like_html($text)) {
        return $text;
    }

    return article_plain_to_html($text);
}

function article_excerpt(string $text, int $limit = 220): string
{
    $plain = article_edit_plain($text);
    $plain = preg_replace('/\[картинка:[^\]]+\]/u', '', $plain) ?? $plain;
    $plain = trim((string) preg_replace('/\s+/u', ' ', $plain));
    if ($plain === '') {
        return '';
    }
    if (mb_strlen($plain) <= $limit) {
        return $plain;
    }

    return rtrim(mb_substr($plain, 0, $limit), " \t.,;:") . '…';
}

function article_html_to_plain(string $html): string
{
    $html = str_replace(["\r\n", "\r"], "\n", $html);

    $html = preg_replace_callback(
        '#<figure\b[^>]*>.*?</figure>#is',
        static function (array $match): string {
            return article_html_image_to_placeholder($match[0]);
        },
        $html
    ) ?? $html;

    $html = preg_replace_callback(
        '#<img\b[^>]*>#i',
        static function (array $match): string {
            return article_html_image_to_placeholder($match[0]);
        },
        $html
    ) ?? $html;

    $html = preg_replace('#<br\s*/?>#i', "\n", $html) ?? $html;
    $html = preg_replace('#</(p|h[1-6])>#i', "\n\n", $html) ?? $html;
    $html = preg_replace('#</li>#i', "\n", $html) ?? $html;
    $html = preg_replace('#<li\b[^>]*>#i', '- ', $html) ?? $html;
    $html = preg_replace('#</(ul|ol)>#i', "\n\n", $html) ?? $html;
    $html = preg_replace('#<(p|h[1-6]|ul|ol|div)\b[^>]*>#i', "\n", $html) ?? $html;

    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace("\u{00A0}", ' ', $text);
    $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
    $text = preg_replace('/[ \t]*\n[ \t]*/u', "\n", $text) ?? $text;
    $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

    return trim($text);
}

function article_html_image_to_placeholder(string $html): string
{
    if (!preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $html, $srcMatch)) {
        return "\n\n";
    }

    $src = html_entity_decode($srcMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $caption = '';
    if (preg_match('#<figcaption\b[^>]*>(.*?)</figcaption>#is', $html, $captionMatch)) {
        $caption = trim(html_entity_decode(strip_tags($captionMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    } elseif (preg_match('/\balt\s*=\s*["\']([^"\']*)["\']/i', $html, $altMatch)) {
        $caption = trim(html_entity_decode($altMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    $line = '[картинка: ' . $src;
    if ($caption !== '') {
        $line .= ' | ' . str_replace(['[', ']'], '', $caption);
    }

    return "\n\n" . $line . "]\n\n";
}

function article_plain_to_html(string $plain): string
{
    $plain = trim(str_replace(["\r\n", "\r"], "\n", $plain));
    if ($plain === '') {
        return '';
    }

    $blocks = [];
    $list = [];

    $flushList = static function () use (&$blocks, &$list): void {
        if ($list === []) {
            return;
        }
        $html = "<ul>\n";
        foreach ($list as $item) {
            $html .= '<li>' . article_inline_html($item) . "</li>\n";
        }
        $html .= '</ul>';
        $blocks[] = $html;
        $list = [];
    };

    foreach (explode("\n", $plain) as $line) {
        $line = trim($line);
        if ($line === '') {
            $flushList();
            continue;
        }

        if (preg_match('/^\[картинка:\s*(.+?)\]\s*$/u', $line, $match)) {
            $flushList();
            $blocks[] = article_placeholder_to_figure($match[1]);
            continue;
        }

        if (preg_match('/^[-•]\s+(.+)$/u', $line, $match)) {
            $list[] = $match[1];
            continue;
        }

        $flushList();
        $blocks[] = '<p>' . article_inline_html($line) . '</p>';
    }
    $flushList();

    return implode("\n", $blocks);
}

function article_placeholder_to_figure(string $payload): string
{
    $parts = array_map('trim', explode('|', $payload, 2));
    $src = $parts[0];
    $caption = $parts[1] ?? '';

    if (
        $src === ''
        || preg_match('#^(javascript|data|vbscript):#i', $src) === 1
        || preg_match('#^(/|assets/|uploads/)#i', $src) !== 1
        || preg_match('/[\s<>"\']/', $src) === 1
    ) {
        return '<p>' . htmlspecialchars('[картинка: ' . $payload . ']', ENT_QUOTES, 'UTF-8') . '</p>';
    }

    $safeSrc = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
    $safeCaption = htmlspecialchars($caption, ENT_QUOTES, 'UTF-8');
    $alt = $safeCaption !== '' ? $safeCaption : 'изображение';

    $html = '<figure class="blog-figure">';
    $html .= '<img src="' . $safeSrc . '" alt="' . $alt . '" loading="lazy" />';
    if ($caption !== '') {
        $html .= '<figcaption>' . $safeCaption . '</figcaption>';
    }
    $html .= '</figure>';

    return $html;
}

function article_inline_html(string $text): string
{
    $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    return (string) preg_replace(
        '/(?<![\p{L}\p{N}])(1[СC])(?![\p{L}\p{N}])/u',
        '<span class="mark-1c">$1</span>',
        $safe
    );
}
