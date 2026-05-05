const translations = {
  he: {
    heroTitle: "אורות הטירה",
    heroSubtitle: "חברת קבלנות ותשתיות לתאורת כבישים",
    aboutTitle: "מי אנחנו",
    aboutText:
      "אורות הטירה היא חברה המתמחה בעבודות תשתית ותאורת כבישים. מייסד החברה הוא ג'ורג' זינאתי.",
    sourceLink: "למידע נוסף על החברה",
    servicesTitle: "שירותי החברה",
    service1: "תשתיות תאורה לכבישים",
    service2: "ביצוע והתקנת מערכות תאורה",
    service3: "פרויקטים עירוניים ובינעירוניים",
    galleryTitle: "גלריית פרויקטים",
    contactTitle: "יצירת קשר",
    contactText: "מעוניינים בפרויקט? נשמח לעזור.",
    contactBtn: "שלחו לנו מייל",
    cookieText: "האתר משתמש בקובצי Cookies לשיפור חוויית הגלישה.",
    cookieBtn: "מאשר/ת",
    popupTitle: "רוצים שנחזור אליכם?",
    popupText: "השאירו אימייל ונשלח לכם מידע נוסף.",
    emailLabel: "האימייל שלכם",
    popupBtn: "שליחה"
  },
  en: {
    heroTitle: "Orot Hatira",
    heroSubtitle: "Infrastructure and road lighting contractors",
    aboutTitle: "Who We Are",
    aboutText:
      "Orot Hatira specializes in road lighting and infrastructure projects. The company founder is George Zinati.",
    sourceLink: "More company information",
    servicesTitle: "Our Services",
    service1: "Road lighting infrastructure",
    service2: "Lighting systems setup and installation",
    service3: "Urban and intercity projects",
    galleryTitle: "Project Gallery",
    contactTitle: "Contact Us",
    contactText: "Planning a project? We would be happy to help.",
    contactBtn: "Send us an email",
    cookieText: "This website uses cookies to improve your browsing experience.",
    cookieBtn: "Accept",
    popupTitle: "Would you like us to contact you?",
    popupText: "Leave your email and we will send more details.",
    emailLabel: "Your email",
    popupBtn: "Submit"
  }
};

const langToggleBtn = document.getElementById("langToggle");
const cookieBanner = document.getElementById("cookieBanner");
const acceptCookiesBtn = document.getElementById("acceptCookies");
const miniPopup = document.getElementById("miniPopup");
const closePopupBtn = document.getElementById("closePopup");
const leadForm = document.getElementById("leadForm");

const COOKIE_KEY = "orot_hatira_cookie_consent";
const POPUP_KEY = "orot_hatira_popup_shown";
const LANG_KEY = "orot_hatira_lang";

function setLanguage(lang) {
  const selected = lang === "en" ? "en" : "he";
  document.documentElement.lang = selected;
  document.documentElement.dir = selected === "he" ? "rtl" : "ltr";
  langToggleBtn.textContent = selected === "he" ? "EN" : "HE";
  localStorage.setItem(LANG_KEY, selected);

  document.querySelectorAll("[data-i18n]").forEach((element) => {
    const key = element.getAttribute("data-i18n");
    if (translations[selected][key]) {
      element.textContent = translations[selected][key];
    }
  });
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

const savedLang = localStorage.getItem(LANG_KEY) || "he";
setLanguage(savedLang);
initCookies();
showMiniPopupWithDelay();
