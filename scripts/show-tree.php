<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/categories.php';

foreach (fetch_category_tree() as $group) {
    echo $group['title'] . PHP_EOL;
    foreach ($group['children'] as $child) {
        echo '  - ' . $child['title'] . ' (' . count($child['articles']) . ')' . PHP_EOL;
    }
}
