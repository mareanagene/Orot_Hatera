const langSelect = document.getElementById("langSelect");
const statusEl = document.getElementById("status");
const uploadResult = document.getElementById("uploadResult");
const imageInput = document.getElementById("imageInput");
const galleryList = document.getElementById("galleryList");
const servicesList = document.getElementById("servicesList");

let projectDirHandle = null;
let currentData = {};
const AUTO_SAVE_KEY = "orot_hatira_admin_autosave";
let isPickerActive = false;
const SAVE_API_ENDPOINT = "/api/save-json";
const UPLOAD_API_ENDPOINT = "/api/upload-images";

const fieldKeys = [
  "heroTitle", "heroSubtitle", "customTitle", "customText", "aboutTitle", "aboutLead",
  "aboutWhatText", "ownerNameValue", "dataFieldValue", "contactText", "contactBtn",
  "contactNote", "popupText"
];

function setStatus(text, isError = false) {
  statusEl.textContent = text;
  statusEl.style.color = isError ? "#b00020" : "#0a5b2d";
}

function liveFileName() {
  return `company-profile.${langSelect.value}.json`;
}

function draftFileName() {
  return `company-profile.${langSelect.value}.draft.json`;
}

async function loadByName(fileName) {
  const response = await fetch(`content/${fileName}?t=${Date.now()}`);
  if (!response.ok) throw new Error(`Cannot load ${fileName}`);
  return response.json();
}

async function loadFromSite() {
  try {
    currentData = await loadByName(liveFileName());
    populateForm(currentData);
    setStatus("נטען קובץ פרסום חי.");
  } catch (error) {
    const backup = localStorage.getItem(AUTO_SAVE_KEY);
    if (backup) {
      currentData = JSON.parse(backup);
      populateForm(currentData);
      setStatus("לא נטען קובץ חי, נטענה טיוטה מקומית מהדפדפן.");
      return;
    }
    setStatus(`שגיאה בטעינה: ${error.message}`, true);
  }
}

async function loadDraft() {
  try {
    currentData = await loadByName(draftFileName());
    populateForm(currentData);
    setStatus("נטען קובץ טיוטה.");
  } catch (_error) {
    setStatus("לא נמצאה טיוטה. נטען קובץ חי.", false);
    await loadFromSite();
  }
}

function setFieldValue(key, value) {
  const el = document.getElementById(`f_${key}`);
  if (el) el.value = value ?? "";
}

function getFieldValue(key) {
  const el = document.getElementById(`f_${key}`);
  return el ? el.value.trim() : "";
}

function createGalleryItemCard(item = { src: "", alt: "", caption: "" }) {
  const wrap = document.createElement("div");
  wrap.className = "item-card gallery-item";
  const safeSrc = item.src || "";
  const safeAlt = item.alt || "";
  const safeCaption = item.caption || "";
  wrap.innerHTML = `
    <div class="item-grid">
      <label>נתיב תמונה<input type="text" class="g-src" value="${safeSrc}"></label>
      <label>Alt<input type="text" class="g-alt" value="${safeAlt}"></label>
      <label class="full">כיתוב<input type="text" class="g-cap" value="${safeCaption}"></label>
    </div>
    <div class="thumb-row">
      <img class="g-thumb" src="${safeSrc}" alt="${safeAlt}">
      <a class="g-open" href="${safeSrc}" target="_blank" rel="noopener noreferrer">פתח תמונה</a>
    </div>
    <div class="actions"><button type="button" class="danger remove-item">מחק תמונה</button></div>
  `;
  const srcInput = wrap.querySelector(".g-src");
  const altInput = wrap.querySelector(".g-alt");
  const thumb = wrap.querySelector(".g-thumb");
  const openLink = wrap.querySelector(".g-open");
  const refreshThumb = () => {
    const src = srcInput.value.trim();
    const alt = altInput.value.trim();
    thumb.src = src || "";
    thumb.alt = alt || "";
    openLink.href = src || "#";
  };
  srcInput.addEventListener("input", refreshThumb);
  altInput.addEventListener("input", refreshThumb);
  wrap.querySelector(".remove-item").addEventListener("click", () => {
    wrap.remove();
    updatePreview();
  });
  wrap.querySelectorAll("input").forEach((inp) => inp.addEventListener("input", updatePreview));
  return wrap;
}

function createServiceItemCard(item = { title: "", desc: "" }) {
  const wrap = document.createElement("div");
  wrap.className = "item-card service-item";
  wrap.innerHTML = `
    <div class="item-grid">
      <label>כותרת תחום<input type="text" class="s-title" value="${item.title || ""}" placeholder="למשל DEV / FARM"></label>
      <label class="full">תיאור התחום<input type="text" class="s-desc" value="${item.desc || ""}"></label>
    </div>
    <div class="actions"><button type="button" class="danger remove-item">מחק תחום</button></div>
  `;
  wrap.querySelector(".remove-item").addEventListener("click", () => {
    wrap.remove();
    updatePreview();
  });
  wrap.querySelectorAll("input").forEach((inp) => inp.addEventListener("input", updatePreview));
  return wrap;
}

function populateForm(data) {
  fieldKeys.forEach((key) => setFieldValue(key, data[key] || ""));
  galleryList.innerHTML = "";
  servicesList.innerHTML = "";
  (data.galleryItems || []).forEach((item) => galleryList.appendChild(createGalleryItemCard(item)));
  (data.servicesItems || []).forEach((item) => servicesList.appendChild(createServiceItemCard(item)));
  updatePreview();
}

function getGalleryItemsFromForm() {
  return Array.from(galleryList.querySelectorAll(".gallery-item")).map((el) => ({
    src: el.querySelector(".g-src")?.value.trim() || "",
    alt: el.querySelector(".g-alt")?.value.trim() || "",
    caption: el.querySelector(".g-cap")?.value.trim() || ""
  })).filter((x) => x.src);
}

function getServicesItemsFromForm() {
  return Array.from(servicesList.querySelectorAll(".service-item")).map((el) => ({
    title: el.querySelector(".s-title")?.value.trim() || "",
    desc: el.querySelector(".s-desc")?.value.trim() || ""
  })).filter((x) => x.title);
}

function buildJsonFromForm() {
  const next = { ...currentData };
  fieldKeys.forEach((key) => {
    const value = getFieldValue(key);
    if (value) next[key] = value;
  });
  next.galleryItems = getGalleryItemsFromForm();
  next.servicesItems = getServicesItemsFromForm();
  return next;
}

function normalizeEditorJson() {
  return JSON.stringify(buildJsonFromForm(), null, 2);
}

function saveLocalBackup() {
  localStorage.setItem(AUTO_SAVE_KEY, normalizeEditorJson());
}

function updatePreview() {
  ["heroTitle", "heroSubtitle", "customTitle", "customText", "aboutLead", "ownerNameValue", "contactText"].forEach((k) => {
    const el = document.getElementById(`p_${k}`);
    if (el) el.textContent = getFieldValue(k) || "-";
  });
}

async function pickProjectDirectory() {
  setStatus("אין צורך יותר לבחור תיקייה. השמירה והעלאת התמונות מתבצעות דרך local_admin_server.py");
}

async function ensureProjectDirectory() {
  return true;
}

async function writeToContentFile(fileName) {
  const jsonText = normalizeEditorJson();
  const response = await fetch(SAVE_API_ENDPOINT, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      fileName,
      content: jsonText
    })
  });
  if (!response.ok) {
    throw new Error("שמירה לשרת נכשלה. יש להפעיל local_admin_server.py במקום python -m http.server");
  }
  const payload = await response.json();
  if (!payload.ok) {
    throw new Error(payload.error || "שמירה נכשלה");
  }
  localStorage.setItem(AUTO_SAVE_KEY, jsonText);
}

async function saveDraft() {
  try {
    // Save directly to the original live JSON file only.
    await writeToContentFile(liveFileName());
    currentData = buildJsonFromForm();
    setStatus(`נשמר לקובץ המקורי: content/${liveFileName()}`);
    return true;
  } catch (error) {
    setStatus(`שגיאת שמירה: ${error.message}`, true);
    return false;
  }
}

async function publishLive() {
  const ok = await ensureProjectDirectory();
  if (!ok) return setStatus("לפני Publish: לחץ 'בחר תיקיית פרויקט'.", true);
  try {
    await writeToContentFile(liveFileName());
    currentData = buildJsonFromForm();
    setStatus(`פורסם בהצלחה: content/${liveFileName()}`);
  } catch (error) {
    setStatus(`שגיאת פרסום: ${error.message}`, true);
  }
}

function downloadJson() {
  const blob = new Blob([normalizeEditorJson()], { type: "application/json" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = liveFileName();
  a.click();
  URL.revokeObjectURL(url);
  setStatus("הורד גיבוי JSON.");
}

async function uploadImages() {
  try {
    const files = Array.from(imageInput.files || []);
    if (!files.length) return setStatus("לא נבחרו תמונות.", true);
    const filesPayload = await Promise.all(
      files.map(async (file) => {
        const buffer = await file.arrayBuffer();
        let binary = "";
        const bytes = new Uint8Array(buffer);
        bytes.forEach((b) => {
          binary += String.fromCharCode(b);
        });
        return {
          name: file.name,
          contentBase64: btoa(binary)
        };
      })
    );
    const response = await fetch(UPLOAD_API_ENDPOINT, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ files: filesPayload })
    });
    if (!response.ok) {
      throw new Error("העלאה לשרת נכשלה. יש להפעיל local_admin_server.py");
    }
    const payload = await response.json();
    if (!payload.ok) {
      throw new Error(payload.error || "העלאת תמונות נכשלה");
    }
    const saved = payload.saved || [];
    const added = [];
    for (let i = 0; i < saved.length; i += 1) {
      const path = saved[i];
      const file = files[i];
      added.push(path);
      galleryList.appendChild(createGalleryItemCard({ src: path, alt: file?.name || "", caption: file?.name || "" }));
    }
    uploadResult.textContent = "הועלה:\n" + added.map((p) => `- ${p}`).join("\n");
    updatePreview();
    setStatus("התמונות הועלו ונוספו לגלריה.");
  } catch (error) {
    setStatus(`שגיאת העלאה: ${error.message}`, true);
  }
}

function openDraftPreview() {
  const base = `${location.origin}${location.pathname.replace("admin.html", "index.html")}`;
  // Open live preview because save now publishes immediately.
  window.open(base, "_blank");
}

async function saveAndOpenDraftPreview() {
  const ok = await saveDraft();
  if (ok) openDraftPreview();
}

function sendApprovalMail() {
  const target = document.getElementById("approverEmail").value.trim() || "mareanagene8@gmail.com";
  const base = `${location.origin}${location.pathname.replace("admin.html", "index.html")}`;
  const previewUrl = `${base}?mode=draft`;
  const subject = encodeURIComponent("בקשת אישור תצוגה לפני שחרור");
  const body = encodeURIComponent(`שלום,\nמצורף לינק לתצוגת טיוטה לפני שחרור:\n${previewUrl}\n\nלאישור פרסום היכנס/י ל-admin.`);
  window.location.href = `mailto:${target}?subject=${subject}&body=${body}`;
}

function approveSingleUser() {
  setStatus("מנגנון אישור מבוטל. אפשר לשמור/לפרסם ישירות.");
}

document.getElementById("loadFromSiteBtn").addEventListener("click", loadFromSite);
document.getElementById("pickProjectBtn").addEventListener("click", pickProjectDirectory);
document.getElementById("loadDraftBtn").addEventListener("click", loadDraft);
document.getElementById("saveDraftBtn").addEventListener("click", saveDraft);
document.getElementById("openPreviewBtn").addEventListener("click", saveAndOpenDraftPreview);
document.getElementById("sendApprovalMailBtn").addEventListener("click", sendApprovalMail);
document.getElementById("approveBtn").addEventListener("click", approveSingleUser);
document.getElementById("publishBtn").addEventListener("click", publishLive);
document.getElementById("downloadBtn").addEventListener("click", downloadJson);
document.getElementById("pickImagesDirBtn").addEventListener("click", pickProjectDirectory);
document.getElementById("uploadImagesBtn").addEventListener("click", uploadImages);
document.getElementById("addGalleryItemBtn").addEventListener("click", () => galleryList.appendChild(createGalleryItemCard()));
document.getElementById("addServiceItemBtn").addEventListener("click", () => servicesList.appendChild(createServiceItemCard()));
langSelect.addEventListener("change", loadFromSite);
fieldKeys.forEach((key) => document.getElementById(`f_${key}`)?.addEventListener("input", updatePreview));
fieldKeys.forEach((key) => document.getElementById(`f_${key}`)?.addEventListener("input", saveLocalBackup));
galleryList.addEventListener("input", saveLocalBackup);
servicesList.addEventListener("input", saveLocalBackup);

window.addEventListener("beforeunload", saveLocalBackup);
document.getElementById("publishBtn").disabled = false;

loadFromSite();
