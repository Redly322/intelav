<?php

declare(strict_types=1);

function article_looks_like_html(string $text): bool
{
    return (bool) preg_match('/<(p|h[1-6]|ul|ol|li|figure|div|span|br|strong|em|b|i|table|thead|tbody|tr|th|td)\b/i', $text);
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
    $plain = article_looks_like_html($text) ? article_html_to_plain($text) : $text;

    return article_plain_to_html($plain);
}

function article_excerpt(string $text, int $limit = 220): string
{
    $plain = article_edit_plain($text);
    $plain = preg_replace('/\[картинка:[^\]]+\]/u', '', $plain) ?? $plain;
    $plain = preg_replace('/^#{1,3}\s+/mu', '', $plain) ?? $plain;
    $plain = preg_replace('/\*\*(.+?)\*\*/u', '$1', $plain) ?? $plain;
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

    $html = preg_replace_callback(
        '#<table\b[^>]*>.*?</table>#is',
        static function (array $match): string {
            return article_html_table_to_plain($match[0]);
        },
        $html
    ) ?? $html;

    $html = preg_replace_callback(
        '#<h[1-6]\b[^>]*>(.*?)</h[1-6]>#is',
        static function (array $match): string {
            $title = trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            return $title === '' ? "\n\n" : "\n\n## " . $title . "\n\n";
        },
        $html
    ) ?? $html;

    $html = preg_replace_callback(
        '#<(strong|b)\b[^>]*>(.*?)</\1>#is',
        static function (array $match): string {
            $inner = trim(html_entity_decode(strip_tags($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            return $inner === '' ? '' : '**' . $inner . '**';
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
    $table = [];

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

    $flushTable = static function () use (&$blocks, &$table): void {
        if ($table === []) {
            return;
        }
        $html = article_table_lines_to_html($table);
        if ($html !== '') {
            $blocks[] = $html;
        }
        $table = [];
    };

    foreach (explode("\n", $plain) as $rawLine) {
        $line = rtrim($rawLine, " \t");
        $trimmed = trim($line);
        if ($trimmed === '') {
            $flushList();
            $flushTable();
            continue;
        }

        if (preg_match('/^\[картинка:\s*(.+?)\]\s*$/u', $trimmed, $match)) {
            $flushList();
            $flushTable();
            $blocks[] = article_placeholder_to_figure($match[1]);
            continue;
        }

        if (preg_match('/^[-•]\s+(.+)$/u', $trimmed, $match)) {
            $flushTable();
            $list[] = $match[1];
            continue;
        }

        if (article_is_table_line($line)) {
            $flushList();
            $table[] = $line;
            continue;
        }

        if (preg_match('/^#{1,3}\s+(.+)$/u', $trimmed, $match)) {
            $flushList();
            $flushTable();
            $blocks[] = '<h3>' . article_inline_html($match[1]) . '</h3>';
            continue;
        }

        $flushList();
        $flushTable();
        $blocks[] = '<p>' . article_inline_html($trimmed) . '</p>';
    }
    $flushList();
    $flushTable();

    return implode("\n", $blocks);
}

function article_is_table_separator(string $line): bool
{
    $line = trim($line);

    return (bool) preg_match('/^\|?[\s:\-|]+\|[\s:\-|]*\|?$/u', $line);
}

function article_is_table_line(string $line): bool
{
    if (article_is_table_separator($line)) {
        return true;
    }
    if (str_contains($line, "\t")) {
        return true;
    }

    $line = trim($line);

    return (bool) preg_match('/^\|.+\|$/u', $line);
}

function article_split_table_row(string $line): array
{
    if (article_is_table_separator($line)) {
        return [];
    }

    if (str_contains($line, "\t")) {
        $cells = explode("\t", $line);
    } else {
        $line = trim($line);
        $line = trim($line, '|');
        $cells = explode('|', $line);
    }

    return array_map(static fn (string $cell): string => trim($cell), $cells);
}

function article_table_lines_to_html(array $lines): string
{
    $rows = [];
    foreach ($lines as $line) {
        $cells = article_split_table_row((string) $line);
        if ($cells === []) {
            continue;
        }
        $rows[] = $cells;
    }
    if ($rows === []) {
        return '';
    }

    $width = 0;
    foreach ($rows as $cells) {
        $width = max($width, count($cells));
    }

    $html = '<div class="table-wrap"><table class="blog-table">';
    foreach ($rows as $index => $cells) {
        while (count($cells) < $width) {
            $cells[] = '';
        }
        $tag = $index === 0 ? 'th' : 'td';
        $html .= '<tr>';
        foreach ($cells as $cell) {
            $html .= '<' . $tag . '>' . article_inline_html($cell) . '</' . $tag . '>';
        }
        $html .= '</tr>';
    }
    $html .= '</table></div>';

    return $html;
}

function article_html_table_to_plain(string $tableHtml): string
{
    if (!preg_match_all('#<tr\b[^>]*>(.*?)</tr>#is', $tableHtml, $rowMatches)) {
        return "\n\n";
    }

    $lines = [];
    foreach ($rowMatches[1] as $rowHtml) {
        if (!preg_match_all('#<t[dh]\b[^>]*>(.*?)</t[dh]>#is', $rowHtml, $cellMatches)) {
            continue;
        }
        $cells = [];
        foreach ($cellMatches[1] as $cellHtml) {
            $cell = html_entity_decode(strip_tags($cellHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $cell = trim((string) preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $cell)));
            $cell = str_replace('|', '/', $cell);
            $cells[] = $cell;
        }
        if ($cells !== []) {
            $lines[] = '| ' . implode(' | ', $cells) . ' |';
        }
    }

    if ($lines === []) {
        return "\n\n";
    }

    return "\n\n" . implode("\n", $lines) . "\n\n";
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
        return '';
    }

    $src = article_resolve_public_src($src);
    if ($src === null) {
        return '';
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

function article_resolve_public_src(string $src): ?string
{
    $path = parse_url($src, PHP_URL_PATH);
    $rel = ltrim(is_string($path) && $path !== '' ? $path : $src, '/');
    $full = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (is_file($full)) {
        return '/' . $rel;
    }

    if (!preg_match('#^uploads/articles/(\d+)/([^/]+)$#', $rel, $match)) {
        return null;
    }

    $articleId = $match[1];
    $filename = rawurldecode($match[2]);
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'articles' . DIRECTORY_SEPARATOR . $articleId;
    if (!is_dir($dir) || !preg_match('/^[a-f0-9]{16}-(.+)$/i', $filename, $suffixMatch)) {
        return null;
    }

    $suffix = $suffixMatch[1];
    $items = scandir($dir);
    if ($items === false) {
        return null;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        if (preg_match('/^[a-f0-9]{16}-' . preg_quote($suffix, '/') . '$/i', $item) === 1) {
            return '/uploads/articles/' . $articleId . '/' . rawurlencode($item);
        }
    }

    return null;
}

function article_inline_html(string $text): string
{
    $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $safe = (string) preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $safe);

    return (string) preg_replace(
        '/(?<![\p{L}\p{N}])(1[СC])(?![\p{L}\p{N}])/u',
        '<span class="mark-1c">$1</span>',
        $safe
    );
}
