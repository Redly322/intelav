(() => {
  const header = document.querySelector(".site-header");
  const navToggle = document.querySelector(".nav-toggle");
  const submenuParents = document.querySelectorAll(".nav-item.has-submenu");
  const year = document.getElementById("year");

  if (year) {
    year.textContent = String(new Date().getFullYear());
  }

  const onScroll = () => {
    if (!header) return;
    header.classList.toggle("is-scrolled", window.scrollY > 12);
  };
  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });

  navToggle?.addEventListener("click", () => {
    const open = document.body.classList.toggle("nav-open");
    navToggle.setAttribute("aria-expanded", String(open));
  });

  submenuParents.forEach((item) => {
    const trigger = item.querySelector(".nav-link");
    trigger?.addEventListener("click", (event) => {
      event.preventDefault();
      const willOpen = !item.classList.contains("is-open");
      submenuParents.forEach((other) => {
        other.classList.remove("is-open");
        other.querySelector(".nav-link")?.setAttribute("aria-expanded", "false");
      });
      if (willOpen) {
        item.classList.add("is-open");
        trigger.setAttribute("aria-expanded", "true");
      }
    });
  });

  document.addEventListener("click", (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;
    if (!target.closest(".has-submenu") && !target.closest(".nav-toggle")) {
      submenuParents.forEach((item) => {
        item.classList.remove("is-open");
        item.querySelector(".nav-link")?.setAttribute("aria-expanded", "false");
      });
    }
  });

  document.querySelectorAll('.submenu a, a.nav-link[href^="#"]').forEach((link) => {
    link.addEventListener("click", () => {
      document.body.classList.remove("nav-open");
      navToggle?.setAttribute("aria-expanded", "false");
      submenuParents.forEach((item) => {
        item.classList.remove("is-open");
        item.querySelector(".nav-link")?.setAttribute("aria-expanded", "false");
      });
    });
  });

  const revealItems = document.querySelectorAll(".reveal");
  if ("IntersectionObserver" in window) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.14, rootMargin: "0px 0px -40px 0px" }
    );
    revealItems.forEach((el) => observer.observe(el));
  } else {
    revealItems.forEach((el) => el.classList.add("is-visible"));
  }

  const contactForm = document.getElementById("contact-form");
  const formNote = document.getElementById("form-note");
  const submitButton = contactForm?.querySelector('button[type="submit"]');

  const setFormNote = (text, isError = false) => {
    if (!formNote) return;
    formNote.textContent = text;
    formNote.hidden = false;
    formNote.classList.toggle("is-error", isError);
  };

  contactForm?.addEventListener("submit", async (event) => {
    event.preventDefault();

    const formData = new FormData(contactForm);
    const payload = {
      name: String(formData.get("name") || "").trim(),
      contact: String(formData.get("contact") || "").trim(),
      message: String(formData.get("message") || "").trim(),
    };

    if (!payload.name || !payload.contact) {
      setFormNote("Заполните имя и телефон или email.", true);
      return;
    }

    if (submitButton instanceof HTMLButtonElement) {
      submitButton.disabled = true;
    }

    try {
      const response = await fetch("api/feedback.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      const result = await response.json().catch(() => null);

      if (!response.ok || !result?.ok) {
        setFormNote(result?.error || "Не удалось отправить заявку. Попробуйте позже.", true);
        return;
      }

      setFormNote(result.message || "Спасибо! Мы свяжемся с вами в ближайшее время.");
      contactForm.reset();
    } catch (_error) {
      setFormNote("Не удалось отправить заявку. Проверьте соединение и попробуйте снова.", true);
    } finally {
      if (submitButton instanceof HTMLButtonElement) {
        submitButton.disabled = false;
      }
    }
  });

  document.getElementById("to-top")?.addEventListener("click", (event) => {
    event.preventDefault();
    window.scrollTo({ top: 0, left: 0, behavior: "smooth" });
  });

  const legacyBlogRedirects = {
    blog: "articles",
    "blog-marketplace": "articles#marketplace",
    "blog-nsi": "articles#nsi",
    "blog-marking": "articles#marking",
    "blog-sales": "articles#sales",
    "blog-chestny-znak": "articles#chestny-znak",
    "blog-management-accounting": "articles#management-accounting",
    "blog-budgeting": "articles#budgeting",
  };

  const legacyHash = window.location.hash.replace(/^#/, "");
  if (legacyHash && legacyBlogRedirects[legacyHash]) {
    window.location.replace(legacyBlogRedirects[legacyHash]);
  }
})();
