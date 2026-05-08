const form = document.getElementById("item-form");
const list = document.getElementById("items-list");

function esc(text) {
  return String(text)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;");
}

async function loadItems() {
  const res = await fetch("/api/items");
  const items = await res.json();

  if (!items.length) {
    list.innerHTML = "<li>אין עדיין פריטים.</li>";
    return;
  }

  list.innerHTML = items
    .map(
      (item) => `
      <li>
        <strong>${esc(item.title)}</strong><br />
        <span>${esc(item.details)}</span><br />
        <small>${new Date(item.created_at).toLocaleString("he-IL")}</small><br />
        <button class="delete" data-id="${item.id}">מחק</button>
      </li>
    `
    )
    .join("");

  list.querySelectorAll("button.delete").forEach((button) => {
    button.addEventListener("click", async () => {
      const id = button.getAttribute("data-id");
      await fetch(`/api/items/${id}`, { method: "DELETE" });
      await loadItems();
    });
  });
}

if (form && list) {
  form.addEventListener("submit", async (event) => {
    event.preventDefault();

    const title = document.getElementById("title").value.trim();
    const details = document.getElementById("details").value.trim();
    if (!title || !details) return;

    const res = await fetch("/api/items", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ title, details }),
    });

    if (!res.ok) {
      alert("שמירה נכשלה, בדוק חיבור למסד נתונים.");
      return;
    }

    form.reset();
    await loadItems();
  });

  loadItems().catch(() => {
    list.innerHTML = "<li>שגיאה בטעינת נתונים מהשרת.</li>";
  });
}
