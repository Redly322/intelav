# Сайт ИнтелАв

Лендинг компании **ИнтелАв** (1С:Франчайзи) с PHP-бэкендом для формы заявок.

## Открыть локально

Для работы формы нужен PHP (SQLite + SMTP):

```powershell
cd C:\Users\Admin\Projects\1c-ai-landing\intelav-repo
php -S localhost:8080
```

Сайт: http://localhost:8080/

Без PHP можно открыть только вёрстку:

```powershell
Start-Process "C:\Users\Admin\Projects\1c-ai-landing\intelav-repo\index.html"
```

## Форма заявок (PHP)

1. Скопируйте `config.example.php` → `config.local.php`
2. Укажите SMTP и адрес получателя в `config.local.php`
3. Заявки сохраняются в SQLite: `data/feedback.db`
4. API: `POST /api/feedback.php` с JSON `{ "name", "contact", "message" }`

## База данных

- **Локально:** SQLite (`db_driver = sqlite`)
- **На хостинге:** MySQL (`db_driver = mysql`, `185.105.110.7:3306`)

Схема MySQL: `database/schema.mysql.sql`

Проверка подключения:

```powershell
php scripts/test-db-connection.php
```

## Статьи

Таблица `article_categories` — иерархия категорий:

| Колонка | Назначение |
|---------|------------|
| `slug` | Ссылка на раздел, URL: `articles.php#slug` |
| `title` | Название категории |
| `parent_id` | Родительская категория (верхний уровень — `NULL`) |
| `sort_order` | Порядок сортировки |

Таблица `articles` в той же базе `data/feedback.db`:

| Колонка | Назначение |
|---------|------------|
| `link` | Ссылка на статью (латиница и дефисы), URL: `article.php?link=...` |
| `description` | Заголовок / краткое описание |
| `author` | Автор |
| `published_at` | Дата публикации (`YYYY-MM-DD`) |
| `text` | Текст статьи (HTML) |
| `category_id` | Категория статьи |

Страницы:
- `articles.php` — список статей
- `article.php?link=slug` — отдельная статья с чатом ответов

### Ответы под статьями

Таблица `article_replies` (ключ — `article_id`):

| Колонка | Назначение |
|---------|------------|
| `article_id` | ID статьи |
| `name` | Имя автора ответа |
| `email` | Email автора ответа |
| `message` | Текст ответа |

API:
- `GET /api/article-reply.php?article_id=1` — список ответов
- `POST /api/article-reply.php` — добавить ответ `{ "article_id", "name", "email", "message" }`

Событие для будущих оповещений администратору: `includes/events.php` → `on_article_reply_created()`.

Импорт всех 9 статей из GitHub-версии `index.html`:

```powershell
git show origin/main:index.html > _github_index.html
php scripts/import-github-articles.php
```

На хостинге положите весь проект в корень сайта и убедитесь, что папка `data/` доступна для записи PHP.

## Админка

URL: `/admin/` (логин через `/admin/login.php`).

Возможности:
- несколько администраторов (`admin_users`)
- CRUD разделов и статей (текст — простое HTML-поле)
- просмотр и удаление ответов под статьями (публикуются сразу)

Первый администратор:

```powershell
php scripts/create-admin.php login password "Display Name"
```

На хостинге (SSH или через панель → терминал):

```bash
php scripts/create-admin.php login password "Display Name"
```

## Структура

| Раздел | Содержание |
|--------|------------|
| Главная | Hero с фоном «мегаполис», заголовок про автоматизацию на базе 1С |
| Продукты 1С | Бухгалтерия, ЗУП, ERP, Документооборот |
| Услуги | ИТС |
| Проекты | Типовые сценарии внедрений |
| О компании | О нас (+ логотип 1С:Франчайзинг), Команда, Контакты |

## Что заменить перед публикацией

1. Реальные фото сотрудников в блоке «Команда» (`assets/css/styles.css` → классы `.tone-a/b/c` или `<img>` в HTML).
2. Телефон, email и адрес в `#contacts`.
3. При наличии официального файла логотипа 1С:Франчайзинг с [рекламных материалов 1С](https://1c.ru/rus/partners/guideline.jsp) — подменить `assets/images/logo-1c-franchise.svg`.
4. При наличии фирменного логотипа ИнтелАв — подменить `assets/images/logo-intelav.svg`.
