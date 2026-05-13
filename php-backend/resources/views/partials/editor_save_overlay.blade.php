<div
  id="save-confirm-modal"
  class="save-modal"
  role="dialog"
  aria-modal="true"
  aria-labelledby="save-confirm-title"
  aria-describedby="save-confirm-text"
  aria-hidden="true"
  hidden
>
  <div class="save-modal__backdrop" data-save-cancel></div>
  <div class="save-modal__panel" role="document">
    <div class="save-modal__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
        <polyline points="17 21 17 13 7 13 7 21" />
        <polyline points="7 3 7 8 15 8" />
      </svg>
    </div>
    <h2 id="save-confirm-title" class="save-modal__title">לאשר שמירת שינויים?</h2>
    <p id="save-confirm-text" class="save-modal__text">
      השמירה תעדכן את התוכן במסד הנתונים ותחליף את הגרסה הקודמת. אחרי האישור לא ניתן לבטל את הפעולה.
    </p>
    <div class="save-modal__actions">
      <button type="button" class="save-modal__btn save-modal__btn--ghost" data-save-cancel>ביטול</button>
      <button type="button" class="save-modal__btn save-modal__btn--primary" id="save-confirm-btn">
        <span class="save-modal__btn-label">כן, לשמור</span>
      </button>
    </div>
  </div>
</div>

<div id="save-progress-overlay" class="save-overlay" aria-hidden="true" hidden>
  <div class="save-overlay__backdrop"></div>
  <div class="save-overlay__card" role="status" aria-live="polite">
    <div class="save-spinner" aria-hidden="true">
      <svg viewBox="0 0 50 50" width="56" height="56">
        <circle class="save-spinner__track" cx="25" cy="25" r="20" fill="none" stroke-width="4" />
        <circle class="save-spinner__arc" cx="25" cy="25" r="20" fill="none" stroke-width="4" />
      </svg>
    </div>
    <p class="save-overlay__title">שומר ל־DB...</p>
    <p class="save-overlay__sub">נא לא לסגור את החלון עד לסיום השמירה</p>
  </div>
</div>

<script>
  (function () {
    "use strict";

    var confirmModal = document.getElementById("save-confirm-modal");
    var confirmBtn = document.getElementById("save-confirm-btn");
    var progressOverlay = document.getElementById("save-progress-overlay");
    if (!confirmModal || !confirmBtn || !progressOverlay) return;

    var forms = Array.prototype.slice.call(document.querySelectorAll("form[data-confirm-save]"));
    if (!forms.length) return;

    var pendingForm = null;
    var pendingSubmitter = null;
    var submitting = false;
    var lastFocus = null;

    forms.forEach(function (form) {
      captureInitialState(form);

      form.addEventListener("input", function () { markDirty(form); }, { passive: true });
      form.addEventListener("change", function () { markDirty(form); }, { passive: true });

      form.addEventListener("submit", function (e) {
        if (submitting) return;
        if (form.dataset.skipConfirm === "1") {
          form.dataset.skipConfirm = "";
          return;
        }
        e.preventDefault();
        pendingForm = form;
        pendingSubmitter = (e.submitter && form.contains(e.submitter)) ? e.submitter : null;
        openConfirmModal();
      });
    });

    confirmBtn.addEventListener("click", function () {
      if (!pendingForm) {
        closeConfirmModal();
        return;
      }
      var form = pendingForm;
      var submitter = pendingSubmitter;
      closeConfirmModal();
      submitting = true;
      showProgressOverlay();

      if (submitter && submitter.name) {
        var hidden = document.createElement("input");
        hidden.type = "hidden";
        hidden.name = submitter.name;
        hidden.value = submitter.value || "";
        hidden.setAttribute("data-submitter-shim", "1");
        form.appendChild(hidden);
      }

      form.dataset.skipConfirm = "1";

      window.setTimeout(function () {
        if (typeof form.requestSubmit === "function") {
          form.requestSubmit(submitter || undefined);
        } else {
          form.submit();
        }
      }, 30);
    });

    document.addEventListener("click", function (e) {
      var target = e.target;
      if (!(target instanceof Element)) return;
      if (target.closest("[data-save-cancel]")) {
        closeConfirmModal();
      }
    });

    document.addEventListener("keydown", function (e) {
      if (e.key !== "Escape") return;
      if (confirmModal.hidden) return;
      closeConfirmModal();
    });

    window.addEventListener("beforeunload", function (e) {
      if (submitting) {
        var msg = "השמירה עדיין רצה. אם תסגרו עכשיו, השינוי יבוטל ויחזור המצב שלפני השמירה. להמשיך לסגור?";
        e.preventDefault();
        e.returnValue = msg;
        return msg;
      }
      var dirtyForm = forms.find(isDirty);
      if (dirtyForm) {
        var msg2 = "יש שינויים שלא נשמרו. אם תסגרו עכשיו, השינוי יימחק ויחזור המצב שלפני השמירה. להמשיך?";
        e.preventDefault();
        e.returnValue = msg2;
        return msg2;
      }
    });

    window.addEventListener("pageshow", function (e) {
      if (e.persisted) {
        submitting = false;
        hideProgressOverlay();
        forms.forEach(captureInitialState);
      }
    });

    function openConfirmModal() {
      lastFocus = document.activeElement;
      confirmModal.hidden = false;
      confirmModal.setAttribute("aria-hidden", "false");
      document.body.classList.add("save-modal-open");
      requestAnimationFrame(function () {
        confirmModal.classList.add("save-modal--visible");
        confirmBtn.focus();
      });
    }

    function closeConfirmModal() {
      confirmModal.classList.remove("save-modal--visible");
      confirmModal.setAttribute("aria-hidden", "true");
      pendingForm = null;
      pendingSubmitter = null;
      window.setTimeout(function () {
        confirmModal.hidden = true;
        document.body.classList.remove("save-modal-open");
        if (lastFocus && typeof lastFocus.focus === "function") {
          lastFocus.focus();
        }
      }, 180);
    }

    function showProgressOverlay() {
      progressOverlay.hidden = false;
      progressOverlay.setAttribute("aria-hidden", "false");
      document.body.classList.add("save-progress-active");
      requestAnimationFrame(function () {
        progressOverlay.classList.add("save-overlay--visible");
      });
    }

    function hideProgressOverlay() {
      progressOverlay.classList.remove("save-overlay--visible");
      progressOverlay.setAttribute("aria-hidden", "true");
      document.body.classList.remove("save-progress-active");
      window.setTimeout(function () {
        progressOverlay.hidden = true;
      }, 180);
    }

    function captureInitialState(form) {
      form.dataset.initialSnapshot = serializeForm(form);
    }

    function markDirty(form) {
      form.dataset.dirty = isDirty(form) ? "1" : "";
    }

    function isDirty(form) {
      if (form.dataset.initialSnapshot === undefined) return false;
      return serializeForm(form) !== form.dataset.initialSnapshot;
    }

    function serializeForm(form) {
      var parts = [];
      var elements = form.elements;
      for (var i = 0; i < elements.length; i++) {
        var el = elements[i];
        if (!el.name) continue;
        if (el.disabled) continue;
        if (el.type === "file" || el.type === "submit" || el.type === "button" || el.type === "reset") continue;
        if ((el.type === "checkbox" || el.type === "radio")) {
          parts.push(el.name + "=" + (el.checked ? "1" : "0"));
        } else {
          parts.push(el.name + "=" + String(el.value));
        }
      }
      return parts.join("&");
    }
  })();
</script>
