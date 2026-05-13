<!doctype html>
<html lang="he" dir="rtl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="אורות הטירה ביצוע 1998 בע&quot;מ — תאורת כבישים ותשתיות." />
    <title>{{ $content['brand_title'] ?? 'אורות הטירה ביצוע 1998 בע"מ' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,700;1,9..144,400&family=Outfit:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="/static/styles.css" />
  </head>
  <body class="theme-public theme-fullwidth">
    @php($groupedCards = collect($farm_cards)->filter(fn($card) => !empty($card['is_active']))->groupBy('row_group'))
    <div class="site-shell">
      @include('partials.public_header', [
        'content' => $content,
        'current_user' => $current_user,
        'current_page' => 'home',
        'show_content_link' => true,
      ])

      <main class="page pub-page">
        <section
          id="hero"
          class="hero-banner pub-hero"
          @if(!empty($content['hero_image_url']))
          style="background-image: linear-gradient(rgba(8, 20, 36, 0.35), rgba(8, 20, 36, 0.28)), url('{{ $content['hero_image_url'] }}');"
          @endif
        >
          <div class="hero-overlay pub-hero-overlay">
            <div class="hero-copy">
              <h2>{!! nl2br(e($content['hero_title'] ?? '')) !!}</h2>
            </div>
          </div>
        </section>

        <div id="content" class="pub-content-wrap">
          @foreach($groupedCards as $row)
          <section class="cards-grid">
            @foreach($row as $card)
            <article
              class="{{ in_array($card['card_type'], ['farm', 'image'], true) ? 'panel ' : '' }}dynamic-card width-{{ $card['width_units'] ?? 1 }} type-{{ $card['card_type'] }}"
              data-bg="{{ $card['bg_color'] ?? \App\Support\LegacyCms::DEFAULT_CARD_BG_COLOR }}"
              data-text="{{ $card['text_color'] ?? \App\Support\LegacyCms::DEFAULT_CARD_TEXT_COLOR }}"
              @if(($card['card_type'] ?? 'farm') === 'image')
              style="height: {{ max(140, min(700, (int) ($card['card_height'] ?? \App\Support\LegacyCms::DEFAULT_CARD_HEIGHT))) }}px; width: {{ max(30, min(100, (int) ($card['image_card_width'] ?? \App\Support\LegacyCms::DEFAULT_IMAGE_CARD_WIDTH))) }}%; max-width: 100%; justify-self: center;"
              @elseif(in_array($card['card_type'], ['farm', 'image'], true))
              style="min-height: {{ max(140, min(700, (int) ($card['card_height'] ?? \App\Support\LegacyCms::DEFAULT_CARD_HEIGHT))) }}px;"
              @endif
            >
              @if(($card['card_type'] ?? '') === 'farm' && !empty($card['title']))
              <h3>{{ $card['title'] }}</h3>
              @endif
              @if(($card['card_type'] ?? '') === 'text' && !empty($card['title']))
              <p class="text-block-title">{{ $card['title'] }}</p>
              @endif
              @if(($card['card_type'] ?? '') === 'heading')
              <h2 class="heading-block">{{ $card['title'] ?? '' }}</h2>
              @endif
              @if(($card['card_type'] ?? '') === 'divider')
              <hr class="divider-block" />
              @endif
              @if(!empty($card['image_url']))
              <div
                class="card-image-wrap"
                style="height: {{ ($card['card_type'] ?? '') === 'image' ? '100%' : max(80, min(520, (int) ($card['image_height'] ?? \App\Support\LegacyCms::DEFAULT_CARD_IMAGE_HEIGHT))).'px' }};"
              >
                <img
                  src="{{ $card['image_url'] }}"
                  alt=""
                  class="card-image"
                  data-scale="{{ $card['image_scale'] ?? 100 }}"
                  data-x="{{ $card['image_x'] ?? 0 }}"
                  data-radius="{{ $card['image_radius'] ?? 0 }}"
                />
              </div>
              @endif
              @if(($card['card_type'] ?? '') !== 'image' && ($card['card_type'] ?? '') !== 'divider')
              <div class="card-body">
                @foreach(array_filter(explode("\n", (string) ($card['body_text'] ?? '')), fn($line) => trim($line) !== '') as $line)
                <p>{{ $line }}</p>
                @endforeach
                @if(!empty($card['caption']))
                <small>{{ $card['caption'] }}</small>
                @endif
                @if(!empty($card['link_url']))
                @php($linkHref = !empty($card['link_is_download']) && str_starts_with((string) $card['link_url'], '/uploads/') ? ((string) $card['link_url']).'?download=1' : $card['link_url'])
                <a
                  class="card-link-button"
                  href="{{ $linkHref }}"
                  @if(!empty($card['link_is_download']))
                  download
                  @else
                  target="_blank" rel="noopener noreferrer"
                  @endif
                >{{ $card['link_label'] ?: (!empty($card['link_is_download']) ? 'הורדת קובץ' : \App\Support\LegacyCms::DEFAULT_CARD_LINK_LABEL) }}</a>
                @endif
              </div>
              @endif
            </article>
            @endforeach
          </section>
          @endforeach
        </div>

        @include('partials.public_footer', [
          'content' => $content,
          'show_version' => true,
          'document_version' => $document_version ?? '1.0.0',
          'back_href' => '#hero',
          'back_label' => 'למעלה',
        ])
      </main>
    </div>
    @include('partials.public_contact_modal')

    <script>
      document.querySelectorAll(".dynamic-card").forEach((card) => {
        const bg = card.dataset.bg || @json(\App\Support\LegacyCms::DEFAULT_CARD_BG_COLOR);
        const text = card.dataset.text || @json(\App\Support\LegacyCms::DEFAULT_CARD_TEXT_COLOR);
        card.style.backgroundColor = bg;
        card.style.color = text;
      });
      document.querySelectorAll(".card-image[data-scale]").forEach((img) => {
        const raw = Number(img.dataset.scale || "100");
        const val = Math.max(30, Math.min(200, raw));
        img.style.transform = `scale(${val / 100})`;
        const x = Math.max(-100, Math.min(100, Number(img.dataset.x || "0")));
        img.style.objectPosition = `${50 + x / 2}% center`;
        const radius = Math.max(0, Math.min(50, Number(img.dataset.radius || "0")));
        img.style.borderRadius = `${radius}%`;
      });

    </script>
  </body>
</html>
