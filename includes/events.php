<?php

declare(strict_types=1);

/**
 * Событие: пользователь оставил ответ под статьёй.
 * Здесь позже можно подключить email/Telegram-оповещения администратору.
 */
function on_article_reply_created(array $reply, array $article): void
{
    error_log(sprintf(
        '[article_reply] id=%d article_id=%d article=%s',
        $reply['id'],
        $reply['article_id'],
        $article['link'] ?? ''
    ));
}

function dispatch_article_reply_created(array $reply, array $article): void
{
    on_article_reply_created($reply, $article);
}
