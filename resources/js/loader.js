(function (w, d) {
  "use strict";

  if (w.Ihsan) return;

  // ── Stub API ──────────────────────────────────────────────────────────────
  // Queues calls made before DOMContentLoaded so nothing is lost.
  var queue = [];
  var api = {
    _q: queue,
    open: function (token) {
      queue.push(["open", token]);
    },
  };
  w.Ihsan = api;

  // ── Helpers ───────────────────────────────────────────────────────────────

  // Derive base URL from this script's own src so no URL param is needed.
  function getBase() {
    var el = d.querySelector("script[data-ihsan-loader]");
    if (el && el.src) {
      var m = el.src.match(/^(https?:\/\/[^/]+)/);
      if (m) return m[1];
    }
    return w.location.origin;
  }

  // ── Modal ─────────────────────────────────────────────────────────────────

  var elCache = {};

  function showModal(elData) {
    if (d.querySelector("[data-ihsan-overlay]")) return;

    var base = getBase();
    var isMobile = w.innerWidth <= 768;
    var url;

    url = base + "/donate/" + elData.token + "?popup=1";

    var overlay = d.createElement("div");
    overlay.setAttribute("role", "dialog");
    overlay.setAttribute("aria-modal", "true");
    overlay.setAttribute("data-ihsan-overlay", "true");
    overlay.style.cssText = [
      "position:fixed",
      "inset:0",
      "z-index:2147483647",
      "display:flex",
      "align-items:center",
      "justify-content:center",
      "background:rgba(15,23,42,.5)",
      isMobile ? "padding:0" : "padding:16px",
      "opacity:0",
      "transition:opacity .2s",
    ].join(";");

    var iframe = d.createElement("iframe");
    iframe.src = url;
    iframe.setAttribute(
      "allow",
      "payment *; clipboard-write; autoplay"
    );
    iframe.setAttribute("loading", "eager");
    iframe.style.cssText = [
      "width:100%",
      "height:100%",
      "border:0",
      isMobile ? "border-radius:0" : "border-radius:16px",
      "background:#fff",
      "box-shadow:0 24px 80px rgba(15,23,42,.35)",
      "display:block",
    ].join(";");

    var wrap = d.createElement("div");
    wrap.style.cssText = [
      "position:relative",
      "width:100%",
      isMobile ? "max-width:100%" : "max-width:1100px",
      isMobile ? "height:100%" : "height:90vh",
      isMobile ? "max-height:100%" : "max-height:820px",
      "overflow:hidden",
    ].join(";");

    var closeBtn = d.createElement("button");
    closeBtn.setAttribute("aria-label", "Close");
    closeBtn.innerHTML = "&times;";
    closeBtn.style.cssText = [
      "position:absolute",
      "top:12px",
      "right:12px",
      "width:36px",
      "height:36px",
      "border:0",
      "border-radius:50%",
      "background:#fff",
      "color:#475569",
      "font-size:22px",
      "line-height:1",
      "cursor:pointer",
      "display:flex",
      "align-items:center",
      "justify-content:center",
      "z-index:10",
      "box-shadow:0 2px 8px rgba(0,0,0,.18)",
    ].join(";");

    function close() {
      w.removeEventListener("message", msgHandler);
      overlay.remove();
    }

    function msgHandler(e) {
      if (!e.data) return;
      if (
        e.data.type === "ihsan:close-modal" ||
        e.data.type === "donation-popup-close"
      ) {
        close();
      }
      if (e.data.type === "ihsan:donation-success") {
        // The donation form already shows a success screen with a Close button.
        // Leave the modal open for the donor to close manually.
      }
      if (
        e.data.type === "chip:payment:success" ||
        e.data.type === "chip:payment:failure" ||
        e.data.type === "chip:payment:cancel"
      ) {
        // The iframe handles its own result UI; do not auto-close the modal.
      }
    }

    closeBtn.addEventListener("click", close);
    overlay.addEventListener("click", function (e) {
      if (e.target === overlay) close();
    });
    d.addEventListener("keydown", function kdown(e) {
      if (e.key === "Escape") {
        close();
        d.removeEventListener("keydown", kdown);
      }
    });
    w.addEventListener("message", msgHandler);

    wrap.appendChild(iframe);
    wrap.appendChild(closeBtn);
    overlay.appendChild(wrap);
    d.body.appendChild(overlay);
    requestAnimationFrame(function () {
      overlay.style.opacity = "1";
    });
  }

  function doOpen(token) {
    var base = getBase();

    if (elCache[token]) {
      showModal(elCache[token]);
      return;
    }

    fetch(base + "/api/public/elements/" + encodeURIComponent(token))
      .then(function (r) {
        return r.ok ? r.json() : null;
      })
      .then(function (data) {
        if (!data) return;
        elCache[token] = data;
        showModal(data);
      });
  }

  // ── Trigger wiring ────────────────────────────────────────────────────────

  function bindTrigger(el) {
    if (el._ihsanBound) return;
    el._ihsanBound = true;

    var token = el.getAttribute("data-ihsan") || el.getAttribute("data-ihsan-token");
    if (!token) return;

    // Strip href from anchor triggers so browsers don't show the URL in the
    // status bar on hover — the modal is opened via JavaScript instead.
    if (el.tagName.toLowerCase() === "a") {
      el.removeAttribute("href");
      el.removeAttribute("target");
      el.removeAttribute("rel");
    }

    el.style.setProperty("cursor", "pointer", "important");

    el.addEventListener("click", function (e) {
      e.preventDefault();
      doOpen(token);
    });
  }

  function scanTriggers() {
    d.querySelectorAll("[data-ihsan], [data-ihsan-token]").forEach(bindTrigger);
  }

  // ── Init ──────────────────────────────────────────────────────────────────

  function init() {
    scanTriggers();

    // Upgrade stub: replace queued open calls with real implementation
    api.open = doOpen;

    // Replay anything queued before init
    var pending = queue.splice(0);
    pending.forEach(function (args) {
      if (args[0] === "open") doOpen(args[1]);
    });

    // Watch for elements added dynamically (SPAs, lazy-loaded sections)
    if (w.MutationObserver) {
      var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
          m.addedNodes.forEach(function (node) {
            if (node.nodeType !== 1) return;
            if (
              node.hasAttribute &&
              (node.hasAttribute("data-ihsan") || node.hasAttribute("data-ihsan-token"))
            ) {
              bindTrigger(node);
            }
            if (node.querySelectorAll) {
              node
                .querySelectorAll("[data-ihsan], [data-ihsan-token]")
                .forEach(bindTrigger);
            }
          });
        });
      });
      observer.observe(d.body, { childList: true, subtree: true });
    }
  }

  if (d.readyState === "loading") {
    d.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})(window, document);
