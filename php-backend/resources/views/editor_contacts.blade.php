<!doctype html>
<html lang="he" dir="rtl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>פניות צור קשר</title>
    <link rel="stylesheet" href="/static/styles.css" />
  </head>
  <body>
    <div class="site-shell">
      <header class="top-header">
        <div class="brand">
          <h1>פניות צור קשר</h1>
          <p>מחובר: {{ $current_user['username'] }}</p>
        </div>
        <div class="header-actions">
          <a class="lang-toggle" href="{{ route('editor') }}">עורך האתר</a>
          <a class="lang-toggle" href="{{ route('index') }}">דף הבית</a>
          <a class="lang-toggle" href="{{ route('logout') }}">התנתקות</a>
        </div>
      </header>
      <main class="page" style="padding: 16px; max-width: 960px; margin: 0 auto;">
        @if(empty($inquiries))
        <p>אין פניות עדיין.</p>
        @else
        <table class="contact-leads-table">
          <thead>
            <tr>
              <th>תאריך</th>
              <th>שם</th>
              <th>אימייל</th>
              <th>טלפון</th>
              <th>הערה</th>
            </tr>
          </thead>
          <tbody>
            @foreach($inquiries as $row)
            <tr>
              <td>{{ $row['created_at'] }}</td>
              <td>{{ $row['full_name'] }}</td>
              <td><a href="mailto:{{ $row['email'] }}">{{ $row['email'] }}</a></td>
              <td>
                @if(!empty($row['phone']))
                <a href="tel:{{ str_replace(' ', '', $row['phone']) }}">{{ $row['phone'] }}</a>
                @else
                —
                @endif
              </td>
              <td>{{ $row['note'] ?: '—' }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
        @endif
      </main>
    </div>
  </body>
</html>
