/**
 * ============================================================================
 * Interactive Vanilla JS (public/js/main.js)
 * ============================================================================
 * - Client-side dynamic filtering for project cards
 * - Accessible mobile navigation toggle (Hamburger menu)
 * - Auto-close mobile menu on link click or outside click
 * - Smooth state transitions
 */

document.addEventListener("DOMContentLoaded", () => {
  initMobileNav();
  initProjectFilter();
});

/**
 * Mobile Navigation Toggle (Hamburger Menu)
 */
function initMobileNav() {
  const toggleBtn = document.getElementById("nav-toggle");
  const navMenu = document.getElementById("nav-menu");

  if (!toggleBtn || !navMenu) {
    return;
  }

  // Toggle menu saat tombol hamburger diklik
  toggleBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    const isOpen = navMenu.classList.toggle("is-open");
    toggleBtn.classList.toggle("is-active", isOpen);
    toggleBtn.setAttribute("aria-expanded", isOpen ? "true" : "false");
  });

  // Tutup menu secara otomatis saat salah satu link navigasi diklik
  const navLinks = navMenu.querySelectorAll("a");
  navLinks.forEach((link) => {
    link.addEventListener("click", () => {
      if (navMenu.classList.contains("is-open")) {
        navMenu.classList.remove("is-open");
        toggleBtn.classList.remove("is-active");
        toggleBtn.setAttribute("aria-expanded", "false");
      }
    });
  });

  // Tutup menu jika user mengklik di luar area navigasi
  document.addEventListener("click", (e) => {
    if (!navMenu.contains(e.target) && !toggleBtn.contains(e.target)) {
      if (navMenu.classList.contains("is-open")) {
        navMenu.classList.remove("is-open");
        toggleBtn.classList.remove("is-active");
        toggleBtn.setAttribute("aria-expanded", "false");
      }
    }
  });

  // Tutup menu jika tombol ESC ditekan
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && navMenu.classList.contains("is-open")) {
      navMenu.classList.remove("is-open");
      toggleBtn.classList.remove("is-active");
      toggleBtn.setAttribute("aria-expanded", "false");
      toggleBtn.focus();
    }
  });
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
