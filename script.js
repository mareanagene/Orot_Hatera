const translations = { he: {}, en: {} };

const langToggleBtn = document.getElementById("langToggle");
const cookieBanner = document.getElementById("cookieBanner");
const acceptCookiesBtn = document.getElementById("acceptCookies");
const miniPopup = document.getElementById("miniPopup");
const closePopupBtn = document.getElementById("closePopup");
const leadForm = document.getElementById("leadForm");

const COOKIE_KEY = "orot_hatira_cookie_consent";
const POPUP_KEY = "orot_hatira_popup_shown";
const LANG_KEY = "orot_hatira_lang";
let carouselCleanup = null;

function currentLangMap() {
  const lang = document.documentElement.lang === "en" ? "en" : "he";
  return translations[lang];
}

function renderServices(lang) {
  const container = document.getElementById("servicesCards");
  if (!container) return;
  const items = Array.isArray(translations[lang].servicesItems) ? translations[lang].servicesItems : [];
  container.innerHTML = "";
  items.forEach((item) => {
    const card = document.createElement("div");
    card.className = "card";
    const title = document.createElement("h3");
    const desc = document.createElement("p");
    title.textContent = item.title || "";
    desc.textContent = item.desc || "";
    card.appendChild(title);
    card.appendChild(desc);
    container.appendChild(card);
  });
}

function renderGallery(lang) {
  const track = document.getElementById("carouselTrack");
  if (!track) return;
  const items = Array.isArray(translations[lang].galleryItems) ? translations[lang].galleryItems : [];
  track.innerHTML = "";
  items.forEach((item) => {
    const figure = document.createElement("figure");
    figure.className = "carousel-slide";
    const img = document.createElement("img");
    const cap = document.createElement("figcaption");
    img.src = item.src || "";
    img.alt = item.alt || "";
    cap.className = "carousel-caption";
    cap.textContent = item.caption || "";
    figure.appendChild(img);
    figure.appendChild(cap);
    track.appendChild(figure);
  });
}

function setLanguage(lang) {
  const selected = lang === "en" ? "en" : "he";
  document.documentElement.lang = selected;
  document.documentElement.dir = selected === "he" ? "rtl" : "ltr";
  langToggleBtn.textContent = selected === "he" ? "EN" : "HE";
  localStorage.setItem(LANG_KEY, selected);

  document.querySelectorAll("[data-i18n]").forEach((element) => {
    const key = element.getAttribute("data-i18n");
    element.textContent = translations[selected][key] || "";
  });
  renderServices(selected);
  renderGallery(selected);
  if (typeof carouselCleanup === "function") carouselCleanup();
  carouselCleanup = initCarousel();
}

async function loadContentFiles() {
  const mode = new URLSearchParams(window.location.search).get("mode");
  const suffix = mode === "draft" ? ".draft" : "";
  const languages = ["he", "en"];
  let loadedCount = 0;
  await Promise.all(
    languages.map(async (lang) => {
      const response = await fetch(`content/company-profile.${lang}${suffix}.json`, { cache: "no-store" });
      if (!response.ok) {
        throw new Error(`Failed loading content/company-profile.${lang}${suffix}.json`);
      }
      const data = await response.json();
      if (!data || typeof data !== "object") {
        throw new Error(`Invalid JSON in company-profile.${lang}${suffix}.json`);
      }
      translations[lang] = data;
      loadedCount += 1;
    })
  );
  if (loadedCount !== languages.length) {
    throw new Error("Missing one or more JSON language files.");
  }
}

function initCookies() {
  if (!localStorage.getItem(COOKIE_KEY)) {
    cookieBanner.classList.remove("hidden");
  }
}

function showMiniPopupWithDelay() {
  if (sessionStorage.getItem(POPUP_KEY)) {
    return;
  }
  setTimeout(() => {
    miniPopup.classList.remove("hidden");
    sessionStorage.setItem(POPUP_KEY, "true");
  }, 10000);
}

langToggleBtn.addEventListener("click", () => {
  const current = document.documentElement.lang === "he" ? "he" : "en";
  setLanguage(current === "he" ? "en" : "he");
});

acceptCookiesBtn.addEventListener("click", () => {
  localStorage.setItem(COOKIE_KEY, "accepted");
  cookieBanner.classList.add("hidden");
});

closePopupBtn.addEventListener("click", () => {
  miniPopup.classList.add("hidden");
});

leadForm.addEventListener("submit", (event) => {
  event.preventDefault();
  const email = document.getElementById("emailInput").value.trim();
  if (!email) {
    return;
  }
  const subject = encodeURIComponent("פניה מאתר אורות הטירה");
  const body = encodeURIComponent(`שלום, אני מעוניין/ת לקבל מידע נוסף. אימייל: ${email}`);
  window.location.href = `mailto:info@orot-hatira.co.il?subject=${subject}&body=${body}`;
  miniPopup.classList.add("hidden");
  leadForm.reset();
});

function initCarousel() {
  const carousel = document.getElementById("galleryCarousel");
  if (!carousel) return () => {};

  const track = document.getElementById("carouselTrack");
  const viewport = document.getElementById("carouselViewport");
  if (!track || !viewport) return () => {};
  const slides = Array.from(track.querySelectorAll(".carousel-slide"));
  if (!slides.length) return () => {};

  const prevBtn = document.getElementById("galleryPrev");
  const nextBtn = document.getElementById("galleryNext");
  const dotsEl = document.getElementById("galleryDots");
  if (dotsEl) dotsEl.innerHTML = "";

  let index = 0;
  let autoplayTimer = null;
  let paused = false;
  let direction = 1;

  const updateTransform = () => {
    const w = viewport.clientWidth;
    track.style.transform = `translateX(${-index * w}px)`;
  };

  const updateDots = () => {
    const dotBtns = dotsEl.querySelectorAll(".dot");
    dotBtns.forEach((btn, i) => btn.classList.toggle("active", i === index));
  };

  const goTo = (nextIndex) => {
    const count = slides.length;
    index = ((nextIndex % count) + count) % count;
    updateTransform();
    updateDots();
  };

  const createDots = () => {
    if (!dotsEl) return;
    dotsEl.innerHTML = "";
    slides.forEach((_, i) => {
      const dot = document.createElement("button");
      dot.type = "button";
      dot.className = "dot";
      dot.setAttribute("aria-label", `Slide ${i + 1}`);
      dot.addEventListener("click", () => {
        goTo(i);
        restartAutoplay();
      });
      dotsEl.appendChild(dot);
    });
  };

  const startAutoplay = () => {
    if (autoplayTimer) window.clearInterval(autoplayTimer);
    autoplayTimer = window.setInterval(() => {
      if (paused) return;
      goTo(index + direction);
      direction *= -1;
    }, 5500);
  };

  const restartAutoplay = () => startAutoplay();

  const onPrev = () => {
    direction = -1;
    goTo(index - 1);
    restartAutoplay();
  };

  const onNext = () => {
    direction = 1;
    goTo(index + 1);
    restartAutoplay();
  };
  prevBtn?.addEventListener("click", onPrev);
  nextBtn?.addEventListener("click", onNext);

  const onResize = () => updateTransform();
  window.addEventListener("resize", onResize);

  const onEnter = () => {
    paused = true;
  };
  const onLeave = () => {
    paused = false;
    restartAutoplay();
  };
  carousel.addEventListener("mouseenter", onEnter);
  carousel.addEventListener("mouseleave", onLeave);

  carousel.tabIndex = 0;
  const onKeyDown = (e) => {
    if (e.key === "ArrowLeft") {
      e.preventDefault();
      direction = -1;
      goTo(index - 1);
      restartAutoplay();
    }
    if (e.key === "ArrowRight") {
      e.preventDefault();
      direction = 1;
      goTo(index + 1);
      restartAutoplay();
    }
  };
  carousel.addEventListener("keydown", onKeyDown);

  createDots();
  updateDots();
  updateTransform();
  startAutoplay();
  return () => {
    if (autoplayTimer) window.clearInterval(autoplayTimer);
    prevBtn?.removeEventListener("click", onPrev);
    nextBtn?.removeEventListener("click", onNext);
    window.removeEventListener("resize", onResize);
    carousel.removeEventListener("mouseenter", onEnter);
    carousel.removeEventListener("mouseleave", onLeave);
    carousel.removeEventListener("keydown", onKeyDown);
  };
}

async function initApp() {
  try {
    await loadContentFiles();
    const savedLang = localStorage.getItem(LANG_KEY) || "he";
    setLanguage(savedLang);
    initCookies();
    showMiniPopupWithDelay();
  } catch (error) {
    console.error(error);
    alert("JSON content failed to load. Please check content/company-profile.he.json and content/company-profile.en.json");
  }
}

initApp();
