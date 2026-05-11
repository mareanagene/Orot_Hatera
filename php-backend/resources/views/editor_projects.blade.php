<!doctype html>
<html lang="he" dir="rtl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>עורך פרויקטים</title>
    <link rel="stylesheet" href="/static/styles.css" />
  </head>
  <body>
    <div class="site-shell">
      <header class="top-header">
        <div class="brand">
          <h1>פרויקטים</h1>
          <p>מחובר: {{ $current_user['username'] }}</p>
        </div>
        <div class="header-actions">
          <a class="lang-toggle" href="{{ route('editor') }}">CMS Editor</a>
          <a class="lang-toggle" href="{{ route('editor.ceo') }}">דבר המייסד</a>
          <a class="lang-toggle" href="{{ route('editor.contacts') }}">פניות צור קשר</a>
          <a class="lang-toggle" href="{{ route('editor.team') }}">עץ ארגון</a>
          <a class="lang-toggle" href="{{ route('projects') }}">צפייה בדף ציבורי</a>
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

          <form method="post" id="projects-editor-form">
            @csrf
            <input type="hidden" name="page_name" value="{{ $page_name }}" />
            <input type="hidden" name="projects_count" id="projects_count" value="{{ count($projects) }}" />

            <div id="projects-container">
              @foreach($projects as $idx => $p)
              <div class="project-editor-row card-editor-item">
                <div class="card-editor-toolbar">
                  <strong>#{{ $idx + 1 }}</strong>
                  <button type="button" class="lang-toggle project-remove-btn">מחק פרויקט</button>
                </div>
                <div class="card-editor-fields">
                  <label>כותרת</label>
                  <input name="title_{{ $idx }}" value="{{ $p['title'] }}" />
                  <label>תקציר</label>
                  <input name="summary_{{ $idx }}" value="{{ $p['summary'] }}" />
                  <label>טקסט מלא</label>
                  <textarea name="body_text_{{ $idx }}" rows="3">{{ $p['body_text'] ?? '' }}</textarea>
                  <div class="project-gallery-editor">
                    <label>העלאת תמונות מהמחשב</label>
                    <input type="file" accept="image/*" class="project-gallery-upload" multiple />
                    <div class="project-gallery-preview"></div>
                    <label>גלריה (JSON)</label>
                    <textarea name="gallery_json_{{ $idx }}" rows="3" class="project-gallery-hidden">@json($p['images'])</textarea>
                    <p class="editor-link-hint">אפשר לבחור כמה תמונות יחד, להסיר תמונות קיימות, ולשמור את הסדר כפי שהוא מופיע כאן.</p>
                  </div>
                </div>
              </div>
              @endforeach
            </div>

            <button type="button" class="lang-toggle" id="project-add-row" style="margin-top: 12px;">+ הוסף פרויקט</button>
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
        const container = document.getElementById("projects-container");
        const countEl = document.getElementById("projects_count");
        const addBtn = document.getElementById("project-add-row");

        function reindexRows() {
          const rows = container.querySelectorAll(".project-editor-row");
          rows.forEach((row, idx) => {
            row.querySelector("strong").textContent = "#" + (idx + 1);
            row.querySelectorAll("input[name], textarea[name]").forEach((el) => {
              const n = el.getAttribute("name");
              if (!n) return;
              const base = n.replace(/_\d+$/, "");
              el.setAttribute("name", base + "_" + idx);
            });
          });
          countEl.value = String(rows.length);
        }

        function parseGalleryValue(value) {
          try {
            const parsed = JSON.parse(value || "[]");
            return Array.isArray(parsed) ? parsed.filter((item) => typeof item === "string" && item.trim() !== "") : [];
          } catch (err) {
            return [];
          }
        }

        function escapeHtml(value) {
          return String(value)
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;");
        }

        function writeGalleryValue(field, gallery) {
          field.value = JSON.stringify(gallery, null, 2);
        }

        function getApiErrorMessage(data, fallback = "Upload failed") {
          if (data && typeof data.error === "string" && data.error.trim()) return data.error;
          if (data && typeof data.message === "string" && data.message.trim()) return data.message;
          if (data && data.errors && typeof data.errors === "object") {
            const firstGroup = Object.values(data.errors).find((value) => Array.isArray(value) && value.length);
            if (Array.isArray(firstGroup) && typeof firstGroup[0] === "string" && firstGroup[0].trim()) {
              return firstGroup[0];
            }
          }
          return fallback;
        }

        function renderGalleryPreview(row) {
          const field = row?.querySelector(".project-gallery-hidden");
          const preview = row?.querySelector(".project-gallery-preview");
          if (!(field instanceof HTMLTextAreaElement) || !(preview instanceof HTMLElement)) return;
          const gallery = parseGalleryValue(field.value);

          if (!gallery.length) {
            preview.innerHTML = `<p class="project-gallery-empty">אין עדיין תמונות בפרויקט הזה.</p>`;
            return;
          }

          preview.innerHTML = gallery
            .map((url, idx) => `
              <div class="project-gallery-item" data-gallery-index="${idx}">
                <img src="${escapeHtml(url)}" alt="" class="project-gallery-thumb" loading="lazy" />
                <div class="project-gallery-meta">
                  <span class="project-gallery-index">תמונה ${idx + 1}</span>
                  <button type="button" class="lang-toggle project-gallery-remove-btn" data-gallery-index="${idx}">הסר</button>
                </div>
              </div>
            `)
            .join("");
        }

        async function uploadImageFile(file) {
          const fd = new FormData();
          fd.append("image", file);
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
            throw new Error("השרת החזיר תשובה לא תקינה. נסו לרענן את הדף ולנסות שוב.");
          }
          if (!res.ok || !data.url) {
            throw new Error(getApiErrorMessage(data, "Upload failed"));
          }
          return data.url;
        }

        function wireGalleryUpload(input) {
          if (!(input instanceof HTMLInputElement) || input.dataset.wired === "1") return;
          input.dataset.wired = "1";
          input.addEventListener("change", async () => {
            const files = Array.from(input.files || []);
            if (!files.length) return;
            const row = input.closest(".project-editor-row");
            const field = row?.querySelector(".project-gallery-hidden");
            if (!(field instanceof HTMLTextAreaElement)) return;
            const gallery = parseGalleryValue(field.value);
            try {
              for (const file of files) {
                gallery.push(await uploadImageFile(file));
              }
              writeGalleryValue(field, gallery);
              renderGalleryPreview(row);
            } catch (err) {
              alert(err instanceof Error ? err.message : "Upload failed");
            } finally {
              input.value = "";
            }
          });
        }

        container.querySelectorAll(".project-gallery-upload").forEach(wireGalleryUpload);
        container.querySelectorAll(".project-editor-row").forEach(renderGalleryPreview);

        addBtn?.addEventListener("click", () => {
          const idx = container.querySelectorAll(".project-editor-row").length;
          const row = document.createElement("div");
          row.className = "project-editor-row card-editor-item";
          row.innerHTML = `
            <div class="card-editor-toolbar">
              <strong>#${idx + 1}</strong>
              <button type="button" class="lang-toggle project-remove-btn">מחק פרויקט</button>
            </div>
            <div class="card-editor-fields">
              <label>כותרת</label><input name="title_${idx}" value="" />
              <label>תקציר</label><input name="summary_${idx}" value="" />
              <label>טקסט מלא</label><textarea name="body_text_${idx}" rows="3"></textarea>
              <div class="project-gallery-editor">
                <label>העלאת תמונות מהמחשב</label><input type="file" accept="image/*" class="project-gallery-upload" multiple />
                <div class="project-gallery-preview"></div>
                <label>גלריה (JSON)</label><textarea name="gallery_json_${idx}" rows="3" class="project-gallery-hidden">[]</textarea>
                <p class="editor-link-hint">אפשר לבחור כמה תמונות יחד, להסיר תמונות קיימות, ולשמור את הסדר כפי שהוא מופיע כאן.</p>
              </div>
            </div>`;
          container.appendChild(row);
          wireGalleryUpload(row.querySelector(".project-gallery-upload"));
          renderGalleryPreview(row);
          reindexRows();
        });

        container.addEventListener("click", (e) => {
          const t = e.target;
          if (!(t instanceof HTMLElement)) return;

          if (t.classList.contains("project-remove-btn")) {
            t.closest(".project-editor-row")?.remove();
            reindexRows();
            return;
          }

          if (t.classList.contains("project-gallery-remove-btn")) {
            const row = t.closest(".project-editor-row");
            const field = row?.querySelector(".project-gallery-hidden");
            const idx = Number(t.getAttribute("data-gallery-index"));
            if (!(field instanceof HTMLTextAreaElement) || Number.isNaN(idx)) return;
            const gallery = parseGalleryValue(field.value);
            gallery.splice(idx, 1);
            writeGalleryValue(field, gallery);
            renderGalleryPreview(row);
          }
        });

        container.addEventListener("input", (e) => {
          const target = e.target;
          if (!(target instanceof HTMLTextAreaElement) || !target.classList.contains("project-gallery-hidden")) return;
          renderGalleryPreview(target.closest(".project-editor-row"));
        });
      })();
    </script>
  </body>
</html>
