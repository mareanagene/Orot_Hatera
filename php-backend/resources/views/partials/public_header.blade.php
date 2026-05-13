@php($currentPage = $current_page ?? '')
@php($showContentLink = !empty($show_content_link))
<header class="top-header pub-header">
  <div class="brand pub-brand">
    <a href="{{ route('index') }}" class="brand-lockup">
      <span class="brand-text">
        <span class="brand-title">{{ $content['brand_title'] ?? '' }}</span>
        <span class="brand-tag">{{ $content['brand_tagline'] ?? '' }}</span>
      </span>
      <img
        class="brand-badge"
        src="https://www.bdicoface.co.il/wp-content/uploads/2026/01/eitanut-iskit_2026_animated_resized-285x300.gif"
        alt='אות "איתנות עסקית" CofaceBDI לשנת 2026'
        loading="lazy"
        decoding="async"
      />
    </a>
  </div>
  <nav class="site-nav" aria-label="ניווט ראשי">
    <a class="nav-link{{ $currentPage === 'home' ? ' nav-link--current' : '' }}" href="{{ route('index') }}" @if($currentPage === 'home') aria-current="page" @endif>בית</a>
    <a class="nav-link{{ $currentPage === 'founder' ? ' nav-link--current' : '' }}" href="{{ route('ceo.message') }}" @if($currentPage === 'founder') aria-current="page" @endif>דבר המייסד והמנכ"ל</a>
    @if($showContentLink)
    <a class="nav-link" href="#content">תוכן</a>
    @endif
    <a class="nav-link{{ $currentPage === 'projects' ? ' nav-link--current' : '' }}" href="{{ route('projects') }}" @if($currentPage === 'projects') aria-current="page" @endif>הפרויקטים שלנו</a>
    <a class="nav-link{{ $currentPage === 'team' ? ' nav-link--current' : '' }}" href="{{ route('team') }}" @if($currentPage === 'team') aria-current="page" @endif>הנהלה והצוות</a>
  </nav>
  <div class="header-actions">
    <button type="button" class="lang-toggle pub-contact-btn" id="contact-open" aria-haspopup="dialog" aria-controls="contact-modal">
      צור קשר
    </button>
    <div class="profile-menu">
      <button class="profile-trigger pub-profile-trigger" type="button" aria-label="תפריט חשבון">
        <span class="profile-avatar">👤</span>
      </button>
      <div class="profile-popover">
        @if($current_user)
        <p class="profile-status">מחובר: {{ $current_user['username'] }}</p>
        <a class="profile-link" href="{{ route('editor') }}">עורך האתר</a>
        <a class="profile-link" href="{{ route('editor.ceo') }}">דבר המייסד והמנכ"ל</a>
        <a class="profile-link" href="{{ route('editor.projects') }}">פרויקטים</a>
        <a class="profile-link" href="{{ route('editor.team') }}">עץ ארגון</a>
        <a class="profile-link" href="{{ route('editor.contacts') }}">פניות צור קשר</a>
        <a class="profile-link" href="{{ route('users') }}">משתמשים</a>
        <a class="profile-link" href="{{ route('logout') }}">התנתקות</a>
        @else
        <p class="profile-status">לא מחובר</p>
        <a class="profile-link" href="{{ route('login') }}">כניסה</a>
        @endif
      </div>
    </div>
  </div>
</header>
