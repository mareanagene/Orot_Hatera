/**
 * שיפורי חוויית משתמש לאתר הציבורי:
 * - כותרת דביקה עם צל בגלילה
 * - גלילה חלקה לקישורים פנימיים
 * - הופעת אלמנטים בגלילה
 * - prefetch של דפים פנימיים ב-hover/touch כדי שהמעבר יהיה כמעט מיידי
 * - מעבר חלק בין מסכים (fade) במקום הבזק לבן
 * - Escape לסגירת מודל צור קשר
 */
(function () {
  "use strict";

  if (!document.body || !document.body.classList.contains("theme-public")) {
    return;
  }

  var root = document.documentElement;
  root.classList.add("pub-js");

  var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function toggleHeaderScrolled() {
    var header = document.querySelector(".pub-header");
    if (!header) return;
    header.classList.toggle("pub-header--scrolled", window.scrollY > 10);
  }

  window.addEventListener("scroll", toggleHeaderScrolled, { passive: true });
  toggleHeaderScrolled();

  document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener("click", function (e) {
      var href = anchor.getAttribute("href");
      if (!href || href === "#" || href.length < 2) return;
      var target = document.querySelector(href);
      if (!target) return;
      e.preventDefault();
      target.scrollIntoView({ behavior: reducedMotion ? "auto" : "smooth", block: "start" });
    });
  });

  var modal = document.getElementById("contact-modal");
  if (modal) {
    document.addEventListener("keydown", function (e) {
      if (e.key !== "Escape") return;
      if (modal.hidden) return;
      var closeBtn = document.getElementById("contact-close");
      if (closeBtn) closeBtn.click();
    });
  }

  initPrefetchAndTransitions();

  if (!reducedMotion) {
    initRevealOnScroll();
  }

  function isInternalNavLink(anchor) {
    if (!(anchor instanceof HTMLAnchorElement)) return false;
    if (anchor.target && anchor.target !== "" && anchor.target !== "_self") return false;
    if (anchor.hasAttribute("download")) return false;
    if (anchor.dataset.noPrefetch !== undefined) return false;
    var href = anchor.getAttribute("href");
    if (!href) return false;
    if (href.indexOf("#") === 0) return false;
    if (/^(mailto:|tel:|javascript:)/i.test(href)) return false;
    var url;
    try {
      url = new URL(anchor.href, window.location.href);
    } catch (e) {
      return false;
    }
    if (url.origin !== window.location.origin) return false;
    if (url.pathname.indexOf("/uploads/") === 0) return false;
    if (url.pathname.indexOf("/api/") === 0) return false;
    if (url.pathname === window.location.pathname && url.search === window.location.search) return false;
    return true;
  }

  function initPrefetchAndTransitions() {
    var prefetched = new Set();
    var inFlight = new Set();
    var navigating = false;

    function prefetch(url) {
      if (!url) return;
      if (prefetched.has(url) || inFlight.has(url)) return;
      inFlight.add(url);

      if ("requestIdleCallback" in window) {
        window.requestIdleCallback(function () {
          performPrefetch(url);
        }, { timeout: 600 });
      } else {
        setTimeout(function () { performPrefetch(url); }, 80);
      }
    }

    function performPrefetch(url) {
      try {
        if (document.querySelector('link[rel="prefetch"][href="' + url + '"]')) {
          prefetched.add(url);
          inFlight.delete(url);
          return;
        }
        var link = document.createElement("link");
        link.rel = "prefetch";
        link.href = url;
        link.as = "document";
        link.onload = function () {
          prefetched.add(url);
          inFlight.delete(url);
        };
        link.onerror = function () {
          inFlight.delete(url);
        };
        document.head.appendChild(link);
      } catch (e) {
        inFlight.delete(url);
      }
    }

    function maybePrefetchFrom(target) {
      var anchor = target && target.closest ? target.closest("a") : null;
      if (!anchor || !isInternalNavLink(anchor)) return;
      prefetch(anchor.href);
    }

    document.addEventListener("mouseover", function (e) { maybePrefetchFrom(e.target); }, { passive: true });
    document.addEventListener("focusin", function (e) { maybePrefetchFrom(e.target); }, { passive: true });
    document.addEventListener("touchstart", function (e) { maybePrefetchFrom(e.target); }, { passive: true });

    if (reducedMotion) return;

    document.body.classList.add("pub-page-enter");
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        document.body.classList.add("pub-page-enter--ready");
      });
    });

    document.addEventListener("click", function (e) {
      if (navigating) return;
      if (e.defaultPrevented) return;
      if (e.button !== 0) return;
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      var anchor = e.target && e.target.closest ? e.target.closest("a") : null;
      if (!anchor || !isInternalNavLink(anchor)) return;

      e.preventDefault();
      navigating = true;
      document.body.classList.add("pub-page-leaving");

      var url = anchor.href;
      setTimeout(function () {
        window.location.href = url;
      }, 180);
    });

    window.addEventListener("pageshow", function (e) {
      if (e.persisted) {
        navigating = false;
        document.body.classList.remove("pub-page-leaving");
        document.body.classList.add("pub-page-enter--ready");
      }
    });
  }

  function initRevealOnScroll() {
    var revealNodes = [];
    var main = document.querySelector("main.pub-page");
    if (main) {
      Array.prototype.forEach.call(main.children, function (child) {
        if (revealNodes.indexOf(child) === -1) revealNodes.push(child);
      });
    }

    document.querySelectorAll(".dynamic-card, .project-card, .org-person").forEach(function (el) {
      if (revealNodes.indexOf(el) === -1) revealNodes.push(el);
    });

    revealNodes.forEach(function (el) {
      el.classList.add("pub-reveal");
    });

    if (!revealNodes.length || !("IntersectionObserver" in window)) {
      revealNodes.forEach(function (el) {
        el.classList.add("is-visible");
      });
      return;
    }

    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          entry.target.classList.add("is-visible");
          io.unobserve(entry.target);
        });
      },
      { root: null, rootMargin: "0px 0px -5% 0px", threshold: 0.06 }
    );

    revealNodes.forEach(function (el) {
      io.observe(el);
    });
  }
})();
