# Деплой на хостинг (ветка `hosting`)

## Ветки

| Ветка | Назначение |
|-------|------------|
| `main` | Статическая версия сайта (как было) |
| `hosting` | PHP-бэкенд, статьи в SQLite, форма, чат, редиректы |

На хостинг выкладывается **только ветка `hosting`**.

## Хостинг MCHost (p668753)

| Параметр | Значение |
|----------|----------|
| Панель | ISPmanager |
| FTP | `p668753.ispmgr.mchost.ru` |
| Каталог сайта | `/www/` |
| MySQL | `localhost:3306` (только с сервера) |
| База | `p668753_sqllite` |
| phpMyAdmin | через панель хостинга |
| Домен | `uchet.info` → каталог `/www/uchet.info/` |

Пароли храните только в `config.local.php` на сервере, не в Git.

### Включить PDO MySQL (обязательно)

ISPmanager → **WWW** → **PHP** → **Расширения** → включить `pdo_mysql`.

Проверка: `https://uchet.info/scripts/health.php` должно показать `PDO MySQL: yes`.

### Ошибка 1044 (Access denied to database)

Логин MySQL верный, но **имя базы** в `config.local.php` не совпадает с созданной в панели.

1. ISPmanager → **Базы данных** → посмотрите точное имя БД
2. ISPmanager → откройте БД → убедитесь, что пользователь `p668753_p668753` добавлен с полными правами
3. Исправьте в `config.local.php`:
   ```php
   'db_name' => 'ТОЧНОЕ_ИМЯ_ИЗ_ПАНЕЛИ',
   'db_user' => 'p668753_p668753',
   ```
4. Диагностика: `https://uchet.info/scripts/diagnose-mysql.php?key=setup-once`

## Обновление на PHP-хостинге

### Через FTP (Windows)

```powershell
$env:INTELAV_FTP_PASS = 'your-ftp-password'
.\scripts\deploy-ftp.ps1
```

### Через Git (если доступен SSH)

```bash
cd /path/to/site
git fetch origin hosting
git checkout hosting
git pull origin hosting
```

После первого деплоя:

1. Скопируйте `config.example.php` → `config.local.php`
2. Укажите SMTP и почту получателя
3. Укажите MySQL на хостинге:
   - `db_driver` = `mysql`
   - `db_host` = `185.105.110.7`
   - `db_port` = `3306`
   - `db_name`, `db_user`, `db_pass` — из панели хостинга
4. На сервере в `config.local.php` укажите `db_host = localhost`
5. Откройте один раз: `/scripts/hosting-setup.php?key=setup-once`
6. Или импортируйте статьи вручную:

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

## Админка

После деплоя создайте первого администратора на сервере:

```bash
php scripts/create-admin.php admin your-password "Admin Name"
```

Вход: `https://uchet.info/admin/login.php`

## Локальный запуск

```powershell
.\start-local.ps1
```

Первый админ локально:

```powershell
php scripts/create-admin.php admin your-password "Admin Name"
```
