<!doctype html>
<html lang="he" dir="rtl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="הנהלה וצוות — אורות הטירב ביצוע 1998 בע&quot;מ." />
    <title>הנהלה והצוות · {{ $content['brand_title'] ?? '' }}</title>
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
        'current_page' => 'team',
      ])

      <main class="page pub-page org-page">
        <header class="org-page-intro">
          <h1 class="org-page-title">הנהלת החברה והצוות</h1>
          <p class="org-page-sub">
            מבנה ארגוני מובנה — צילום, תפקיד ותיאור תפקידי בשטח.
          </p>
        </header>

        @if(empty($team_tiers))
        <p class="org-empty">אין עדיין רשומות — יש למלא בעורך.</p>
        @endif

        @foreach($team_tiers as $tierIndex => $tier)
        <section class="org-tier {{ $tierIndex === 0 ? 'org-tier--lead' : '' }}" aria-label="שורה {{ $tierIndex + 1 }}">
          @foreach($tier as $m)
          <article class="org-person">
            <div class="org-photo-wrap">
              @if(!empty($m['image_url']))
              <img src="{{ $m['image_url'] }}" alt="" class="org-photo" loading="lazy" />
              @else
              <div class="org-photo org-photo--placeholder" aria-hidden="true"></div>
              @endif
            </div>
            <h2 class="org-name">{{ $m['full_name'] }}</h2>
            <p class="org-role">{{ $m['role_title'] }}</p>
            @if(!empty($m['role_detail']))
            <p class="org-detail">{{ $m['role_detail'] }}</p>
            @endif
          </article>
          @endforeach
        </section>
        @endforeach
      </main>

      @include('partials.public_footer', [
        'content' => $content,
      ])
    </div>
    @include('partials.public_contact_modal')
  </body>
</html>
