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

  window.handleContactSubmit = (event) => {
    event.preventDefault();
    const note = document.getElementById("form-note");
    const form = event.target;
    if (note) note.hidden = false;
    if (form instanceof HTMLFormElement) form.reset();
    return false;
  };

  document.getElementById("to-top")?.addEventListener("click", (event) => {
    event.preventDefault();
    window.scrollTo({ top: 0, left: 0, behavior: "smooth" });
  });
})();
