<div
  id="contact-modal"
  class="contact-modal"
  role="dialog"
  aria-modal="true"
  aria-labelledby="contact-modal-title"
  aria-hidden="true"
  hidden
  data-msg-success="תודה! קיבלנו את הפנייה — נחזור אליך בהקדם."
  data-msg-network="שגיאת רשת. נסו שוב."
>
  <div class="contact-modal__backdrop" id="contact-backdrop" tabindex="-1"></div>
  <div class="contact-modal__panel">
    <button type="button" class="contact-modal__close" id="contact-close" aria-label="סגור">×</button>
    <h2 id="contact-modal-title" class="contact-modal__title">צור קשר</h2>
    <p class="contact-modal__lead">
      השאירו שם, מייל וטלפון (אופציונלי) — נחזור אליכם בהקדם.
    </p>
    <form id="contact-form" class="contact-modal__form">
      <label for="contact-name">שם מלא</label>
      <input id="contact-name" name="full_name" type="text" required maxlength="160" autocomplete="name" />
      <label for="contact-email">אימייל</label>
      <input id="contact-email" name="email" type="email" required maxlength="255" autocomplete="email" />
      <label for="contact-phone">טלפון (אופציונלי)</label>
      <input id="contact-phone" name="phone" type="tel" maxlength="40" autocomplete="tel" inputmode="tel" />
      <label for="contact-note">הערה (אופציונלי)</label>
      <textarea id="contact-note" name="note" rows="2" maxlength="1000"></textarea>
      <p id="contact-message" class="contact-modal__msg" role="status" aria-live="polite"></p>
      <div class="contact-modal__actions">
        <button type="button" class="lang-toggle" id="contact-cancel">ביטול</button>
        <button type="submit" class="contact-modal__submit">שליחה</button>
      </div>
    </form>
  </div>
</div>

<script>
  (function () {
    const modal = document.getElementById("contact-modal");
    const openBtn = document.getElementById("contact-open");
    const closeBtn = document.getElementById("contact-close");
    const cancelBtn = document.getElementById("contact-cancel");
    const backdrop = document.getElementById("contact-backdrop");
    const form = document.getElementById("contact-form");
    const msg = document.getElementById("contact-message");
    if (!modal || !openBtn) return;

    function openModal() {
      modal.hidden = false;
      modal.setAttribute("aria-hidden", "false");
      document.body.style.overflow = "hidden";
    }

    function closeModal() {
      modal.hidden = true;
      modal.setAttribute("aria-hidden", "true");
      document.body.style.overflow = "";
      if (msg) msg.textContent = "";
    }

    openBtn.addEventListener("click", openModal);
    closeBtn?.addEventListener("click", closeModal);
    cancelBtn?.addEventListener("click", closeModal);
    backdrop?.addEventListener("click", closeModal);

    form?.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (!(form instanceof HTMLFormElement)) return;
      const fd = new FormData(form);
      if (msg) msg.textContent = "";
      try {
        const res = await fetch("/api/contact", {
          method: "POST",
          body: fd,
          headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
          },
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          if (msg) msg.textContent = data.error || "אירעה שגיאה. נסו שוב.";
          return;
        }
        if (msg) msg.textContent = modal.getAttribute("data-msg-success") || "";
        form.reset();
        setTimeout(closeModal, 2200);
      } catch (err) {
        if (msg) msg.textContent = modal.getAttribute("data-msg-network") || "";
      }
    });

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && !modal.hidden) closeModal();
    });
  })();
</script>
