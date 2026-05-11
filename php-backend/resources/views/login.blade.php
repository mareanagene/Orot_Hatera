<!doctype html>
<html lang="he" dir="rtl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>כניסה</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,700&family=Outfit:wght@400;600&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="/static/styles.css" />
  </head>
  <body class="theme-public theme-public-login">
    <div class="site-shell">
      <main class="page" style="max-width: 520px; margin: 40px auto; padding: 0 16px;">
        <section class="panel panel-live" style="min-height: auto;">
          <h3>כניסה למערכת</h3>
          <form method="post" class="live-form">
            @csrf
            <input type="hidden" name="next" value="{{ $next_url ?? '' }}" />
            <label for="username">שם משתמש</label>
            <input id="username" name="username" required />

            <label for="password">סיסמה</label>
            <input id="password" name="password" type="password" required />

            <button type="submit">התחבר</button>
          </form>
          <p style="padding: 0 12px 12px;">
            <a href="{{ route('index') }}" style="color: inherit;">חזרה לדף הבית</a>
          </p>
          @if(!empty($error))
          <p style="padding: 0 12px 12px; color: #ffb4b4;">{{ $error }}</p>
          @endif
        </section>
      </main>
    </div>
  </body>
</html>
