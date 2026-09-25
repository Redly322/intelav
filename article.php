<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/articles.php';
require_once __DIR__ . '/includes/article_replies.php';
require_once __DIR__ . '/includes/categories.php';
require_once __DIR__ . '/includes/layout.php';

$link = trim((string) ($_GET['link'] ?? ''));
$article = $link !== '' ? fetch_article_by_link($link) : null;

if ($article === null) {
    http_response_code(404);
    render_page_start('Статья не найдена — ИнтелАв');
    render_header('articles');
    ?>
  <main>
    <section class="section section-alt articles-page">
      <div class="container">
        <div class="article-not-found reveal">
          <h1>Статья не найдена</h1>
          <p>Возможно, ссылка устарела или материал был удалён.</p>
          <a class="btn btn-primary" href="<?= site_root() ?>articles">К списку статей</a>
        </div>
      </div>
    </section>
  </main>
    <?php
    render_page_end();
    exit;
}

$pageTitle = $article['description'] . ' — ИнтелАв';
$breadcrumb = fetch_category_breadcrumb($article['category_id']);

render_page_start($pageTitle, $article['description']);
render_header('articles');
?>
  <main>
    <section class="section section-alt articles-page">
      <div class="container article-page">
        <nav class="article-breadcrumb" aria-label="Навигация по статьям">
          <a href="<?= site_root() ?>articles">Статьи</a>
          <?php foreach ($breadcrumb as $crumb): ?>
            <span aria-hidden="true">/</span>
            <a href="<?= htmlspecialchars(category_url($crumb['slug']), ENT_QUOTES, 'UTF-8') ?>">
              <?= htmlspecialchars($crumb['title'], ENT_QUOTES, 'UTF-8') ?>
            </a>
          <?php endforeach; ?>
        </nav>

        <article class="blog-article reveal is-visible">
          <h1 class="blog-article-title"><?= htmlspecialchars($article['description'], ENT_QUOTES, 'UTF-8') ?></h1>
          <p class="article-card-meta">
            <span><?= htmlspecialchars($article['author'], ENT_QUOTES, 'UTF-8') ?></span>
            <span><?= htmlspecialchars(format_article_date($article['published_at']), ENT_QUOTES, 'UTF-8') ?></span>
          </p>
          <div class="blog-body">
            <?= $article['text'] ?>
          </div>
        </article>

        <section
          class="article-chat reveal is-visible"
          id="article-chat"
          data-article-id="<?= (int) $article['id'] ?>"
          aria-labelledby="article-chat-title"
        >
          <header class="article-chat-head">
            <h2 id="article-chat-title">Обсуждение</h2>
            <p>Оставьте ответ под статьёй — укажите имя и email.</p>
          </header>

          <div class="article-chat-list" id="article-chat-list">
            <p class="article-chat-empty">Загрузка ответов…</p>
          </div>

          <form class="article-chat-form" id="article-chat-form" novalidate>
            <div class="article-chat-fields">
              <label>
                Имя
                <input type="text" name="name" required autocomplete="name" placeholder="Как к вам обращаться" />
              </label>
              <label>
                Email
                <input type="email" name="email" required autocomplete="email" placeholder="mail@company.ru" />
              </label>
            </div>
            <label>
              Сообщение
              <textarea name="message" rows="4" required placeholder="Ваш комментарий или вопрос"></textarea>
            </label>
            <button class="btn btn-primary" type="submit">Отправить ответ</button>
            <p class="form-note" id="article-chat-note" hidden></p>
          </form>
        </section>
      </div>
    </section>
  </main>
<?php
render_page_end(['assets/js/article-chat.js?v=2']);
