<?php

declare(strict_types=1);

/** @var array $article */
?>
<article class="article-card reveal">
  <h2 class="article-card-title">
    <a href="<?= htmlspecialchars(article_url($article['link']), ENT_QUOTES, 'UTF-8') ?>">
      <?= htmlspecialchars($article['description'], ENT_QUOTES, 'UTF-8') ?>
    </a>
  </h2>
  <p class="article-card-meta">
    <span><?= htmlspecialchars($article['author'], ENT_QUOTES, 'UTF-8') ?></span>
    <span><?= htmlspecialchars(format_article_date($article['published_at']), ENT_QUOTES, 'UTF-8') ?></span>
  </p>
  <p class="article-card-excerpt">
    <?= htmlspecialchars(mb_substr(strip_tags($article['text']), 0, 220), ENT_QUOTES, 'UTF-8') ?>…
  </p>
  <a class="article-card-link" href="<?= htmlspecialchars(article_url($article['link']), ENT_QUOTES, 'UTF-8') ?>">
    Читать статью
  </a>
</article>
