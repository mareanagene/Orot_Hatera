<!doctype html>
<html lang="he" dir="rtl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>עורך דף המייסד</title>
    <link rel="stylesheet" href="/static/styles.css" />
  </head>
  <body>
    <div class="site-shell">
      <header class="top-header">
        <div class="brand">
          <h1>דבר המייסד</h1>
          <p>מחובר: {{ $current_user['username'] }}</p>
        </div>
        <div class="header-actions">
          <a class="lang-toggle" href="{{ route('editor') }}">CMS Editor</a>
          <a class="lang-toggle" href="{{ route('editor.projects') }}">פרויקטים</a>
          <a class="lang-toggle" href="{{ route('editor.team') }}">עץ ארגון</a>
          <a class="lang-toggle" href="{{ route('ceo.message') }}">צפייה בדף ציבורי</a>
          <a class="lang-toggle" href="{{ route('logout') }}">Logout</a>
        </div>
      </header>

      <main class="page" style="max-width: 920px; margin: 0 auto; padding: 16px;">
        <section class="panel panel-live" style="min-height: auto;">
          <form method="post" id="ceo-editor-form" class="live-form">
            @csrf

            <label>כותרת הדף</label>
            <input name="page_title" value="{{ $content['page_title'] ?? 'דבר המייסד' }}" />

            <label>טקסט פתיחה</label>
            <textarea name="page_intro" rows="3">{{ $content['page_intro_body'] ?? $content['page_intro'] ?? '' }}</textarea>

            <label>Version מסמך</label>
            <input name="document_version" value="{{ $content['document_version'] ?? '1.0.0' }}" />

            <label>שם המייסד</label>
            <input name="ceo_name" value="{{ $content['ceo_name'] ?? '' }}" />

            <label>תפקיד / הגדרה</label>
            <input name="ceo_role" value="{{ $content['ceo_role'] ?? '' }}" />

            <label>ציטוט קצר</label>
            <input name="ceo_quote" value="{{ $content['ceo_quote'] ?? '' }}" />

            <label>הסיפור / הרקע</label>
            <textarea name="ceo_story" rows="7">{{ $content['ceo_story_body'] ?? $content['ceo_story'] ?? '' }}</textarea>

            <label>פעילות ציבורית / הישגים</label>
            <textarea name="ceo_vision" rows="6">{{ $content['ceo_vision_body'] ?? $content['ceo_vision'] ?? '' }}</textarea>

            <label>נקודות מפתח (שורה לכל נקודה)</label>
            <textarea name="ceo_highlights" rows="5">{{ $content['ceo_highlights_body'] ?? $content['ceo_highlights'] ?? '' }}</textarea>

            <label>תמונה</label>
            <input name="ceo_image" type="text" class="ceo-image-url" value="{{ $content['ceo_image_image_url'] ?? '' }}" />

            <label>העלאת תמונה מהמחשב</label>
            <input type="file" accept="image/*" class="ceo-photo-upload" />

            <div style="margin-top: 18px;">
              <button type="submit">שמירה</button>
            </div>
          </form>

          @if(!empty($message))
          <p style="padding: 12px 0; color: #b7f5c7; font-weight: 700;">{{ $message }}</p>
          @endif
        </section>
      </main>
    </div>
    <script>
      (function () {
        const uploadInput = document.querySelector(".ceo-photo-upload");
        const urlField = document.querySelector(".ceo-image-url");
        if (!uploadInput || !urlField) return;

        uploadInput.addEventListener("change", async () => {
          const file = uploadInput.files?.[0];
          if (!file) return;
          const fd = new FormData();
          fd.append("image", file);
          try {
            const res = await fetch("/api/upload-image", {
              method: "POST",
              body: fd,
              headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
              },
            });
            const text = await res.text();
            let data = {};
            try {
              data = text ? JSON.parse(text) : {};
            } catch (err) {
              alert("השרת החזיר תשובה לא תקינה. נסו לרענן את הדף ולנסות שוב.");
              return;
            }
            if (!res.ok || !data.url) {
              alert(data.error || "Upload failed");
              return;
            }
            urlField.value = data.url;
          } catch (err) {
            alert("Upload failed");
          } finally {
            uploadInput.value = "";
          }
        });
      })();
    </script>
  </body>
</html>
