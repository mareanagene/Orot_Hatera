<!doctype html>
<html lang="he" dir="rtl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>עורך האתר</title>
    <link rel="stylesheet" href="/static/styles.css" />
    <style>
      .editor-preview-shell {
        margin-bottom: 18px;
      }

      .editor-preview-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 16px 0;
        flex-wrap: wrap;
      }

      .editor-preview-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
      }

      .editor-preview-btn,
      .editor-reset-btn {
        margin-top: 0;
      }

      .editor-reset-btn {
        background: #6b7280;
      }

      .editor-reset-btn:hover {
        background: #4b5563;
      }

      .editor-mini-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 8px;
      }

      .editor-mini-btn {
        margin-top: 0;
        background: #173861;
        color: #f2d57e;
      }

      .editor-mini-btn:hover {
        background: #102a4a;
      }

      .editor-link-hint {
        margin: 6px 0 0;
        color: #dce6f2;
        font-size: 13px;
      }

      .preview-notice {
        padding: 8px 16px 0;
        color: #dce6f2;
        font-size: 14px;
      }

      .editor-preview-frame {
        margin: 12px;
        border-radius: 12px;
        overflow: hidden;
        background: #eef1f6;
        border: 1px solid rgba(255, 255, 255, 0.12);
        box-shadow: inset 0 0 0 1px rgba(16, 42, 74, 0.08);
      }

      .editor-preview-frame .top-header,
      .editor-preview-frame .pub-footer {
        pointer-events: none;
      }
    </style>
  </head>
  <body>
    <div class="site-shell">
      <header class="top-header">
        <div class="brand">
          <h1>{{ $content['brand_title'] ?: 'עורך האתר' }}</h1>
          <p>{{ $content['brand_tagline'] ?: ('מחובר: '.$current_user['username']) }}</p>
        </div>
        <div class="header-actions">
          <a class="lang-toggle" href="{{ route('editor.contacts') }}">פניות צור קשר</a>
          <a class="lang-toggle" href="{{ route('editor.ceo') }}">דבר המייסד והמנכ"ל</a>
          <a class="lang-toggle" href="{{ route('editor.projects') }}">פרויקטים</a>
          <a class="lang-toggle" href="{{ route('editor.team') }}">עץ ארגון</a>
          <a class="lang-toggle" href="{{ route('index') }}">דף הבית</a>
          <a class="lang-toggle" href="{{ route('logout') }}">התנתקות</a>
        </div>
      </header>

      <main class="page" style="max-width: 1100px; margin: 0 auto; padding: 16px;">
        @php(
          $previewRows = collect($farm_cards)
            ->sortBy(fn ($card) => sprintf('%06d-%06d', $card['row_group'] ?? 0, $card['sort_order'] ?? 0))
            ->filter(fn ($card) => !empty($card['is_active']))
            ->groupBy('row_group')
        )
        <section class="panel panel-live editor-preview-shell" style="min-height: auto;">
          <div class="editor-preview-head">
            <div>
              <h3 style="margin: 0;">תצוגה מקדימה לפני שמירה</h3>
              <p class="preview-notice" id="preview-notice" style="padding: 6px 0 0;">
                כאן אפשר לראות איך הדף ייראה לפני שמירה ל־DB. האיפוס מחזיר את כל השינויים למצב ההתחלתי.
              </p>
            </div>
            <div class="editor-preview-actions">
              <button type="button" class="editor-preview-btn" id="preview-btn">רענן תצוגה מקדימה</button>
              <button type="button" class="editor-reset-btn" id="reset-btn">איפוס שינויים</button>
            </div>
          </div>

          <div id="editor-preview-frame" class="editor-preview-frame">
            <header class="top-header pub-header">
              <div class="brand pub-brand">
                <span class="brand-text">
                  <span class="brand-title" id="preview-brand-title">{{ $content['brand_title'] ?? '' }}</span>
                  <span class="brand-tag" id="preview-brand-tagline">{{ $content['brand_tagline'] ?? '' }}</span>
                </span>
              </div>
            </header>

            <section
              id="preview-hero"
              class="hero-banner pub-hero"
              @if(!empty($content['hero_image']) || !empty($default_hero_image_url))
              style="background-image: linear-gradient(rgba(8, 20, 36, 0.35), rgba(8, 20, 36, 0.28)), url('{{ $content['hero_image'] ?: $default_hero_image_url }}');"
              @endif
            >
              <div class="hero-overlay pub-hero-overlay">
                <div class="hero-copy">
                  <h2 id="preview-hero-title">{!! nl2br(e($content['hero_title'] ?? '')) !!}</h2>
                </div>
              </div>
            </section>

            <div id="preview-cards-root" class="pub-content-wrap">
              @foreach($previewRows as $row)
              <section class="cards-grid">
                @foreach($row as $card)
                <article
                  class="{{ in_array($card['card_type'] ?? 'farm', ['farm', 'image'], true) ? 'panel ' : '' }}dynamic-card width-{{ $card['width_units'] ?? 1 }} type-{{ $card['card_type'] ?? 'farm' }}"
                  data-bg="{{ $card['bg_color'] ?? \App\Support\LegacyCms::DEFAULT_CARD_BG_COLOR }}"
                  data-text="{{ $card['text_color'] ?? \App\Support\LegacyCms::DEFAULT_CARD_TEXT_COLOR }}"
                  @if(($card['card_type'] ?? 'farm') === 'image')
                  style="height: {{ max(140, min(700, (int) ($card['card_height'] ?? \App\Support\LegacyCms::DEFAULT_CARD_HEIGHT))) }}px; width: {{ max(30, min(100, (int) ($card['image_card_width'] ?? \App\Support\LegacyCms::DEFAULT_IMAGE_CARD_WIDTH))) }}%; max-width: 100%; justify-self: center;"
                  @elseif(in_array($card['card_type'] ?? 'farm', ['farm', 'image'], true))
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
                    @foreach(array_filter(explode("\n", (string) ($card['body_text'] ?? '')), fn ($line) => trim($line) !== '') as $line)
                    <p>{{ $line }}</p>
                    @endforeach
                    @if(!empty($card['caption']))
                    <small>{{ $card['caption'] }}</small>
                    @endif
                  </div>
                  @endif
                </article>
                @endforeach
              </section>
              @endforeach
            </div>

            <footer class="pub-footer">
              <div class="pub-footer-inner">
                <div class="pub-footer-brand">
                  <strong id="preview-footer-title">{{ $content['brand_title'] ?? '' }}</strong>
                  <span id="preview-footer-tagline">{{ $content['brand_tagline'] ?? '' }}</span>
                </div>
              </div>
            </footer>
          </div>
        </section>

        <section class="panel panel-live" style="min-height: auto;">
          <form method="post" class="live-form" id="editor-form" data-confirm-save>
            @csrf
            <input type="hidden" name="cards_count" id="cards_count" value="{{ count($farm_cards) }}" />

            <label for="page_name">Farm / Page Name</label>
            <input id="page_name" name="page_name" list="pages-list" value="{{ $page_name }}" required />
            <datalist id="pages-list">
              @foreach($pages as $p)
              <option value="{{ $p }}"></option>
              @endforeach
            </datalist>

            <label for="brand_title">brand_title</label>
            <input id="brand_title" name="brand_title" value="{{ \App\Support\LegacyCms::DEFAULT_BRAND_TITLE }}" readonly />
            <label for="brand_tagline">brand_tagline</label>
            <input id="brand_tagline" name="brand_tagline" value="{{ $content['brand_tagline'] ?? '' }}" />
            <label for="hero_title">hero_title</label>
            <textarea id="hero_title" name="hero_title" rows="2">{{ $content['hero_title'] ?? '' }}</textarea>
            <label for="hero_image">hero_image URL</label>
            <input id="hero_image" name="hero_image" value="{{ $content['hero_image'] ?? '' }}" />
            <label for="hero_image_upload">העלאת תמונת Hero מהמחשב</label>
            <input id="hero_image_upload" type="file" accept="image/*" />

            <hr />
            <div class="editor-cards-head">
              <strong>FARM Cards</strong>
              <button type="button" class="lang-toggle" id="add-card-btn">+ הוסף כרטיס</button>
            </div>

            <div id="cards-container">
              @foreach($farm_cards as $idx => $card)
              <div class="card-editor-item{{ $idx === 0 ? '' : ' is-collapsed' }}">
                <div class="card-editor-toolbar">
                  <button type="button" class="card-collapse-btn" aria-expanded="{{ $idx === 0 ? 'true' : 'false' }}">
                    <span class="card-collapse-icon">{{ $idx === 0 ? '-' : '+' }}</span>
                    <span class="card-editor-summary">#{{ $idx + 1 }} | {{ strtoupper($card['card_type'] ?? 'farm') }} | {{ $card['title'] ?: ($card['card_key'] ?? 'card') }}</span>
                  </button>
                  <div class="card-editor-toolbar-actions">
                    <button type="button" class="lang-toggle delete-card-btn">Delete</button>
                  </div>
                </div>
                <div class="card-editor-fields{{ $idx === 0 ? '' : ' collapsed' }}">
                  <label>card_key</label>
                  <input name="card_key_{{ $idx }}" value="{{ $card['card_key'] ?? '' }}" />
                  <label>card_type</label>
                  <select name="card_type_{{ $idx }}" style="width: 100%; padding: 8px; border-radius: 6px;">
                    @foreach(['farm' => 'farm', 'text' => 'text only', 'image' => 'image block', 'heading' => 'heading', 'divider' => 'divider / bar'] as $value => $label)
                    <option value="{{ $value }}" {{ ($card['card_type'] ?? 'farm') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                  </select>
                  <label>title</label>
                  <input name="card_title_{{ $idx }}" value="{{ $card['title'] ?? '' }}" />
                  <label>body_text</label>
                  <textarea name="card_body_{{ $idx }}" rows="3">{{ $card['body_text'] ?? '' }}</textarea>
                  <label>bg_color</label>
                  <input name="card_bg_{{ $idx }}" type="color" value="{{ $card['bg_color'] ?? \App\Support\LegacyCms::DEFAULT_CARD_BG_COLOR }}" />
                  <label>text_color</label>
                  <input name="card_text_{{ $idx }}" type="color" value="{{ $card['text_color'] ?? \App\Support\LegacyCms::DEFAULT_CARD_TEXT_COLOR }}" />
                  <div class="editor-mini-actions">
                    <button type="button" class="editor-mini-btn apply-default-colors-btn">צבעי אתר</button>
                  </div>
                  <label>width_units (1-3)</label>
                  <input name="card_width_{{ $idx }}" type="number" min="1" max="3" value="{{ $card['width_units'] ?? 1 }}" />
                  <label>גובה FARM (px)</label>
                  <input name="card_height_{{ $idx }}" type="number" min="140" max="700" value="{{ $card['card_height'] ?? \App\Support\LegacyCms::DEFAULT_CARD_HEIGHT }}" />
                  <label>sort_order</label>
                  <input name="card_sort_{{ $idx }}" type="number" value="{{ $card['sort_order'] ?? $idx + 1 }}" />
                  <label>row_group</label>
                  <input name="card_row_{{ $idx }}" type="number" min="1" value="{{ $card['row_group'] ?? 1 }}" />
                  <label>image_url</label>
                  <input name="card_image_{{ $idx }}" value="{{ $card['image_url'] ?? '' }}" class="image-url-field" />
                  <label>Upload image from computer</label>
                  <input type="file" accept="image/*" class="card-image-upload" />
                  <label>גובה תמונה (px)</label>
                  <input name="card_image_height_{{ $idx }}" type="number" min="80" max="520" value="{{ $card['image_height'] ?? \App\Support\LegacyCms::DEFAULT_CARD_IMAGE_HEIGHT }}" />
                  <label>רוחב כרטיס IMAGE (%)</label>
                  <input name="card_image_width_{{ $idx }}" type="number" min="30" max="100" value="{{ $card['image_card_width'] ?? \App\Support\LegacyCms::DEFAULT_IMAGE_CARD_WIDTH }}" />
                  <label>image_scale</label>
                  <input name="card_scale_{{ $idx }}" type="number" min="30" max="200" value="{{ $card['image_scale'] ?? 100 }}" />
                  <label>image_x</label>
                  <input name="card_x_{{ $idx }}" type="number" min="-100" max="100" value="{{ $card['image_x'] ?? 0 }}" />
                  <label>image_radius</label>
                  <input name="card_radius_{{ $idx }}" type="number" min="0" max="50" value="{{ $card['image_radius'] ?? 0 }}" />
                  <label>caption</label>
                  <input name="card_caption_{{ $idx }}" value="{{ $card['caption'] ?? '' }}" />
                  <label>טקסט לכפתור / קישור</label>
                  <input name="card_link_label_{{ $idx }}" value="{{ $card['link_label'] ?? '' }}" />
                  <label>לינק לאתר / קובץ</label>
                  <input name="card_link_url_{{ $idx }}" value="{{ $card['link_url'] ?? '' }}" />
                  <label>העלאת קובץ להורדה</label>
                  <input type="file" class="card-link-file-upload" />
                  <label><input name="card_link_download_{{ $idx }}" type="checkbox" {{ !empty($card['link_is_download']) ? 'checked' : '' }} /> קישור להורדה</label>
                  <p class="editor-link-hint">אפשר לשים כאן כתובת של אתר חיצוני או קישור לקובץ להורדה מהאתר.</p>
                  <label><input name="card_active_{{ $idx }}" type="checkbox" {{ !empty($card['is_active']) ? 'checked' : '' }} /> active</label>
                </div>
              </div>
              @endforeach
            </div>

            <div style="margin-top: 18px; display: flex; gap: 10px; flex-wrap: wrap;">
              <button type="submit">שמירה</button>
              <button type="button" class="editor-preview-btn" id="preview-btn-bottom">רענן תצוגה מקדימה</button>
              <button type="button" class="editor-reset-btn" id="reset-btn-bottom">איפוס שינויים</button>
            </div>
          </form>
          @if(!empty($message))
          <p style="padding: 8px 12px; color: #b7f5c7; font-weight: 700;">{{ $message }}</p>
          @endif
        </section>
      </main>
    </div>

    <script>
      (function () {
        const editorForm = document.getElementById("editor-form");
        const container = document.getElementById("cards-container");
        const cardsCount = document.getElementById("cards_count");
        const addBtn = document.getElementById("add-card-btn");
        const previewBtn = document.getElementById("preview-btn");
        const previewBtnBottom = document.getElementById("preview-btn-bottom");
        const resetBtn = document.getElementById("reset-btn");
        const resetBtnBottom = document.getElementById("reset-btn-bottom");
        const previewNotice = document.getElementById("preview-notice");
        const previewBrandTitle = document.getElementById("preview-brand-title");
        const previewBrandTagline = document.getElementById("preview-brand-tagline");
        const previewFooterTitle = document.getElementById("preview-footer-title");
        const previewFooterTagline = document.getElementById("preview-footer-tagline");
        const previewHero = document.getElementById("preview-hero");
        const previewHeroTitle = document.getElementById("preview-hero-title");
        const previewCardsRoot = document.getElementById("preview-cards-root");
        const heroImageInput = document.getElementById("hero_image");
        const heroImageUpload = document.getElementById("hero_image_upload");
        const defaultHeroImageUrl = @json($default_hero_image_url ?? '');
        const defaultCardBgColor = @json(\App\Support\LegacyCms::DEFAULT_CARD_BG_COLOR);
        const defaultCardTextColor = @json(\App\Support\LegacyCms::DEFAULT_CARD_TEXT_COLOR);
        const defaultCardLinkLabel = @json(\App\Support\LegacyCms::DEFAULT_CARD_LINK_LABEL);
        const defaultCardHeight = @json(\App\Support\LegacyCms::DEFAULT_CARD_HEIGHT);
        const defaultCardImageHeight = @json(\App\Support\LegacyCms::DEFAULT_CARD_IMAGE_HEIGHT);
        const defaultImageCardWidth = @json(\App\Support\LegacyCms::DEFAULT_IMAGE_CARD_WIDTH);
        const initialCardsMarkup = container.innerHTML;
        const initialFormState = captureFormState();

        function reindex() {
          const rows = Array.from(container.querySelectorAll(".card-editor-item"));
          rows.forEach((row, idx) => {
            row.querySelectorAll("input[name], textarea[name], select[name]").forEach((el) => {
              const name = el.getAttribute("name");
              if (!name) return;
              const base = name.replace(/_\d+$/, "");
              el.setAttribute("name", `${base}_${idx}`);
            });
            updateRowSummary(row, idx);
          });
          cardsCount.value = String(rows.length);
          ensureAccordionState();
        }

        function updateRowSummary(row, idx) {
          const summary = row.querySelector(".card-editor-summary");
          if (!summary) return;
          const type = String(getFieldValue(row, "card_type", "farm")).trim() || "farm";
          const title = String(getFieldValue(row, "card_title", "")).trim();
          const cardKey = String(getFieldValue(row, "card_key", `card_${idx + 1}`)).trim() || `card_${idx + 1}`;
          summary.textContent = `#${idx + 1} | ${type.toUpperCase()} | ${title || cardKey}`;
        }

        function setRowCollapsed(row, collapsed) {
          row.classList.toggle("is-collapsed", collapsed);
          row.querySelector(".card-editor-fields")?.classList.toggle("collapsed", collapsed);
          const toggleBtn = row.querySelector(".card-collapse-btn");
          if (toggleBtn instanceof HTMLButtonElement) {
            toggleBtn.setAttribute("aria-expanded", collapsed ? "false" : "true");
          }
          const icon = row.querySelector(".card-collapse-icon");
          if (icon) icon.textContent = collapsed ? "+" : "-";
        }

        function ensureAccordionState() {
          const rows = Array.from(container.querySelectorAll(".card-editor-item"));
          if (!rows.length) return;
          rows.forEach((row, idx) => updateRowSummary(row, idx));
          if (!rows.some((row) => !row.classList.contains("is-collapsed"))) {
            setRowCollapsed(rows[0], false);
          }
        }

        function uploadHandler(input) {
          if (input.dataset.wired === "1") return;
          input.dataset.wired = "1";
          input.addEventListener("change", async () => {
            const file = input.files?.[0];
            if (!file) return;
            const fd = new FormData();
            fd.append("image", file);
            try {
              const data = await fetchJson("/api/upload-image", fd);
              const row = input.closest(".card-editor-item");
              const urlInput = row?.querySelector(".image-url-field");
              if (urlInput) urlInput.value = data.url;
              if (row) {
                const idx = Array.from(container.querySelectorAll(".card-editor-item")).indexOf(row);
                if (idx >= 0) updateRowSummary(row, idx);
              }
            } catch (err) {
              alert("Upload failed");
            } finally {
              input.value = "";
            }
          });
        }

        function wireHeroUpload() {
          if (!(heroImageUpload instanceof HTMLInputElement) || heroImageUpload.dataset.wired === "1") return;
          heroImageUpload.dataset.wired = "1";
          heroImageUpload.addEventListener("change", async () => {
            const file = heroImageUpload.files?.[0];
            if (!file || !heroImageInput) return;
            const fd = new FormData();
            fd.append("image", file);
            try {
              const data = await fetchJson("/api/upload-image", fd);
              heroImageInput.value = data.url;
            } catch (err) {
              alert(err instanceof Error ? err.message : "Upload failed");
            } finally {
              heroImageUpload.value = "";
            }
          });
        }

        function uploadFileHandler(input) {
          if (input.dataset.wired === "1") return;
          input.dataset.wired = "1";
          input.addEventListener("change", async () => {
            const file = input.files?.[0];
            if (!file) return;
            const fd = new FormData();
            fd.append("file", file);
            try {
              const data = await fetchJson("/api/upload-file", fd);
              const row = input.closest(".card-editor-item");
              const urlInput = row?.querySelector('[name^="card_link_url_"]');
              const labelInput = row?.querySelector('[name^="card_link_label_"]');
              const downloadInput = row?.querySelector('[name^="card_link_download_"]');
              if (urlInput) urlInput.value = data.url;
              if (labelInput && !labelInput.value.trim()) {
                labelInput.value = data.original_name || file.name || "הורדת קובץ";
              }
              if (downloadInput instanceof HTMLInputElement) {
                downloadInput.checked = true;
              }
              if (row) {
                const idx = Array.from(container.querySelectorAll(".card-editor-item")).indexOf(row);
                if (idx >= 0) updateRowSummary(row, idx);
              }
            } catch (err) {
              alert("File upload failed");
            } finally {
              input.value = "";
            }
          });
        }

        function wireUploadInputs(scope = container) {
          scope.querySelectorAll(".card-image-upload").forEach(uploadHandler);
          scope.querySelectorAll(".card-link-file-upload").forEach(uploadFileHandler);
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

        async function fetchJson(url, formData) {
          const res = await fetch(url, {
            method: "POST",
            body: formData,
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
          if (!res.ok) {
            throw new Error(getApiErrorMessage(data, "Upload failed"));
          }
          return data;
        }

        function wireDefaultColorButtons(scope = container) {
          scope.querySelectorAll(".apply-default-colors-btn").forEach((btn) => {
            if (btn.dataset.wired === "1") return;
            btn.dataset.wired = "1";
            btn.addEventListener("click", () => {
              const row = btn.closest(".card-editor-item");
              const bgInput = row?.querySelector('[name^="card_bg_"]');
              const textInput = row?.querySelector('[name^="card_text_"]');
              if (bgInput) bgInput.value = defaultCardBgColor;
              if (textInput) textInput.value = defaultCardTextColor;
            });
          });
        }

        function escapeHtml(value) {
          return String(value)
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;");
        }

        function captureFormState() {
          const state = {};
          editorForm.querySelectorAll("input[name], textarea[name], select[name]").forEach((el) => {
            if (el instanceof HTMLInputElement && el.type === "checkbox") {
              state[el.name] = el.checked;
            } else {
              state[el.name] = el.value;
            }
          });
          return state;
        }

        function restoreFormState(state) {
          container.innerHTML = initialCardsMarkup;
          editorForm.querySelectorAll("input[name], textarea[name], select[name]").forEach((el) => {
            if (!(el.name in state)) return;
            if (el instanceof HTMLInputElement && el.type === "checkbox") {
              el.checked = Boolean(state[el.name]);
            } else {
              el.value = state[el.name];
            }
          });
          wireUploadInputs();
          wireDefaultColorButtons();
          reindex();
        }

        function getFieldValue(row, prefix, fallback = "") {
          const field = row.querySelector(`[name^="${prefix}_"]`);
          if (!field) return fallback;
          if (field instanceof HTMLInputElement && field.type === "checkbox") {
            return field.checked;
          }
          return field.value;
        }

        function getPreviewData() {
          const rows = Array.from(container.querySelectorAll(".card-editor-item")).map((row, idx) => ({
            card_key: String(getFieldValue(row, "card_key", `card_${idx + 1}`)).trim(),
            card_type: String(getFieldValue(row, "card_type", "farm")).trim() || "farm",
            title: String(getFieldValue(row, "card_title", "")).trim(),
            body_text: String(getFieldValue(row, "card_body", "")),
            bg_color: String(getFieldValue(row, "card_bg", defaultCardBgColor)).trim() || defaultCardBgColor,
            text_color: String(getFieldValue(row, "card_text", defaultCardTextColor)).trim() || defaultCardTextColor,
            width_units: Math.max(1, Math.min(3, Number(getFieldValue(row, "card_width", 1)) || 1)),
            card_height: Math.max(140, Math.min(700, Number(getFieldValue(row, "card_height", defaultCardHeight)) || defaultCardHeight)),
            sort_order: Number(getFieldValue(row, "card_sort", idx + 1)) || idx + 1,
            row_group: Math.max(1, Number(getFieldValue(row, "card_row", 1)) || 1),
            image_url: String(getFieldValue(row, "card_image", "")).trim(),
            image_height: Math.max(80, Math.min(520, Number(getFieldValue(row, "card_image_height", defaultCardImageHeight)) || defaultCardImageHeight)),
            image_card_width: Math.max(30, Math.min(100, Number(getFieldValue(row, "card_image_width", defaultImageCardWidth)) || defaultImageCardWidth)),
            image_scale: Math.max(30, Math.min(200, Number(getFieldValue(row, "card_scale", 100)) || 100)),
            image_x: Math.max(-100, Math.min(100, Number(getFieldValue(row, "card_x", 0)) || 0)),
            image_radius: Math.max(0, Math.min(50, Number(getFieldValue(row, "card_radius", 0)) || 0)),
            caption: String(getFieldValue(row, "card_caption", "")).trim(),
            link_label: String(getFieldValue(row, "card_link_label", "")).trim(),
            link_url: String(getFieldValue(row, "card_link_url", "")).trim(),
            link_is_download: Boolean(getFieldValue(row, "card_link_download", false)),
            is_active: Boolean(getFieldValue(row, "card_active", false)),
          }));

          return {
            brand_title: document.getElementById("brand_title")?.value?.trim() || "",
            brand_tagline: document.getElementById("brand_tagline")?.value?.trim() || "",
            hero_title: document.getElementById("hero_title")?.value || "",
            hero_image: document.getElementById("hero_image")?.value?.trim() || "",
            cards: rows,
          };
        }

        function applyCardVisuals(scope) {
          scope.querySelectorAll(".dynamic-card").forEach((card) => {
            const bg = card.dataset.bg || defaultCardBgColor;
            const text = card.dataset.text || defaultCardTextColor;
            card.style.backgroundColor = bg;
            card.style.color = text;
            const title = card.querySelector("h3");
            if (title) {
              title.style.color = text;
              title.style.backgroundColor = "rgba(0, 0, 0, 0.22)";
            }
          });

          scope.querySelectorAll(".card-image[data-scale]").forEach((img) => {
            const raw = Number(img.dataset.scale || "100");
            const val = Math.max(30, Math.min(200, raw));
            img.style.transform = `scale(${val / 100})`;
            const x = Math.max(-100, Math.min(100, Number(img.dataset.x || "0")));
            img.style.objectPosition = `${50 + x / 2}% center`;
            const radius = Math.max(0, Math.min(50, Number(img.dataset.radius || "0")));
            img.style.borderRadius = `${radius}%`;
          });
        }

        function renderPreview() {
          const data = getPreviewData();
          const heroImage = data.hero_image || defaultHeroImageUrl;

          previewBrandTitle.textContent = data.brand_title;
          previewBrandTagline.textContent = data.brand_tagline;
          previewFooterTitle.textContent = data.brand_title;
          previewFooterTagline.textContent = data.brand_tagline;
          previewHeroTitle.innerHTML = escapeHtml(data.hero_title).replaceAll("\n", "<br />");
          if (heroImage) {
            previewHero.style.backgroundImage = `linear-gradient(rgba(8, 20, 36, 0.35), rgba(8, 20, 36, 0.28)), url("${heroImage}")`;
          } else {
            previewHero.style.backgroundImage = "";
          }

          const groupedCards = data.cards
            .filter((card) => card.is_active)
            .sort((a, b) => {
              if (a.row_group !== b.row_group) return a.row_group - b.row_group;
              return a.sort_order - b.sort_order;
            })
            .reduce((groups, card) => {
              if (!groups.has(card.row_group)) {
                groups.set(card.row_group, []);
              }
              groups.get(card.row_group).push(card);
              return groups;
            }, new Map());

          previewCardsRoot.innerHTML = "";

          groupedCards.forEach((cards) => {
            const section = document.createElement("section");
            section.className = "cards-grid";

            cards.forEach((card) => {
              const article = document.createElement("article");
              article.className = `${["farm", "image"].includes(card.card_type) ? "panel " : ""}dynamic-card width-${card.width_units} type-${card.card_type}`;
              article.dataset.bg = card.bg_color;
              article.dataset.text = card.text_color;
              if (card.card_type === "image") {
                article.style.height = `${card.card_height}px`;
                article.style.width = `${card.image_card_width}%`;
                article.style.maxWidth = "100%";
                article.style.justifySelf = "center";
              } else if (["farm", "image"].includes(card.card_type)) {
                article.style.minHeight = `${card.card_height}px`;
              }

              if (card.card_type === "farm" && card.title) {
                const h3 = document.createElement("h3");
                h3.textContent = card.title;
                article.appendChild(h3);
              }

              if (card.card_type === "text" && card.title) {
                const p = document.createElement("p");
                p.className = "text-block-title";
                p.textContent = card.title;
                article.appendChild(p);
              }

              if (card.card_type === "heading") {
                const h2 = document.createElement("h2");
                h2.className = "heading-block";
                h2.textContent = card.title;
                article.appendChild(h2);
              }

              if (card.card_type === "divider") {
                const hr = document.createElement("hr");
                hr.className = "divider-block";
                article.appendChild(hr);
              }

              if (card.image_url) {
                const imageWrap = document.createElement("div");
                imageWrap.className = "card-image-wrap";
                imageWrap.style.height = card.card_type === "image" ? "100%" : `${card.image_height}px`;
                const img = document.createElement("img");
                img.src = card.image_url;
                img.alt = "";
                img.className = "card-image";
                img.dataset.scale = String(card.image_scale);
                img.dataset.x = String(card.image_x);
                img.dataset.radius = String(card.image_radius);
                imageWrap.appendChild(img);
                article.appendChild(imageWrap);
              }

              if (card.card_type !== "image" && card.card_type !== "divider") {
                const body = document.createElement("div");
                body.className = "card-body";
                String(card.body_text || "")
                  .split("\n")
                  .map((line) => line.trim())
                  .filter(Boolean)
                  .forEach((line) => {
                    const p = document.createElement("p");
                    p.textContent = line;
                    body.appendChild(p);
                  });

                if (card.caption) {
                  const small = document.createElement("small");
                  small.textContent = card.caption;
                  body.appendChild(small);
                }

                if (card.link_url) {
                  const link = document.createElement("a");
                  link.className = "card-link-button";
                  link.href = card.link_is_download && card.link_url.startsWith("/uploads/")
                    ? `${card.link_url}?download=1`
                    : card.link_url;
                  link.textContent = card.link_label || (card.link_is_download ? "הורדת קובץ" : defaultCardLinkLabel);
                  if (card.link_is_download) {
                    link.setAttribute("download", "");
                  } else {
                    link.target = "_blank";
                    link.rel = "noopener noreferrer";
                  }
                  body.appendChild(link);
                }

                article.appendChild(body);
              }

              section.appendChild(article);
            });

            previewCardsRoot.appendChild(section);
          });

          applyCardVisuals(document.getElementById("editor-preview-frame"));
          previewNotice.textContent = "התצוגה עודכנה. השינויים עדיין לא נשמרו ל־DB.";
        }

        wireUploadInputs();
        wireHeroUpload();
        wireDefaultColorButtons();
        ensureAccordionState();
        renderPreview();

        addBtn?.addEventListener("click", () => {
          const idx = container.querySelectorAll(".card-editor-item").length;
          const row = document.createElement("div");
          row.className = "card-editor-item";
          row.innerHTML = `
            <div class="card-editor-toolbar">
              <button type="button" class="card-collapse-btn" aria-expanded="true">
                <span class="card-collapse-icon">-</span>
                <span class="card-editor-summary">#${idx + 1} | FARM | card_${idx + 1}</span>
              </button>
              <div class="card-editor-toolbar-actions">
                <button type="button" class="lang-toggle delete-card-btn">Delete</button>
              </div>
            </div>
            <div class="card-editor-fields">
              <label>card_key</label><input name="card_key_${idx}" value="card_${idx + 1}" />
              <label>card_type</label>
              <select name="card_type_${idx}" style="width: 100%; padding: 8px; border-radius: 6px;">
                <option value="farm">farm</option>
                <option value="text">text only</option>
                <option value="image">image block</option>
                <option value="heading">heading</option>
                <option value="divider">divider / bar</option>
              </select>
              <label>title</label><input name="card_title_${idx}" value="" />
              <label>body_text</label><textarea name="card_body_${idx}" rows="3"></textarea>
              <label>bg_color</label><input name="card_bg_${idx}" type="color" value="${defaultCardBgColor}" />
              <label>text_color</label><input name="card_text_${idx}" type="color" value="${defaultCardTextColor}" />
              <div class="editor-mini-actions">
                <button type="button" class="editor-mini-btn apply-default-colors-btn">צבעי אתר</button>
              </div>
              <label>width_units (1-3)</label><input name="card_width_${idx}" type="number" min="1" max="3" value="1" />
              <label>גובה FARM (px)</label><input name="card_height_${idx}" type="number" min="140" max="700" value="${defaultCardHeight}" />
              <label>sort_order</label><input name="card_sort_${idx}" type="number" value="${idx + 1}" />
              <label>row_group</label><input name="card_row_${idx}" type="number" min="1" value="1" />
              <label>image_url</label><input name="card_image_${idx}" value="" class="image-url-field" />
              <label>Upload image from computer</label><input type="file" accept="image/*" class="card-image-upload" />
              <label>גובה תמונה (px)</label><input name="card_image_height_${idx}" type="number" min="80" max="520" value="${defaultCardImageHeight}" />
              <label>רוחב כרטיס IMAGE (%)</label><input name="card_image_width_${idx}" type="number" min="30" max="100" value="${defaultImageCardWidth}" />
              <label>image_scale</label><input name="card_scale_${idx}" type="number" min="30" max="200" value="100" />
              <label>image_x</label><input name="card_x_${idx}" type="number" min="-100" max="100" value="0" />
              <label>image_radius</label><input name="card_radius_${idx}" type="number" min="0" max="50" value="0" />
              <label>caption</label><input name="card_caption_${idx}" value="" />
              <label>טקסט לכפתור / קישור</label><input name="card_link_label_${idx}" value="" />
              <label>לינק לאתר / קובץ</label><input name="card_link_url_${idx}" value="" />
              <label>העלאת קובץ להורדה</label><input type="file" class="card-link-file-upload" />
              <label><input name="card_link_download_${idx}" type="checkbox" /> קישור להורדה</label>
              <p class="editor-link-hint">אפשר לשים כאן כתובת של אתר חיצוני או קישור לקובץ להורדה מהאתר.</p>
              <label><input name="card_active_${idx}" type="checkbox" checked /> active</label>
            </div>`;
          container.querySelectorAll(".card-editor-item").forEach((item) => setRowCollapsed(item, true));
          container.appendChild(row);
          wireUploadInputs(row);
          wireDefaultColorButtons(row);
          setRowCollapsed(row, false);
          reindex();
        });

        container.addEventListener("input", (e) => {
          const target = e.target;
          if (!(target instanceof HTMLElement)) return;
          const row = target.closest(".card-editor-item");
          if (!row) return;
          const idx = Array.from(container.querySelectorAll(".card-editor-item")).indexOf(row);
          if (idx >= 0) updateRowSummary(row, idx);
        });

        container.addEventListener("click", (e) => {
          const target = e.target;
          if (!(target instanceof HTMLElement)) return;

          const collapseBtn = target.closest(".card-collapse-btn");
          if (collapseBtn) {
            const row = collapseBtn.closest(".card-editor-item");
            if (!row) return;
            const shouldOpen = row.classList.contains("is-collapsed");
            if (shouldOpen) {
              container.querySelectorAll(".card-editor-item").forEach((item) => {
                if (item !== row) setRowCollapsed(item, true);
              });
            }
            setRowCollapsed(row, !shouldOpen);
            return;
          }

          const deleteBtn = target.closest(".delete-card-btn");
          if (!deleteBtn) return;
          deleteBtn.closest(".card-editor-item")?.remove();
          reindex();
        });

        previewBtn?.addEventListener("click", renderPreview);
        previewBtnBottom?.addEventListener("click", renderPreview);

        function resetChanges() {
          restoreFormState(initialFormState);
          renderPreview();
          previewNotice.textContent = "כל השינויים אופסו למצב ההתחלתי.";
        }

        resetBtn?.addEventListener("click", resetChanges);
        resetBtnBottom?.addEventListener("click", resetChanges);
      })();
    </script>
    @include('partials.editor_save_overlay')
  </body>
</html>
