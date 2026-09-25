<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/categories.php';
require_once __DIR__ . '/includes/layout.php';

$tree = fetch_category_tree();
$hasArticles = fetch_all_articles() !== [];

render_page_start('Статьи — ИнтелАв', 'Материалы о наших доработках и решениях для клиентов на базе 1С.');
render_header('articles');
?>
  <main>
    <section class="section section-alt articles-page">
      <div class="container">
        <header class="section-head reveal">
          <p class="eyebrow">Практика</p>
          <h1>Статьи</h1>
          <p class="section-lead">Материалы о наших доработках и решениях для клиентов.</p>
        </header>

        <?php if (!$hasArticles): ?>
          <div class="articles-empty reveal">
            <p>Пока нет опубликованных статей. Добавьте первую запись в базу.</p>
          </div>
        <?php else: ?>
          <div class="articles-tree">
            <?php foreach ($tree as $section): ?>
              <section class="articles-tree-group reveal" id="<?= htmlspecialchars($section['slug'], ENT_QUOTES, 'UTF-8') ?>">
                <header class="articles-tree-group-head">
                  <h2><?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                </header>

                <?php if ($section['articles'] === []): ?>
                  <p class="articles-tree-empty">В этом разделе пока нет статей.</p>
                <?php else: ?>
                  <div class="articles-grid">
                    <?php foreach ($section['articles'] as $article): ?>
                      <?php require __DIR__ . '/includes/partials/article-card.php'; ?>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </section>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>
<?php
render_page_end();
