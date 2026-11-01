/* =========================================================
   Selettore lingua IT / EN — vale per tutte le pagine
   Uso: il testo inglese resta nell'HTML, l'italiano va in data-it="…"
   Attributi: data-it-alt, data-it-aria-label, data-it-title,
              data-it-content (meta), data-it-placeholder
   ========================================================= */
(function () {
  var KEY = "mn-lang";
  var ATTRS = ["alt", "aria-label", "title", "content", "placeholder"];

  function saved() {
    try { return localStorage.getItem(KEY); } catch (e) { return null; }
  }
  function save(lang) {
    try { localStorage.setItem(KEY, lang); } catch (e) {}
  }
  function initial() {
    var s = saved();
    if (s === "it" || s === "en") return s;
    var nav = (navigator.language || "en").toLowerCase();
    return nav.indexOf("it") === 0 ? "it" : "en";
  }

  var current = initial();
  document.documentElement.lang = current;

  function apply(lang) {
    current = lang;
    document.documentElement.lang = lang;

    document.querySelectorAll("[data-it]").forEach(function (el) {
      if (el.dataset.en === undefined) el.dataset.en = el.innerHTML;
      el.innerHTML = lang === "it" ? el.dataset.it : el.dataset.en;
    });

    ATTRS.forEach(function (attr) {
      var itKey = "data-it-" + attr;
      var enKey = "data-en-" + attr;
      document.querySelectorAll("[" + itKey + "]").forEach(function (el) {
        if (!el.hasAttribute(enKey)) el.setAttribute(enKey, el.getAttribute(attr) || "");
        el.setAttribute(attr, lang === "it" ? el.getAttribute(itKey) : el.getAttribute(enKey));
      });
    });

    document.querySelectorAll(".lang-toggle button").forEach(function (b) {
      var on = b.dataset.lang === lang;
      b.classList.toggle("is-active", on);
      b.setAttribute("aria-pressed", on);
    });
    var group = document.querySelector(".lang-toggle");
    if (group) group.setAttribute("aria-label", lang === "it" ? "Lingua" : "Language");

    document.dispatchEvent(new CustomEvent("langchange", { detail: lang }));
  }

  function buildToggle() {
    var header = document.querySelector(".site-header");
    if (!header || header.querySelector(".lang-toggle")) return;
    var box = document.createElement("div");
    box.className = "lang-toggle";
    box.setAttribute("role", "group");
    box.innerHTML =
      '<button type="button" data-lang="it" lang="it">IT</button>' +
      '<span aria-hidden="true">/</span>' +
      '<button type="button" data-lang="en" lang="en">EN</button>';
    header.insertBefore(box, header.firstChild);
    box.addEventListener("click", function (e) {
      var b = e.target.closest("button[data-lang]");
      if (!b || b.dataset.lang === current) return;
      save(b.dataset.lang);
      apply(b.dataset.lang);
    });
  }

  function start() {
    buildToggle();
    apply(current);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", start);
  } else {
    start();
  }

  window.siteLang = { get: function () { return current; }, set: function (l) { save(l); apply(l); } };
})();
