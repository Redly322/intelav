# Деплой на хостинг (ветка `hosting`)

## Ветки

| Ветка | Назначение |
|-------|------------|
| `main` | Статическая версия сайта (как было) |
| `hosting` | PHP-бэкенд, статьи в SQLite, форма, чат, редиректы |

На хостинг выкладывается **только ветка `hosting`**.

## Обновление на PHP-хостинге

```bash
cd /path/to/site
git fetch origin hosting
git checkout hosting
git pull origin hosting
```

После первого деплоя:

1. Скопируйте `config.example.php` → `config.local.php`
2. Укажите SMTP и почту получателя
3. Убедитесь, что папка `data/` доступна для записи PHP
4. Импортируйте статьи:

```bash
git show origin/main:index.html > _github_index.html
php scripts/import-github-articles.php
```

## Редиректы

Настроены в `.htaccess`:

| Старый адрес | Новый адрес |
|--------------|-------------|
| `/article.php?link=slug` | `/article/slug` |
| `/articles.php` | `/articles` |
| `/blog/...` | `/articles#...` |
| `#blog-marketplace` на главной | `/articles#marketplace` (через JS) |

## Локальный запуск

```powershell
.\start-local.ps1
```
