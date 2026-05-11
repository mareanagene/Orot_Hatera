<!doctype html>
<html lang="he" dir="rtl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>עורך עץ ארגון</title>
    <link rel="stylesheet" href="/static/styles.css" />
  </head>
  <body>
    <div class="site-shell">
      <header class="top-header">
        <div class="brand">
          <h1>עץ ארגון</h1>
          <p>מחובר: {{ $current_user['username'] }}</p>
        </div>
        <div class="header-actions">
          <a class="lang-toggle" href="{{ route('editor') }}">CMS Editor</a>
          <a class="lang-toggle" href="{{ route('editor.ceo') }}">דבר המייסד</a>
          <a class="lang-toggle" href="{{ route('editor.projects') }}">פרויקטים</a>
          <a class="lang-toggle" href="{{ route('team') }}">צפייה בדף ציבורי</a>
          <a class="lang-toggle" href="{{ route('logout') }}">Logout</a>
        </div>
      </header>

      <main class="page" style="max-width: 920px; margin: 0 auto; padding: 16px;">
        <section class="panel panel-live" style="min-height: auto;">
          <form method="get" class="live-form" style="margin-bottom: 16px;">
            <label>page_name</label>
            <input name="page_name" value="{{ $page_name }}" list="pages-list" />
            <datalist id="pages-list">
              @foreach($pages as $p)
              <option value="{{ $p }}"></option>
              @endforeach
            </datalist>
            <button type="submit">טען</button>
          </form>

          <form method="post" id="team-editor-form">
            @csrf
            <input type="hidden" name="page_name" value="{{ $page_name }}" />
            <input type="hidden" name="members_count" id="members_count" value="{{ count($members) }}" />

            <div id="team-members-container">
              @foreach($members as $idx => $m)
              <div class="team-editor-row card-editor-item">
                <div class="card-editor-toolbar">
                  <strong>#{{ $idx + 1 }}</strong>
                  <button type="button" class="lang-toggle team-remove-btn">Remove</button>
                </div>
                <div class="card-editor-fields">
                  <label>tier</label>
                  <input name="tier_{{ $idx }}" type="number" min="0" value="{{ $m['tier_index'] }}" />
                  <label>שם מלא</label>
                  <input name="full_name_{{ $idx }}" value="{{ $m['full_name'] }}" />
                  <label>תפקיד בארגון</label>
                  <input name="role_title_{{ $idx }}" value="{{ $m['role_title'] }}" />
                  <label>מה התפקיד כולל</label>
                  <textarea name="role_detail_{{ $idx }}" rows="2">{{ $m['role_detail'] ?? '' }}</textarea>
                  <label>image URL</label>
                  <input name="image_url_{{ $idx }}" type="text" class="team-image-url" value="{{ $m['image_url'] ?? '' }}" />
                  <label>Upload photo</label>
                  <input type="file" accept="image/*" class="team-photo-upload" />
                </div>
              </div>
              @endforeach
            </div>

            <button type="button" class="lang-toggle" id="team-add-member" style="margin-top: 12px;">+ הוסף איש צוות</button>
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
        const container = document.getElementById("team-members-container");
        const membersCount = document.getElementById("members_count");
        const addBtn = document.getElementById("team-add-member");

        function reindexRows() {
          const rows = container.querySelectorAll(".team-editor-row");
          rows.forEach((row, idx) => {
            row.querySelector("strong").textContent = "#" + (idx + 1);
            row.querySelectorAll("input[name], textarea[name]").forEach((el) => {
              const n = el.getAttribute("name");
              if (!n) return;
              const base = n.replace(/_\d+$/, "");
              el.setAttribute("name", base + "_" + idx);
            });
          });
          membersCount.value = String(rows.length);
        }

        function wireUpload(input) {
          input.addEventListener("change", async () => {
            const file = input.files?.[0];
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
                return alert("השרת החזיר תשובה לא תקינה. נסו לרענן את הדף ולנסות שוב.");
              }
              if (!res.ok || !data.url) return alert(data.error || "Upload failed");
              const row = input.closest(".team-editor-row");
              const field = row?.querySelector(".team-image-url");
              if (field) field.value = data.url;
            } catch (err) {
              alert("Upload failed");
            }
          });
        }

        container.querySelectorAll(".team-photo-upload").forEach(wireUpload);

        addBtn?.addEventListener("click", () => {
          const idx = container.querySelectorAll(".team-editor-row").length;
          const wrap = document.createElement("div");
          wrap.className = "team-editor-row card-editor-item";
          wrap.innerHTML = `
            <div class="card-editor-toolbar">
              <strong>#${idx + 1}</strong>
              <button type="button" class="lang-toggle team-remove-btn">Remove</button>
            </div>
            <div class="card-editor-fields">
              <label>tier</label><input name="tier_${idx}" type="number" min="0" value="2" />
              <label>שם מלא</label><input name="full_name_${idx}" value="" />
              <label>תפקיד בארגון</label><input name="role_title_${idx}" value="" />
              <label>מה התפקיד כולל</label><textarea name="role_detail_${idx}" rows="2"></textarea>
              <label>image URL</label><input name="image_url_${idx}" type="text" class="team-image-url" value="" />
              <label>Upload photo</label><input type="file" accept="image/*" class="team-photo-upload" />
            </div>`;
          container.appendChild(wrap);
          wireUpload(wrap.querySelector(".team-photo-upload"));
          reindexRows();
        });

        container.addEventListener("click", (e) => {
          const t = e.target;
          if (!(t instanceof HTMLElement) || !t.classList.contains("team-remove-btn")) return;
          t.closest(".team-editor-row")?.remove();
          reindexRows();
        });
      })();
    </script>
  </body>
</html>
