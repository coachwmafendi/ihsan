(function () {
  "use strict";

  var script = document.currentScript;
  if (!script) return;

  var token = script.getAttribute("data-token") || "";
  if (!token) return;

  var apiBase = script.getAttribute("data-api-base") || "";
  var baseUrl = apiBase || (function () {
    var src = script.src || "";
    var m = src.match(/^(https?:\/\/[^\/]+)/);
    return m ? m[1] : window.location.origin;
  })();

  var colors = {
    campaign: "#16a34a",
    campaign_color: "#16a34a",
    blue: "#2563eb",
    teal: "#0d9488",
    green: "#16a34a",
    orange: "#ea580c",
    red: "#dc2626",
    purple: "#9333ea",
    dark: "#1e293b",
  };

  function hexColor(raw) {
    if (!raw) return "#16a34a";
    if (raw[0] === "#") return raw;
    return colors[raw] || colors.campaign;
  }

  function esc(str) {
    var d = document.createElement("div");
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
  }

  function fadeIn(el) {
    requestAnimationFrame(function () {
      el.style.opacity = "1";
    });
  }

  function isVisible(settings) {
    var v = settings.visibility || "desktop_mobile";
    if (v === "desktop_only" && window.innerWidth < 768) return false;
    if (v === "mobile_only" && window.innerWidth >= 768) return false;
    return true;
  }

  function checkoutUrl(el, asPopup) {
    var qs = asPopup ? "?popup=1" : "?embed=1";
    return el.campaign_form_parameter
      ? baseUrl + "/donate/campaign/" + el.campaign_form_parameter + qs
      : baseUrl + "/donate/" + el.token + qs;
  }

  function handleClick(el) {
    var action = el.settings.action || "open_campaign_page";
    if (action === "checkout_modal" || action === "open_checkout_modal") {
      showCheckoutModal(el);
      return;
    }
    window.open(el.campaign_url || checkoutUrl(el), "_blank", "noopener");
  }

  function showCheckoutModal(el, overrideUrl) {
    var isMobileView = window.innerWidth <= 768;
    var overlay = document.createElement("div");
    overlay.setAttribute("role", "dialog");
    overlay.setAttribute("aria-modal", "true");
    overlay.style.cssText = [
      "position:fixed",
      "inset:0",
      "z-index:2147483647",
      "display:flex",
      "align-items:center",
      "justify-content:center",
      "background:rgba(15,23,42,.5)",
      isMobileView ? "padding:0" : "padding:16px",
      "opacity:0",
      "transition:opacity .2s",
    ].join(";");

    var checkUrl = overrideUrl || checkoutUrl(el, true);
    var iframe = document.createElement("iframe");
    iframe.src = checkUrl;
    iframe.setAttribute("allow", "payment *");
    iframe.style.cssText = [
      "width:100%",
      "height:100%",
      "border:0",
      isMobileView ? "border-radius:0" : "border-radius:16px",
      "background:#fff",
      "box-shadow:0 24px 80px rgba(15,23,42,.35)",
      "display:block",
    ].join(";");

    var modalWrap = document.createElement("div");
    modalWrap.style.cssText = [
      "position:relative",
      "width:100%",
      isMobileView ? "max-width:100%" : "max-width:1100px",
      isMobileView ? "height:100%" : "height:90vh",
      isMobileView ? "max-height:100%" : "max-height:820px",
    ].join(";");

    var closeBtn = document.createElement("button");
    closeBtn.setAttribute("aria-label", "Close");
    closeBtn.innerHTML = "&times;";
    closeBtn.style.cssText = [
      "position:absolute",
      isMobileView ? "top:8px" : "top:-16px",
      isMobileView ? "right:8px" : "right:-16px",
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
      "transition:background .15s,color .15s",
      "z-index:10",
      "box-shadow:0 2px 8px rgba(0,0,0,.18)",
    ].join(";");

    closeBtn.addEventListener("mouseenter", function () {
      closeBtn.style.background = "#f1f5f9";
      closeBtn.style.color = "#0f172a";
    });
    closeBtn.addEventListener("mouseleave", function () {
      closeBtn.style.background = "#fff";
      closeBtn.style.color = "#475569";
    });
    closeBtn.addEventListener("click", function () {
      overlay.remove();
    });
    overlay.addEventListener("click", function (e) {
      if (e.target === overlay) overlay.remove();
    });

    function closeOverlay() { overlay.remove(); }

    window.addEventListener("message", function closeHandler(e) {
      if (e.data && e.data.type === "ihsan:close-modal") {
        closeOverlay();
        window.removeEventListener("message", closeHandler);
      }
    });

    modalWrap.appendChild(iframe);
    modalWrap.appendChild(closeBtn);
    overlay.appendChild(modalWrap);
    document.body.appendChild(overlay);
    fadeIn(overlay);
  }

  function renderQrCode(el) {
    var s = el.settings;
    var campaignUrl = el.campaign_url || checkoutUrl(el);
    var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=' + (s.size || 200) + 'x' + (s.size || 200) + '&data=' + encodeURIComponent(campaignUrl) + '&bgcolor=ffffff&color=0f172a&qzone=2';
    var size = s.size || 200;
    var alignment = s.alignment || 'center';
    var label = s.label || 'Scan to donate';
    var marginTop = s.margin_top || 0;
    var marginBottom = s.margin_bottom || 0;

    var wrapper = document.createElement("div");
    wrapper.style.cssText = [
      "text-align:" + alignment,
      "margin-top:" + marginTop + "px",
      "margin-bottom:" + marginBottom + "px",
    ].join(";");

    var img = document.createElement("img");
    img.src = qrUrl;
    img.alt = label;
    img.width = size;
    img.height = size;
    img.style.cssText = "display:block;margin:0 auto;border-radius:12px;border:1px solid #e2e8f0;";

    var caption = document.createElement("p");
    caption.textContent = label;
    caption.style.cssText = "margin:8px 0 0;font-size:14px;font-weight:500;color:#475569;text-align:center;";

    wrapper.appendChild(img);
    wrapper.appendChild(caption);

    if (script.parentNode) {
      script.parentNode.replaceChild(wrapper, script);
    }
  }

  function renderFloatingButton(el) {
    var s = el.settings;
    if (!isVisible(s)) return;

    var posKey = (s.position || "bottom-right").replace(/-/g, "_");
    var positions = {
      bottom_right: { bottom: "20px", right: "20px" },
      bottom_left: { bottom: "20px", left: "20px" },
      top_right: { top: "20px", right: "20px" },
      top_left: { top: "20px", left: "20px" },
      vertical_left_center: { top: "50%", left: "20px", transform: "translateY(-50%)" },
      vertical_right_center: { top: "50%", right: "20px", transform: "translateY(-50%)" },
    };

    var pos = positions[posKey] || positions.bottom_right;
    var color = hexColor(s.color || s.custom_color || "campaign");
    var isVertical = posKey.indexOf("vertical_") === 0;

    var icons = {
      heart: '<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>',
      hand: '<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M23 12.22V15c0 4.97-4.03 9-9 9H9.17c-1.59 0-3.11-.63-4.24-1.76L0 17.5l1.5-1.5c.47-.47 1.08-.73 1.77-.73.46 0 .9.12 1.28.35L7 17.34V4.5C7 3.67 7.67 3 8.5 3S10 3.67 10 4.5v4h1V3.5C11 2.67 11.67 2 12.5 2S14 2.67 14 3.5V8.5h1V4c0-.83.67-1.5 1.5-1.5S18 3.17 18 4v5.5h1V6c0-.83.67-1.5 1.5-1.5S22 5.17 22 6v6.22z"/></svg>',
      star: '<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>',
      gift: '<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M20 6h-2.18c.11-.31.18-.65.18-1 0-1.66-1.34-3-3-3-1.05 0-1.96.54-2.5 1.35l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm11 15H4v-2h16v2zm0-5H4V8h5.08L7 10.83 8.62 12 12 7.01l3.38 4.99L17 10.83 14.92 8H20v6z"/></svg>',
      plus: '<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>',
    };

    var sizes = {
      small: { w: "48px", h: "48px", fs: "12px", pad: "0 14px" },
      medium: { w: "56px", h: "56px", fs: "14px", pad: "0 20px" },
      large: { w: "64px", h: "64px", fs: "16px", pad: "0 24px" },
    };

    var sz = sizes[s.size] || sizes.medium;

    var shapeStyles = {
      pill: "border-radius: 9999px",
      circle: "border-radius: 50%",
      square: "border-radius: 0",
      rounded: "border-radius: 12px",
    };

    var shapeStyle = shapeStyles[s.shape] || shapeStyles.pill;
    var iconHtml = icons[s.icon] || icons.heart;

    var inner = s.shape === "pill"
      ? iconHtml + '<span style="font-weight:600;white-space:' + (isVertical ? "normal" : "nowrap") + ';">' + esc(s.text || s.button_text || "Derma Sekarang") + "</span>"
      : iconHtml;

    var gap = isVertical ? "4px" : "6px";
    var fontSize = isVertical ? "11px" : sz.fs;
    var flexDirection = isVertical ? "column" : "row";
    var heightStyle = isVertical ? "min-height:" + sz.h : "height:" + sz.h;
    var widthStyle = isVertical ? "width:" + sz.w : "min-width:" + sz.w;
    var padding = s.shape === "pill" ? sz.pad : "0";
    var posTransform = pos.transform || "";
    var hoverScale = "scale(1.06)";
    var combinedEnterTransform = posTransform
      ? posTransform + " " + hoverScale
      : hoverScale;

    var btn = document.createElement("div");
    btn.setAttribute("role", "button");
    btn.setAttribute("tabindex", "0");
    btn.style.cssText = [
      "position:fixed",
      "z-index:2147483646",
      "display:inline-flex",
      "flex-direction:" + flexDirection,
      "align-items:center",
      "justify-content:center",
      "gap:" + gap,
      "cursor:pointer",
      "border:0",
      "outline:none",
      "box-shadow:0 4px 16px rgba(0,0,0,.18)",
      "transition:transform .2s,box-shadow .2s",
      "color:#fff",
      "background:" + color,
      widthStyle,
      heightStyle,
      "font-size:" + fontSize,
      "font-weight:600",
      "line-height:1.2",
      "padding:" + padding,
      shapeStyle,
      pos.top ? "top:" + pos.top : "",
      pos.bottom ? "bottom:" + pos.bottom : "",
      pos.left ? "left:" + pos.left : "",
      pos.right ? "right:" + pos.right : "",
      posTransform ? "transform:" + posTransform : "",
    ].filter(Boolean).join(";");

    btn.innerHTML = inner;

    btn.addEventListener("mouseenter", function () {
      btn.style.transform = combinedEnterTransform;
      btn.style.boxShadow = "0 6px 24px rgba(0,0,0,.24)";
    });
    btn.addEventListener("mouseleave", function () {
      btn.style.transform = posTransform || "";
      btn.style.boxShadow = "0 4px 16px rgba(0,0,0,.18)";
    });
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      handleClick(el);
    });

    document.body.appendChild(btn);
  }

  function renderButton(el) {
    var s = el.settings;
    var isMobile = window.innerWidth <= 640;
    var isTablet = window.innerWidth > 640 && window.innerWidth <= 1024;

    var btn = document.createElement("div");
    btn.setAttribute("role", "button");
    btn.setAttribute("tabindex", "0");

    // Derive colour from button_color (Tailwind class names stored in config)
    var colour = hexColor(s.colour || s.color || "campaign");
    var btnColour = s.button_colour || s.button_color || "";
    if (btnColour.indexOf("blue") !== -1) colour = "#2563eb";
    else if (btnColour.indexOf("green") !== -1) colour = "#16a34a";
    else if (btnColour.indexOf("red") !== -1) colour = "#dc2626";
    else if (btnColour.indexOf("orange") !== -1) colour = "#ea580c";
    else if (btnColour.indexOf("purple") !== -1) colour = "#9333ea";
    else if (btnColour.indexOf("teal") !== -1) colour = "#0d9488";
    else if (btnColour.indexOf("dark") !== -1) colour = "#1e293b";

    var radius = parseInt(s.corner_radius, 10);
    if (isNaN(radius)) radius = 8;

    var size = s.button_size || "";
    var padding = isMobile ? "10px 20px" : isTablet ? "11px 24px" : "13px 32px";
    var fontSize = isMobile ? "14px" : isTablet ? "15px" : "16px";
    if (size.indexOf("px-4") !== -1)      padding = isMobile ? "8px 16px" : "10px 20px";
    else if (size.indexOf("px-6") !== -1) padding = isMobile ? "10px 20px" : "12px 28px";
    else if (size.indexOf("px-8") !== -1) padding = isMobile ? "12px 24px" : "14px 36px";
    if (size.indexOf("text-sm") !== -1)  fontSize = isMobile ? "13px" : "14px";
    else if (size.indexOf("text-lg") !== -1) fontSize = isMobile ? "15px" : "17px";
    else if (size.indexOf("text-xl") !== -1) fontSize = isMobile ? "16px" : "18px";

    var gap = isMobile ? "5px" : "7px";
    var iconSize = isMobile ? "16" : "18";

    btn.style.cssText = [
      "display:inline-flex",
      "align-items:center",
      "justify-content:center",
      "gap:" + gap,
      "cursor:pointer",
      "border:0",
      "outline:none",
      "box-shadow:0 3px 12px rgba(0,0,0,.15)",
      "transition:transform .2s,box-shadow .2s,background .2s",
      "color:#fff",
      "background:" + colour,
      "padding:" + padding,
      "font-size:" + fontSize,
      "font-weight:600",
      "border-radius:" + radius + "px",
      "min-width:" + (isMobile ? "80px" : "120px"),
      "max-width:100%",
      "line-height:1.3",
      "white-space:nowrap",
      "letter-spacing:.01em",
      "font-family:system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif",
      "width:auto",
      "box-sizing:border-box",
    ].join(";");

    var heartIcon = '<svg viewBox="0 0 24 24" fill="currentColor" width="' + iconSize + '" height="' + iconSize + '"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>';
    var text = esc(s.button_text || s.text || "Donate Now");
    btn.innerHTML = heartIcon + '<span>' + text + '</span>';

    btn.addEventListener("mouseenter", function () {
      btn.style.transform = "scale(1.04)";
      btn.style.boxShadow = "0 5px 18px rgba(0,0,0,.22)";
    });
    btn.addEventListener("mouseleave", function () {
      btn.style.transform = "";
      btn.style.boxShadow = "0 3px 12px rgba(0,0,0,.15)";
    });
    btn.addEventListener("mousedown", function () {
      btn.style.transform = "scale(.97)";
    });
    btn.addEventListener("mouseup", function () {
      btn.style.transform = "scale(1.04)";
    });
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      handleClick(el);
    });

    if (script.parentNode) {
      script.parentNode.replaceChild(btn, script);
    }
  }

  function renderPopup(el) {
    var s = el.settings;
    var token = el.token;
    var trigger = s.trigger || "after_delay";
    var delay = parseInt(s.delay || s.delay_seconds || 8, 10);
    var frequency = s.frequency || "once_per_day";

    function shouldShow() {
      if (frequency === "once") return !sessionStorage.getItem("ihsan-popup-" + token);
      if (frequency === "once_per_day") {
        var last = localStorage.getItem("ihsan-popup-" + token);
        if (last && Date.now() - parseInt(last, 10) < 86400000) return false;
      }
      if (frequency === "once_per_session") return !sessionStorage.getItem("ihsan-popup-" + token);
      return true;
    }

    function markShown() {
      if (frequency === "once" || frequency === "once_per_session") {
        sessionStorage.setItem("ihsan-popup-" + token, "1");
      }
      if (frequency === "once_per_day") {
        localStorage.setItem("ihsan-popup-" + token, String(Date.now()));
      }
    }

    function show() {
      if (!shouldShow()) return;
      markShown();

      var overlay = document.createElement("div");
      overlay.setAttribute("role", "dialog");
      overlay.setAttribute("aria-modal", "true");
      overlay.style.cssText = [
        "position:fixed",
        "inset:0",
        "z-index:2147483647",
        "display:flex",
        "align-items:center",
        "justify-content:center",
        "background:rgba(15,23,42,.5)",
        "padding:16px",
        "opacity:0",
        "transition:opacity .25s",
      ].join(";");

      var color = hexColor(s.color || "campaign");
      var heading = s.title
        ? '<h3 style="margin:0 0 6px;font-size:18px;font-weight:700;color:#0f172a;">' + esc(s.title) + "</h3>"
        : "";
      var msg = s.message
        ? '<p style="margin:0 0 20px;color:#475569;font-size:14px;line-height:1.6;">' + esc(s.message) + "</p>"
        : "";
      var btnText = esc(s.button_text || s.text || "Derma Sekarang");
      var hasImage = !!s.image_url;
      var isFull = s.layout === "full";
      var closeBtn = '<button data-close style="position:absolute;top:12px;right:12px;width:32px;height:32px;border:0;border-radius:50%;background:rgba(15,23,42,.06);color:#64748b;font-size:20px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s;z-index:2;">&times;</button>';
      var ctaBtn = '<button data-cta style="display:inline-block;background:' + color + ";color:#fff;padding:12px 32px;border:0;border-radius:999px;font-weight:600;font-size:15px;cursor:pointer;transition:transform .2s,box-shadow .2s;box-shadow:0 2px 8px rgba(0,0,0,.14);" + '">' + btnText + "</button>";

      var cardHtml = '<div style="background:#fff;border-radius:16px;max-width:420px;width:100%;box-shadow:0 24px 80px rgba(15,23,42,.28);text-align:center;position:relative;overflow:hidden;">';
      if (isFull && hasImage) {
        cardHtml += '<div style="height:180px;background:url(' + esc(s.image_url) + ') center/cover no-repeat;"></div>';
      }
      cardHtml += '<div style="padding:32px;position:relative;">';
      cardHtml += closeBtn;
      if (!isFull && hasImage) {
        cardHtml += '<img src="' + esc(s.image_url) + '" style="width:100%;height:150px;object-fit:cover;border-radius:12px;margin-bottom:16px;">';
      }
      cardHtml += heading + msg + ctaBtn;
      cardHtml += "</div></div>";

      overlay.innerHTML = cardHtml;

      overlay.addEventListener("click", function (e) {
        var target = e.target;
        if (target === overlay || target.closest("[data-close]")) { overlay.remove(); return; }
        if (target.closest("[data-cta]")) { overlay.remove(); handleClick(el); }
      });

      document.body.appendChild(overlay);
      fadeIn(overlay);
    }

    function schedule() {
      if (trigger === "after_delay") { setTimeout(show, delay * 1000); }
      else if (trigger === "immediately") { show(); }
      else if (trigger === "on_scroll") {
        var fired = false;
        window.addEventListener("scroll", function () { if (!fired && window.scrollY > 300) { fired = true; show(); } });
      }
      else if (trigger === "on_exit") {
        var fired = false;
        document.addEventListener("mouseleave", function (e) { if (e.clientY <= 0 && !fired) { fired = true; show(); } });
      }
    }

    schedule();
  }

  function renderForm(el) {
    var formUrl = el.campaign_form_parameter
      ? baseUrl + "/donate/campaign/" + el.campaign_form_parameter + "?embed=1"
      : baseUrl + "/donate/" + el.token + "?embed=1";

    var wrapper = document.createElement("div");
    wrapper.style.cssText = "max-width:100%;";

    var iframe = document.createElement("iframe");
    iframe.src = formUrl;
    iframe.setAttribute("width", "100%");
    iframe.setAttribute("height", "600");
    iframe.setAttribute("frameborder", "0");
    iframe.setAttribute("allow", "payment *");
    iframe.setAttribute("scrolling", "no");
    iframe.style.cssText = "border:0;border-radius:16px;display:block;overflow:hidden;";

    wrapper.appendChild(iframe);

    window.addEventListener("message", function (e) {
      if (!e.data || e.data.type !== "ihsan:open-modal") return;
      var qs = "?popup=1&step=2";
      if (e.data.amount) qs += "&amount=" + encodeURIComponent(e.data.amount);
      if (e.data.frequency) qs += "&frequency=" + encodeURIComponent(e.data.frequency);
      if (e.data.currency) qs += "&currency=" + encodeURIComponent(e.data.currency);
      var modalUrl = el.campaign_form_parameter
        ? baseUrl + "/donate/campaign/" + el.campaign_form_parameter + qs
        : baseUrl + "/donate/" + el.token + qs;
      showCheckoutModal(el, modalUrl);
    });

    if (script.parentNode) {
      script.parentNode.replaceChild(wrapper, script);
    }
  }

  function renderLink(el) {
    var s = el.settings;
    var campaignUrl = el.campaign_url || checkoutUrl(el);
    var href = s.url || campaignUrl;
    var text = s.text || "Derma Sekarang";
    var alignment = s.alignment || "left";
    var style = s.style || "button";
    var color = hexColor(s.color || "campaign");

    var sizes = {
      small: { font: "14px", padding: "8px 16px" },
      medium: { font: "16px", padding: "10px 24px" },
      large: { font: "18px", padding: "12px 32px" },
    };

    var sz = sizes[s.size] || sizes.medium;

    var link = document.createElement("a");
    link.href = href;
    link.textContent = text;
    link.target = "_blank";
    link.rel = "noopener";
    link.style.display = "inline-block";
    link.style.textDecoration = "none";
    link.style.textAlign = "center";

    if (style === "button") {
      link.style.cssText = [
        "display:inline-flex",
        "align-items:center",
        "justify-content:center",
        "gap:6px",
        "cursor:pointer",
        "border:0",
        "outline:none",
        "box-shadow:0 2px 8px rgba(0,0,0,.12)",
        "transition:transform .2s,box-shadow .2s,background .2s",
        "color:#fff",
        "background:" + color,
        "padding:" + sz.padding,
        "font-size:" + sz.font,
        "font-weight:600",
        "border-radius:8px",
        "min-width:120px",
        "line-height:1.3",
        "white-space:nowrap",
        "letter-spacing:.01em",
        "font-family:system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif",
        "text-decoration:none",
      ].join(";");
    } else {
      link.style.cssText = [
        "color:" + color,
        "font-size:" + sz.font,
        "font-weight:500",
        "text-decoration:underline",
        "text-underline-offset:2px",
      ].join(";");
    }

    var action = s.action || "open_campaign_page";
    if (action === "checkout_modal" || action === "open_checkout_modal") {
      link.href = "#";
      link.removeAttribute("target");
      link.removeAttribute("rel");
      link.addEventListener("click", function (e) {
        e.preventDefault();
        showCheckoutModal(el);
      });
    }

    link.addEventListener("mouseenter", function () {
      if (style === "button") {
        link.style.transform = "scale(1.04)";
        link.style.boxShadow = "0 4px 12px rgba(0,0,0,.18)";
      }
    });
    link.addEventListener("mouseleave", function () {
      if (style === "button") {
        link.style.transform = "";
        link.style.boxShadow = "0 2px 8px rgba(0,0,0,.12)";
      }
    });

    var wrapper = document.createElement("div");
    wrapper.style.textAlign = alignment;

    wrapper.appendChild(link);

    if (script.parentNode) {
      script.parentNode.replaceChild(wrapper, script);
    }
  }

  var renderers = {
    "floating-button": renderFloatingButton,
    floating_button: renderFloatingButton,
    button: renderButton,
    popup: renderPopup,
    form: renderForm,
    "qr-code": renderQrCode,
    qr_code: renderQrCode,
    link: renderLink,
  };

  function renderFromData(el) {
    var renderer = renderers[el.type] || renderers[el.type.replace(/-/g, "_")];
    if (renderer) renderer(el);
  }

  function ready(fn) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn);
    } else {
      fn();
    }
  }

  fetch(baseUrl + "/api/public/elements/" + encodeURIComponent(token))
    .then(function (res) {
      if (!res.ok) throw new Error("API returned " + res.status);
      return res.json();
    })
    .then(function (data) {
      ready(function () { renderFromData(data); });
    })
    .catch(function () {
      // Fallback: render from data-attributes (backward compat)
      var el = {
        type: script.getAttribute("data-type") || "",
        token: token,
        is_active: true,
        campaign_url: script.getAttribute("data-campaign-url") || baseUrl + "/donate/" + token,
        campaign_form_parameter: script.getAttribute("data-campaign-form-parameter") || "",
        settings: {
          text: script.getAttribute("data-text") || "Derma Sekarang",
          button_text: script.getAttribute("data-button-text") || "Derma Sekarang",
          title: script.getAttribute("data-title") || "Jom Menyumbang",
          message: script.getAttribute("data-message") || "",
          action: script.getAttribute("data-action") || "open_campaign_page",
          position: script.getAttribute("data-position") || "bottom-right",
          color: script.getAttribute("data-color") || "campaign",
          icon: script.getAttribute("data-icon") || "heart",
          shape: script.getAttribute("data-shape") || "pill",
          size: script.getAttribute("data-size") || "medium",
          visibility: script.getAttribute("data-visibility") || "desktop_mobile",
          trigger: script.getAttribute("data-trigger") || "after_delay",
          delay_seconds: parseFloat(script.getAttribute("data-delay-seconds")) || 8,
          frequency: script.getAttribute("data-frequency") || "once_per_day",
        },
      };
      ready(function () { renderFromData(el); });
    });
})();
