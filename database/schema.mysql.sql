CREATE TABLE IF NOT EXISTS feedback (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    email VARCHAR(120) NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS article_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(120) NOT NULL,
    title VARCHAR(255) NOT NULL,
    parent_id INT UNSIGNED NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_article_categories_slug (slug),
    KEY idx_article_categories_parent_id (parent_id),
    CONSTRAINT fk_article_categories_parent
        FOREIGN KEY (parent_id) REFERENCES article_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS articles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    link VARCHAR(120) NOT NULL,
    description VARCHAR(255) NOT NULL,
    author VARCHAR(100) NOT NULL,
    published_at DATE NOT NULL,
    text MEDIUMTEXT NOT NULL,
    category_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_articles_link (link),
    KEY idx_articles_category_id (category_id),
    CONSTRAINT fk_articles_category
        FOREIGN KEY (category_id) REFERENCES article_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(60) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_admin_users_login (login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS article_replies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_article_replies_article_id (article_id),
    CONSTRAINT fk_article_replies_article
        FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
