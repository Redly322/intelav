<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/articles.php';

$sourcePath = dirname(__DIR__) . '/_github_index.html';
if (!is_file($sourcePath)) {
    fwrite(STDERR, "Run import after placing GitHub index.html as _github_index.html\n");
    exit(1);
}

require __DIR__ . '/import-github-articles.php';
