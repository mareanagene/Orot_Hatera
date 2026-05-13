<!doctype html>
<html lang="he" dir="rtl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="פרויקטים נבחרים — אורות הטירה ביצוע 1998 בע&quot;מ." />
    <title>הפרויקטים שלנו · {{ $content['brand_title'] ?? '' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,700;1,9..144,400&family=Outfit:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="/static/styles.css" />
  </head>
  <body class="theme-public theme-fullwidth">
    <div class="site-shell">
      @include('partials.public_header', [
        'content' => $content,
        'current_user' => $current_user,
        'current_page' => 'projects',
      ])

      <main class="page pub-page portfolio-page">
        <header class="org-page-intro portfolio-intro">
          <h1 class="org-page-title">הפרויקטים שלנו</h1>
          <p class="org-page-sub">
            דוגמאות מתוך עבודות תכנון ויישום בתאורת כבישים ותשתיות.
          </p>
        </header>

        @if(empty($portfolio_projects))
        <p class="org-empty">אין פרויקטים להצגה — יש להוסיף בעורך.</p>
        @else
        <div class="projects-grid" id="projects-grid">
          @foreach($portfolio_projects as $index => $p)
          @php($cover = $p['images'][0] ?? null)
          <article class="project-card" tabindex="0" role="button" aria-haspopup="dialog" data-project-index="{{ $index }}">
            <div class="project-card__media">
              <img src="{{ $cover ?: '/static/hero-reference.png' }}" alt="" loading="lazy" width="640" height="400" />
              <div class="project-card__shine" aria-hidden="true"></div>
            </div>
            <div class="project-card__body">
              <h2 class="project-card__title">{{ $p['title'] }}</h2>
              <p class="project-card__summary">{{ $p['summary'] }}</p>
              <span class="project-card__hint">לחצו לפרטים</span>
            </div>
          </article>
          @endforeach
        </div>
        @endif
      </main>

      @include('partials.public_footer', [
        'content' => $content,
      ])
    </div>

    <div id="project-modal" class="project-modal" role="dialog" aria-modal="true" aria-labelledby="project-modal-title" aria-hidden="true" hidden>
      <div class="project-modal__backdrop" id="project-modal-backdrop" tabindex="-1"></div>
      <div class="project-modal__panel">
        <button type="button" class="project-modal__close" id="project-modal-close" aria-label="סגור">×</button>
        <div class="project-modal__layout">
          <div class="project-modal__visual">
            <div class="project-modal__carousel-shell">
              <button type="button" class="project-carousel__arrow project-carousel__arrow--prev" id="project-carousel-prev" aria-label="תמונה קודמת">‹</button>
              <div class="project-carousel__viewport" id="project-carousel-viewport">
                <div class="project-carousel__strip" id="project-carousel-strip"></div>
              </div>
              <button type="button" class="project-carousel__arrow project-carousel__arrow--next" id="project-carousel-next" aria-label="תמונה הבאה">›</button>
            </div>
            <div class="project-carousel__dots" id="project-carousel-dots"></div>
          </div>
          <div class="project-modal__text">
            <h2 id="project-modal-title" class="project-modal__heading"></h2>
            <p id="project-modal-summary" class="project-modal__lead"></p>
            <div id="project-modal-body" class="project-modal__body"></div>
          </div>
        </div>
      </div>
    </div>

    @include('partials.public_contact_modal')

    <script type="application/json" id="portfolio-json">@json($portfolio_projects)</script>
    <script>
      (function () {
        const items = JSON.parse(document.getElementById("portfolio-json")?.textContent || "[]");
        const modal = document.getElementById("project-modal");
        const backdrop = document.getElementById("project-modal-backdrop");
        const closeBtn = document.getElementById("project-modal-close");
        const titleEl = document.getElementById("project-modal-title");
        const summaryEl = document.getElementById("project-modal-summary");
        const bodyEl = document.getElementById("project-modal-body");
        const stripEl = document.getElementById("project-carousel-strip");
        const dotsEl = document.getElementById("project-carousel-dots");
        const viewportEl = document.getElementById("project-carousel-viewport");
        const prevBtn = document.getElementById("project-carousel-prev");
        const nextBtn = document.getElementById("project-carousel-next");
        let activeImages = [];
        let activeIndex = 0;

        function renderCarousel(images, startIndex = 0) {
          activeImages = images.length ? images : ["/static/hero-reference.png"];
          activeIndex = Math.max(0, Math.min(startIndex, activeImages.length - 1));

          if (stripEl) {
            stripEl.innerHTML = activeImages
              .map((src, idx) => `<img src="${src}" alt="" data-slide-index="${idx}" />`)
              .join("");
          }

          if (dotsEl) {
            dotsEl.innerHTML = activeImages
              .map((_, idx) => `<button type="button" class="project-carousel__dot${idx === activeIndex ? " is-active" : ""}" data-dot-index="${idx}" aria-label="מעבר לתמונה ${idx + 1}"></button>`)
              .join("");
          }

          updateCarouselPosition();
          const multiple = activeImages.length > 1;
          if (prevBtn) prevBtn.hidden = !multiple;
          if (nextBtn) nextBtn.hidden = !multiple;
        }

        function updateCarouselPosition() {
          const slides = Array.from(stripEl?.querySelectorAll("img") || []);
          slides.forEach((img, idx) => {
            img.setAttribute("aria-hidden", idx === activeIndex ? "false" : "true");
          });
          dotsEl?.querySelectorAll(".project-carousel__dot").forEach((dot, idx) => {
            dot.classList.toggle("is-active", idx === activeIndex);
          });

          const target = slides[activeIndex];
          if (target) {
            target.scrollIntoView({ behavior: "smooth", inline: "start", block: "nearest" });
          }
        }

        function openProject(index) {
          const item = items[index];
          if (!item) return;
          titleEl.textContent = item.title || "";
          summaryEl.textContent = item.summary || "";
          bodyEl.innerHTML = String(item.body_text || "").split("\n").filter(Boolean).map((line) => `<p>${line}</p>`).join("");
          renderCarousel(Array.isArray(item.images) ? item.images.filter(Boolean) : []);
          modal.hidden = false;
          modal.setAttribute("aria-hidden", "false");
        }

        function closeProject() {
          modal.hidden = true;
          modal.setAttribute("aria-hidden", "true");
        }

        document.querySelectorAll("[data-project-index]").forEach((card) => {
          card.addEventListener("click", () => openProject(Number(card.dataset.projectIndex)));
          card.addEventListener("keydown", (e) => {
            if (e.key === "Enter" || e.key === " ") {
              e.preventDefault();
              openProject(Number(card.dataset.projectIndex));
            }
          });
        });

        closeBtn?.addEventListener("click", closeProject);
        backdrop?.addEventListener("click", closeProject);
        prevBtn?.addEventListener("click", () => {
          if (!activeImages.length) return;
          activeIndex = (activeIndex - 1 + activeImages.length) % activeImages.length;
          updateCarouselPosition();
        });
        nextBtn?.addEventListener("click", () => {
          if (!activeImages.length) return;
          activeIndex = (activeIndex + 1) % activeImages.length;
          updateCarouselPosition();
        });
        dotsEl?.addEventListener("click", (e) => {
          const target = e.target;
          if (!(target instanceof HTMLElement) || !target.classList.contains("project-carousel__dot")) return;
          const idx = Number(target.getAttribute("data-dot-index"));
          if (Number.isNaN(idx)) return;
          activeIndex = idx;
          updateCarouselPosition();
        });
        viewportEl?.addEventListener("scroll", () => {
          if (!viewportEl || !activeImages.length) return;
          const width = viewportEl.clientWidth || 1;
          const nextIndex = Math.round(viewportEl.scrollLeft / width);
          if (nextIndex !== activeIndex && nextIndex >= 0 && nextIndex < activeImages.length) {
            activeIndex = nextIndex;
            dotsEl?.querySelectorAll(".project-carousel__dot").forEach((dot, idx) => {
              dot.classList.toggle("is-active", idx === activeIndex);
            });
          }
        });
      })();

    </script>
  </body>
</html>
