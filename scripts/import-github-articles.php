<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/categories.php';

$sourcePath = dirname(__DIR__) . '/_github_index.html';
if (!is_file($sourcePath)) {
    fwrite(STDERR, "Source file not found: {$sourcePath}\n");
    exit(1);
}

$articleSlugs = [
    'fbs-marketplace-orders',
    'ozon-stock-export',
    'nsi-dimensions-load',
    'marking-batch-print',
    'buyout-return-ut',
    'diadoc-upd-scanarchive',
    'chestny-znak-xml-import',
    'kpi-plan-correction',
    'budgeting-report',
];

$articleDates = [
    '2025-09-15',
    '2025-09-01',
    '2025-08-25',
    '2025-08-15',
    '2025-08-01',
    '2025-07-20',
    '2025-07-10',
    '2025-06-25',
    '2025-06-10',
];

$parentCategories = [
    ['slug' => 'commerce', 'title' => 'Коммерция и продажи', 'sort_order' => 1],
    ['slug' => 'accounting', 'title' => 'Учёт и отчётность', 'sort_order' => 2],
    ['slug' => 'marking-compliance', 'title' => 'Маркировка и комплаенс', 'sort_order' => 3],
];

$categoryParents = [
    'marketplace' => 'commerce',
    'sales' => 'commerce',
    'nsi' => 'accounting',
    'management-accounting' => 'accounting',
    'budgeting' => 'accounting',
    'marking' => 'marking-compliance',
    'chestny-znak' => 'marking-compliance',
];

$html = file_get_contents($sourcePath);
if ($html === false) {
    fwrite(STDERR, "Unable to read source HTML.\n");
    exit(1);
}

$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML('<?xml encoding="utf-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
libxml_clear_errors();

$xpath = new DOMXPath($dom);
$sections = $xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' blog-subsection ')]");

if ($sections === false || $sections->length === 0) {
    fwrite(STDERR, "No blog sections found in source HTML.\n");
    exit(1);
}

function inner_html(DOMNode $node): string
{
    $html = '';
    foreach ($node->childNodes as $child) {
        $html .= $node->ownerDocument?->saveHTML($child) ?? '';
    }

    return trim($html);
}

$pdo = db();
$pdo->exec('DELETE FROM article_replies');
$pdo->exec('DELETE FROM articles');
$pdo->exec('DELETE FROM article_categories');

$parentIds = [];
foreach ($parentCategories as $index => $parent) {
    $parentIds[$parent['slug']] = insert_category(
        $parent['slug'],
        $parent['title'],
        null,
        $parent['sort_order'] ?? ($index + 1)
    );
}

$articleIndex = 0;
$categoryCount = 0;

foreach ($sections as $sectionIndex => $section) {
    if (!$section instanceof DOMElement) {
        continue;
    }

    $sectionId = $section->getAttribute('id');
    $categorySlug = preg_replace('/^blog-/', '', $sectionId) ?: ('section-' . ($sectionIndex + 1));
    $parentSlug = $categoryParents[$categorySlug] ?? null;
    $parentId = $parentSlug !== null ? ($parentIds[$parentSlug] ?? null) : null;

    $titleNode = $xpath->query(".//h3[contains(concat(' ', normalize-space(@class), ' '), ' blog-subhead ')]", $section)->item(0);
    $categoryTitle = trim($titleNode?->textContent ?? ucfirst(str_replace('-', ' ', $categorySlug)));

    $categoryId = insert_category(
        $categorySlug,
        $categoryTitle,
        $parentId,
        $sectionIndex + 1
    );
    $categoryCount++;

    $articleNodes = $xpath->query(".//article[contains(concat(' ', normalize-space(@class), ' '), ' blog-article ')]", $section);
    if ($articleNodes === false) {
        continue;
    }

    foreach ($articleNodes as $node) {
        if (!$node instanceof DOMElement || !isset($articleSlugs[$articleIndex])) {
            continue;
        }

        $titleNode = null;
        $leadNode = null;
        $bodyNode = null;

        foreach ($node->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }

            if ($child->tagName === 'h4' && str_contains($child->getAttribute('class'), 'blog-article-title')) {
                $titleNode = $child;
            }

            if ($child->tagName === 'p' && str_contains($child->getAttribute('class'), 'blog-lead')) {
                $leadNode = $child;
            }

            if ($child->tagName === 'div' && str_contains($child->getAttribute('class'), 'blog-body')) {
                $bodyNode = $child;
            }
        }

        if (!$titleNode || !$bodyNode) {
            fwrite(STDERR, "Article #{$articleIndex} is missing title or body.\n");
            exit(1);
        }

        $description = trim(preg_replace('/\s+/u', ' ', $titleNode->textContent) ?? '');
        $text = inner_html($bodyNode);

        if ($leadNode instanceof DOMElement) {
            $leadHtml = trim($dom->saveHTML($leadNode));
            $text = $leadHtml . "\n" . $text;
        }

        insert_article(
            $articleSlugs[$articleIndex],
            $description,
            'Команда ИнтелАв',
            $articleDates[$articleIndex],
            $text,
            $categoryId
        );

        $articleIndex++;
    }
}

if ($articleIndex !== count($articleSlugs)) {
    fwrite(STDERR, "Imported {$articleIndex} articles, expected " . count($articleSlugs) . ".\n");
    exit(1);
}

echo "Imported {$categoryCount} categories and {$articleIndex} articles into SQLite.\n";
