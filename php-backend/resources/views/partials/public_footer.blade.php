@php($showVersion = !empty($show_version))
@php($version = trim((string) ($document_version ?? '1.0.0')) ?: '1.0.0')
<footer class="pub-footer">
  <div class="pub-footer-inner">
    <div class="pub-footer-brand">
      <strong>{{ $content['brand_title'] ?? '' }}</strong>
      <span>{{ $content['brand_tagline'] ?? '' }}</span>
    </div>
    <p class="pub-footer-copy">
      תאורה חכמה ואמינה לדרכים ולתשתיות — אורות הטירב ביצוע 1998 בע&quot;מ.
    </p>
    <div class="pub-footer-extra">
      <div class="pub-footer-contact">
        <span class="pub-footer-contact__divider" aria-hidden="true"></span>
        <div class="pub-footer-contact__row">
          <span><strong>טל:</strong> <a href="tel:048525220">04-8525220</a></span>
          <span><strong>פקס</strong> 04-8525224</span>
          <span><strong>אימייל:</strong> <a href="mailto:office@orot-l.com">office@orot-l.com</a></span>
        </div>
      </div>
    </div>
    <div class="pub-footer-meta">
      <a href="{{ $back_href ?? route('index') }}">{{ $back_label ?? 'בית' }}</a>
      <span class="pub-dot">·</span>
      <span>&copy; {{ $content['brand_title'] ?? '' }}</span>
    </div>
    @if($showVersion)
    <p class="pub-footer-version">Version: {{ $version }}</p>
    @endif
  </div>
</footer>
