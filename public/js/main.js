/**
 * ============================================================================
 * Interactive Vanilla JS (public/js/main.js)
 * ============================================================================
 * - Client-side dynamic filtering for project cards
 * - Smooth state transitions
 */

document.addEventListener("DOMContentLoaded", () => {
  initProjectFilter();
  initPillNav();
});

function initPillNav() {
  const navItems = Array.from(document.querySelectorAll(".pill-nav-item"));

  if (!navItems.length) {
    return;
  }

  const sections = navItems
    .map((item) => {
      const hash = new URL(item.href, window.location.href).hash;
      return hash ? document.getElementById(hash.slice(1)) : null;
    })
    .filter(Boolean);

  let pendingItem = null;
  let scrollEndTimer = null;
  let scrollFrame = null;

  const setActiveItem = (activeItem) => {
    navItems.forEach((item) => {
      const isActive = item === activeItem;
      item.classList.toggle("active", isActive);

      if (isActive) {
        item.setAttribute("aria-current", "location");
      } else {
        item.removeAttribute("aria-current");
      }
    });
  };

  const itemFromHash = () => navItems.find((item) => {
    const target = new URL(item.href, window.location.href);
    return target.hash && target.hash === window.location.hash;
  });

  const updateFromScroll = () => {
    if (!sections.length) return;

    // The latest section whose top passed this line is the active section.
    const activationLine = Math.min(window.innerHeight * 0.35, 180);
    let activeIndex = 0;

    sections.forEach((section, index) => {
      if (section.getBoundingClientRect().top <= activationLine) {
        activeIndex = index;
      }
    });

    // The final section remains active when the page reaches its bottom.
    const scrollRoot = document.scrollingElement || document.documentElement;
    if (scrollRoot.scrollTop + scrollRoot.clientHeight >= scrollRoot.scrollHeight - 2) {
      activeIndex = sections.length - 1;
    }

    setActiveItem(navItems.find((item) => {
      const hash = new URL(item.href, window.location.href).hash;
      return hash === `#${sections[activeIndex].id}`;
    }));
  };

  const scheduleScrollUpdate = () => {
    if (scrollFrame === null) {
      scrollFrame = window.requestAnimationFrame(() => {
        scrollFrame = null;
        if (!pendingItem) updateFromScroll();
      });
    }

    if (pendingItem) {
      window.clearTimeout(scrollEndTimer);
      scrollEndTimer = window.setTimeout(() => {
        pendingItem = null;
        updateFromScroll();
      }, 160);
    }
  };

  navItems.forEach((item) => {
    item.addEventListener("click", () => {
      pendingItem = item;
      setActiveItem(item);
      window.clearTimeout(scrollEndTimer);
      scrollEndTimer = window.setTimeout(() => {
        pendingItem = null;
        updateFromScroll();
      }, 160);
    });
  });

  window.addEventListener("scroll", scheduleScrollUpdate, { passive: true });
  window.addEventListener("hashchange", () => {
    const hashItem = itemFromHash();
    if (hashItem) setActiveItem(hashItem);
    scheduleScrollUpdate();
  });

  const initialItem = itemFromHash();
  if (initialItem) setActiveItem(initialItem);
  else updateFromScroll();
}

/**
 * Filter Kartu Proyek Berdasarkan Kategori
 */
function initProjectFilter() {
  const filterButtons = document.querySelectorAll(".filter-btn");
  const projectCards = document.querySelectorAll(".project-card");

  if (!filterButtons.length || !projectCards.length) {
    return;
  }

  filterButtons.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();

      // 1. Update State Active Button
      filterButtons.forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");

      const selectedCategory = btn.getAttribute("data-filter") || "all";

      // 2. Tampilkan / Sembunyikan Kartu dengan Efek Transisi Ringan
      let visibleCount = 0;

      projectCards.forEach((card) => {
        const cardCategory = card.getAttribute("data-category") || "";

        if (selectedCategory === "all" || cardCategory === selectedCategory) {
          card.style.display = "flex";
          card.style.opacity = "0";
          card.style.transform = "translateY(6px)";

          // Memicu reflow lembut untuk animasi transisi muncul
          setTimeout(() => {
            card.style.transition = "opacity 0.25s ease, transform 0.25s ease";
            card.style.opacity = "1";
            card.style.transform = "translateY(0)";
          }, 10);

          visibleCount++;
        } else {
          card.style.display = "none";
        }
      });

      // 3. Fallback jika tidak ada proyek yang cocok dalam kategori ini
      let emptyNotice = document.getElementById("filter-empty-notice");
      if (visibleCount === 0) {
        if (!emptyNotice) {
          emptyNotice = document.createElement("div");
          emptyNotice.id = "filter-empty-notice";
          emptyNotice.className = "empty-box";
          emptyNotice.innerHTML = `
                        <h3>Tidak Ada Proyek</h3>
                        <p>Belum ada karya yang diunggah untuk kategori ini.</p>
                    `;
          const grid = document.querySelector(".projects-grid");
          if (grid) {
            grid.parentNode.insertBefore(emptyNotice, grid.nextSibling);
          }
        }
        emptyNotice.style.display = "block";
      } else if (emptyNotice) {
        emptyNotice.style.display = "none";
      }
    });
  });
}
