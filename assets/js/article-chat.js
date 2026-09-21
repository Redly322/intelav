(() => {
  const chat = document.getElementById("article-chat");
  if (!chat) return;

  const articleId = Number(chat.dataset.articleId || 0);
  const form = document.getElementById("article-chat-form");
  const list = document.getElementById("article-chat-list");
  const note = document.getElementById("article-chat-note");
  const submitButton = form?.querySelector('button[type="submit"]');

  if (!articleId || !form || !list) return;

  const setNote = (text, isError = false) => {
    if (!note) return;
    note.textContent = text;
    note.hidden = false;
    note.classList.toggle("is-error", isError);
  };

  const escapeHtml = (value) =>
    String(value)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#39;");

  const renderReply = (item) => {
    const article = document.createElement("article");
    article.className = "article-chat-item";
    article.innerHTML = `
      <div class="article-chat-item-head">
        <strong>${escapeHtml(item.name)}</strong>
        <time datetime="${escapeHtml(item.created_at)}">${escapeHtml(item.created_at_label || item.created_at)}</time>
      </div>
      <p>${escapeHtml(item.message).replaceAll("\n", "<br>")}</p>
    `;
    return article;
  };

  const renderEmpty = () => {
    list.innerHTML = '<p class="article-chat-empty">Пока нет ответов. Будьте первым.</p>';
  };

  const loadReplies = async () => {
    try {
      const response = await fetch(`api/article-reply.php?article_id=${articleId}`);
      const result = await response.json().catch(() => null);

      if (!response.ok || !result?.ok) {
        list.innerHTML = '<p class="article-chat-empty">Не удалось загрузить ответы.</p>';
        return;
      }

      if (!result.items?.length) {
        renderEmpty();
        return;
      }

      list.innerHTML = "";
      result.items.forEach((item) => list.appendChild(renderReply(item)));
    } catch (_error) {
      list.innerHTML = '<p class="article-chat-empty">Не удалось загрузить ответы.</p>';
    }
  };

  form.addEventListener("submit", async (event) => {
    event.preventDefault();

    const formData = new FormData(form);
    const payload = {
      article_id: articleId,
      name: String(formData.get("name") || "").trim(),
      email: String(formData.get("email") || "").trim(),
      message: String(formData.get("message") || "").trim(),
    };

    if (!payload.name || !payload.email || !payload.message) {
      setNote("Заполните имя, email и сообщение.", true);
      return;
    }

    if (submitButton instanceof HTMLButtonElement) {
      submitButton.disabled = true;
    }

    try {
      const response = await fetch("api/article-reply.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const result = await response.json().catch(() => null);

      if (!response.ok || !result?.ok) {
        setNote(result?.error || "Не удалось отправить ответ.", true);
        return;
      }

      const emptyState = list.querySelector(".article-chat-empty");
      if (emptyState) {
        emptyState.remove();
      }

      list.appendChild(renderReply(result.item));
      setNote(result.message || "Ответ добавлен.");
      form.reset();
    } catch (_error) {
      setNote("Не удалось отправить ответ. Проверьте соединение.", true);
    } finally {
      if (submitButton instanceof HTMLButtonElement) {
        submitButton.disabled = false;
      }
    }
  });

  loadReplies();
})();
