<!doctype html>
<html lang="he" dir="rtl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="דבר המייסד — {{ $content['brand_title'] ?? 'אורות הטירב ביצוע 1998 בע&quot;מ' }}" />
    <title>{{ $content['page_title'] ?: 'דבר המייסד' }} · {{ $content['brand_title'] ?? '' }}</title>
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
    <div class="site-shell">
      @include('partials.public_header', [
        'content' => $content,
        'current_user' => $current_user,
        'current_page' => 'founder',
      ])

      <main class="page pub-page ceo-page">
        <header class="org-page-intro ceo-page-intro">
          <h1 class="org-page-title">{{ $content['page_title'] ?: 'דבר המייסד' }}</h1>
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
      </main>

      @include('partials.public_footer', [
        'content' => $content,
      ])
    </div>
    @include('partials.public_contact_modal')
  </body>
</html>
