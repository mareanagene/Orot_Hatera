<!doctype html>
<html lang="he" dir="rtl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="דבר המייסד והמנכ&quot;ל — {{ $content['brand_title'] ?? 'אורות הטירה ביצוע 1998 בע&quot;מ' }}" />
    <title>{{ $content['page_title'] ?: 'דבר המייסד והמנכ"ל' }} · {{ $content['brand_title'] ?? '' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,700;1,9..144,400&family=Outfit:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="/static/styles.css" />
  </head>
  <body class="theme-public theme-fullwidth">
    @php($highlights = collect(explode("\n", (string) ($content['ceo_highlights_body'] ?? $content['ceo_highlights'] ?? '')))->map(fn ($line) => trim($line))->filter()->values()->all())
    @php($currentCeoName = $content['ceo_current_name'] ?: 'המנכ"ל הנוכחי')
    @php($currentCeoRole = $content['ceo_current_role'] ?: 'בנו של המייסד וממשיך הדרך')
    @php($currentCeoQuote = $content['ceo_current_quote'] ?: 'להמשיך את הדרך שהתחילה מתוך חזון, אחריות ואהבת העשייה, ולחבר בין המסורת המשפחתית למצוינות ניהולית של היום.')
    @php($currentCeoStory = $content['ceo_current_story_body'] ?? $content['ceo_current_story'] ?? 'כיום את החברה מוביל בנו של המייסד, שצמח לתוך העשייה, למד את התחום מבפנים וממשיך לבנות את החברה מתוך מחויבות לאנשים, לשירות ולמקצועיות. לצד ההמשכיות המשפחתית, הוא מביא איתו הסתכלות ניהולית עדכנית, קצב עבודה גבוה ושאיפה מתמדת לשפר, לדייק ולהוביל קדימה.')
    @php($currentCeoVision = $content['ceo_current_vision_body'] ?? $content['ceo_current_vision'] ?? 'הניהול הנוכחי ממשיך את ערכי היסוד שעליהם קמה החברה, ובמקביל שם דגש על צמיחה אחראית, חיזוק הקשר עם הלקוחות, פיתוח הצוות והרחבת הפעילות לשנים הבאות.')
    <div class="site-shell">
      @include('partials.public_header', [
        'content' => $content,
        'current_user' => $current_user,
        'current_page' => 'founder',
      ])

      <main class="page pub-page ceo-page">
        <header class="org-page-intro ceo-page-intro">
          <h1 class="org-page-title">{{ $content['page_title'] ?: 'דבר המייסד והמנכ"ל' }}</h1>
          <p class="org-page-sub">
            {{ $content['page_intro_body'] ?? $content['page_intro'] ?? 'היכרות אישית עם מייסד החברה, הדרך המקצועית שעבר והעשייה שבנתה את החברה לאורך השנים.' }}
          </p>
        </header>

        <section class="ceo-story-layout">
          <aside class="ceo-profile-card">
            <div class="ceo-portrait-wrap">
              @if(!empty($content['ceo_image_image_url']))
              <img src="{{ $content['ceo_image_image_url'] }}" alt="{{ $content['ceo_name'] ?? 'מייסד החברה' }}" class="ceo-portrait" loading="lazy" />
              @else
              <div class="ceo-portrait ceo-portrait--placeholder" aria-hidden="true"></div>
              @endif
            </div>
            <h2 class="ceo-name">{{ $content['ceo_name'] ?: 'מייסד החברה' }}</h2>
            <p class="ceo-role">{{ $content['ceo_role'] ?: 'מייסד החברה' }}</p>
            @if(!empty($content['ceo_quote']))
            <blockquote class="ceo-quote">
              “{{ $content['ceo_quote'] }}”
            </blockquote>
            @endif
          </aside>

          <div class="ceo-content-stack">
            <section class="ceo-section-card">
              <h2 class="ceo-section-title">הדרך שלי</h2>
              <div class="ceo-section-body">
                {!! nl2br(e($content['ceo_story_body'] ?? $content['ceo_story'] ?? 'כאן אפשר לספר על הדרך המקצועית, הרקע והערכים שמלווים את הנהלת החברה.')) !!}
              </div>
            </section>

            <section class="ceo-section-card">
              <h2 class="ceo-section-title">פעילות ציבורית ומקצועית</h2>
              <div class="ceo-section-body">
                {!! nl2br(e($content['ceo_vision_body'] ?? $content['ceo_vision'] ?? 'כאן אפשר להציג את הפעילות הציבורית, ההישגים המקצועיים והפרויקטים המרכזיים לאורך השנים.')) !!}
              </div>
            </section>

            @if(!empty($highlights))
            <section class="ceo-section-card">
              <h2 class="ceo-section-title">נקודות מפתח</h2>
              <ul class="ceo-highlights">
                @foreach($highlights as $item)
                <li>{{ $item }}</li>
                @endforeach
              </ul>
            </section>
            @endif
          </div>
        </section>

        <section class="ceo-story-layout ceo-story-layout--secondary">
          <aside class="ceo-profile-card">
            <div class="ceo-portrait-wrap">
              @if(!empty($content['ceo_current_image_image_url']))
              <img src="{{ $content['ceo_current_image_image_url'] }}" alt="{{ $currentCeoName }}" class="ceo-portrait" loading="lazy" />
              @else
              <div class="ceo-portrait ceo-portrait--placeholder" aria-hidden="true"></div>
              @endif
            </div>
            <h2 class="ceo-name">{{ $currentCeoName }}</h2>
            <p class="ceo-role">{{ $currentCeoRole }}</p>
            <blockquote class="ceo-quote">
              “{{ $currentCeoQuote }}”
            </blockquote>
          </aside>

          <div class="ceo-content-stack">
            <section class="ceo-section-card">
              <h2 class="ceo-section-title">דבר המנכ"ל</h2>
              <div class="ceo-section-body">
                {!! nl2br(e($currentCeoStory)) !!}
              </div>
            </section>

            <section class="ceo-section-card">
              <h2 class="ceo-section-title">ממשיכים את הדרך</h2>
              <div class="ceo-section-body">
                {!! nl2br(e($currentCeoVision)) !!}
              </div>
            </section>
          </div>
        </section>

        @if(!empty($ceo_gallery_items))
        <section class="ceo-gallery-shell">
          <div class="ceo-gallery-head">
            <h2 class="ceo-section-title">רגעים מהדרך</h2>
            <p class="org-page-sub">גלריה נעה של תמונות מהעשייה, מהמשפחה ומהמשך הדרך. לחצו על תמונה כדי להציג את המשפט שלה.</p>
          </div>
          <div class="ceo-gallery-marquee" data-ceo-gallery>
            <div class="ceo-gallery-track">
              @foreach($ceo_gallery_items as $item)
              <button
                type="button"
                class="ceo-gallery-card{{ $loop->first ? ' is-active' : '' }}"
                data-caption="{{ $item['caption'] }}"
                aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
              >
                <img src="{{ $item['image_url'] }}" alt="{{ $item['caption'] }}" loading="lazy" />
                <span class="ceo-gallery-caption">{{ $item['caption'] }}</span>
              </button>
              @endforeach
            </div>
          </div>
        </section>
        @endif
      </main>

      @include('partials.public_footer', [
        'content' => $content,
      ])
    </div>
    @include('partials.public_contact_modal')
    @if(!empty($ceo_gallery_items))
    <script>
      (function () {
        const gallery = document.querySelector("[data-ceo-gallery]");
        if (!gallery) return;

        const cards = Array.from(gallery.querySelectorAll(".ceo-gallery-card"));
        if (!cards.length) return;

        let direction = 1;
        let paused = false;
        let rafId = 0;

        function setActive(card) {
          cards.forEach((item) => {
            const active = item === card;
            item.classList.toggle("is-active", active);
            item.setAttribute("aria-pressed", active ? "true" : "false");
          });
        }

        cards.forEach((card) => {
          card.addEventListener("click", () => setActive(card));
        });

        gallery.addEventListener("mouseenter", () => {
          paused = true;
        });

        gallery.addEventListener("mouseleave", () => {
          paused = false;
        });

        function tick() {
          const maxScroll = Math.max(0, gallery.scrollWidth - gallery.clientWidth);
          if (!paused && maxScroll > 0) {
            const next = gallery.scrollLeft + direction * 0.45;
            if (next <= 0) {
              gallery.scrollLeft = 0;
              direction = 1;
            } else if (next >= maxScroll) {
              gallery.scrollLeft = maxScroll;
              direction = -1;
            } else {
              gallery.scrollLeft = next;
            }
          }

          rafId = window.requestAnimationFrame(tick);
        }

        rafId = window.requestAnimationFrame(tick);
        window.addEventListener("pagehide", () => window.cancelAnimationFrame(rafId), { once: true });
      })();
    </script>
    @endif
  </body>
</html>
