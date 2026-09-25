<?php

declare(strict_types=1);

function render_page_start(string $title, string $description = ''): void
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeDescription = htmlspecialchars($description !== '' ? $description : $title, ENT_QUOTES, 'UTF-8');
    ?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= $safeTitle ?></title>
  <meta name="description" content="<?= $safeDescription ?>" />
  <base href="<?= htmlspecialchars(site_root(), ENT_QUOTES, 'UTF-8') ?>" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Onest:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('assets/css/styles.css?v=59'), ENT_QUOTES, 'UTF-8') ?>" />
  <link rel="icon" href="<?= htmlspecialchars(asset_url('assets/images/favicon.svg'), ENT_QUOTES, 'UTF-8') ?>" type="image/svg+xml" />
  <link rel="icon" href="<?= htmlspecialchars(asset_url('favicon.ico'), ENT_QUOTES, 'UTF-8') ?>" sizes="any" />
  <link rel="icon" href="<?= htmlspecialchars(asset_url('assets/images/favicon-32.png'), ENT_QUOTES, 'UTF-8') ?>" type="image/png" sizes="32x32" />
  <link rel="apple-touch-icon" href="<?= htmlspecialchars(asset_url('apple-touch-icon.png'), ENT_QUOTES, 'UTF-8') ?>" />
</head>
<body>
  <div class="page-glow" aria-hidden="true"></div>
<?php
}

function nav_article_title(string $title, int $limit = 72): string
{
    $title = trim($title);
    if (mb_strlen($title) <= $limit) {
        return $title;
    }

    return rtrim(mb_substr($title, 0, $limit - 1)) . '…';
}

function render_header(string $active = ''): void
{
    require_once __DIR__ . '/categories.php';
    $blogTree = fetch_nav_category_tree();
    $home = site_root();
    ?>
  <header class="site-header" id="top">
    <div class="header-inner">
      <a class="brand" href="<?= $home ?>" aria-label="ИнтелАв — на главную">
        <img src="<?= htmlspecialchars(asset_url('assets/images/logo-intelav.svg'), ENT_QUOTES, 'UTF-8') ?>" alt="ИнтелАв" width="160" height="40" />
      </a>

      <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Открыть меню">
        <span></span><span></span><span></span>
      </button>

      <nav class="site-nav" id="site-nav" aria-label="Основное меню">
        <ul class="nav-list">
          <li class="nav-item has-submenu">
            <button type="button" class="nav-link" aria-expanded="false" aria-controls="submenu-company">
              О компании <span class="chevron" aria-hidden="true"></span>
            </button>
            <ul class="submenu" id="submenu-company">
              <li><a href="<?= $home ?>#about">О нас</a></li>
              <li><a href="<?= $home ?>#certificates">Сертификаты</a></li>
              <li><a href="<?= $home ?>#team">Команда</a></li>
              <li><a href="<?= $home ?>#contacts">Контакты</a></li>
            </ul>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= $home ?>#projects">Проекты</a>
          </li>
          <li class="nav-item has-submenu">
            <button type="button" class="nav-link<?= $active === 'articles' ? ' is-active' : '' ?>" aria-expanded="false" aria-controls="submenu-blog">
              Блог <span class="chevron" aria-hidden="true"></span>
            </button>
            <ul class="submenu submenu-blog" id="submenu-blog">
              <li><a href="<?= $home ?>articles">Все статьи</a></li>
              <?php foreach ($blogTree as $section): ?>
                <li>
                  <a class="submenu-section" href="<?= htmlspecialchars(category_url($section['slug']), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8') ?>
                  </a>
                </li>
                <?php foreach ($section['articles'] as $navArticle): ?>
                  <li class="submenu-article">
                    <a href="<?= htmlspecialchars(article_url($navArticle['link']), ENT_QUOTES, 'UTF-8') ?>">
                      <?= htmlspecialchars(nav_article_title($navArticle['description']), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </ul>
          </li>
          <li class="nav-item has-submenu">
            <button type="button" class="nav-link" aria-expanded="false" aria-controls="submenu-services">
              Услуги <span class="chevron" aria-hidden="true"></span>
            </button>
            <ul class="submenu" id="submenu-services">
              <li><a href="<?= $home ?>#service-pricing">Стоимость услуг</a></li>
              <li><a href="<?= $home ?>#service-its">ИТС</a></li>
            </ul>
          </li>
          <li class="nav-item has-submenu">
            <button type="button" class="nav-link" aria-expanded="false" aria-controls="submenu-products">
              Продукты <span class="mark-1c">1С</span> <span class="chevron" aria-hidden="true"></span>
            </button>
            <ul class="submenu" id="submenu-products">
              <li><a href="<?= $home ?>#product-buh"><span class="mark-1c">1С</span>:Бухгалтерия</a></li>
              <li><a href="<?= $home ?>#product-zup"><span class="mark-1c">1С</span>:ЗУП</a></li>
              <li><a href="<?= $home ?>#product-ut"><span class="mark-1c">1С</span>:Управление торговлей</a></li>
              <li><a href="<?= $home ?>#product-ka"><span class="mark-1c">1С</span>:Комплексная автоматизация</a></li>
              <li><a href="<?= $home ?>#product-erp"><span class="mark-1c">1С</span>:ERP</a></li>
              <li><a href="<?= $home ?>#product-doc"><span class="mark-1c">1С</span>:Документооборот</a></li>
            </ul>
          </li>
        </ul>
        <a class="header-cta" href="<?= $home ?>#contacts">Оставить заявку</a>
      </nav>
    </div>
  </header>
<?php
}

function render_page_end(array $extraScripts = []): void
{
    ?>
  <footer class="site-footer">
    <div class="container footer-inner">
      <a class="brand" href="<?= site_root() ?>">
        <img src="<?= htmlspecialchars(asset_url('assets/images/logo-intelav.svg'), ENT_QUOTES, 'UTF-8') ?>" alt="ИнтелАв" width="140" height="36" />
      </a>
      <p>© <span id="year"></span> ИнтелАв. Комплексные решения для автоматизации на базе <span class="mark-1c">1С</span>.</p>
      <a href="#top" class="to-top" id="to-top">Наверх</a>
    </div>
  </footer>

  <script src="<?= htmlspecialchars(asset_url('assets/js/main.js?v=4'), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php foreach ($extraScripts as $script): ?>
  <script src="<?= htmlspecialchars(asset_url($script), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endforeach; ?>
</body>
</html>
<?php
}
