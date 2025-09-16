// js/kgsweb-ticker.js
// ===================
// Handles the live ticker functionality for KGSweb.
// Fetches the latest ticker text from the REST API every 60 seconds,
// formats it into two views (scrolling + full text),
// and animates a smooth, infinite marquee effect.
// Also preserves expand/collapse functionality and ensures
// no unnecessary API calls if data hasn't changed.

(function () {

  /**
   * Normalize text by collapsing multiple blank lines into one.
   */
  function normalizeText(text) {
    return text.replace(/(\r\n|\n|\r){2,}/g, "\n");
  }

  /**
   * Build the scrolling ticker text from raw Google Drive text.
   */
  function buildScrollText(text) {
    let t = normalizeText(text);
    t = t.replace(/(\r\n|\n|\r)/g, " | ");
    return t.trim() + " | KGS |";
  }

  /**
   * Build the full text view HTML.
   */
  function buildFullHTML(text) {
    let t = text.replace(/\r\n|\r/g, "\n");
    const lines = t.split("\n").map(l => l.trimEnd());
    const fullLines = [];
    let prevEmpty = false;
    for (const line of lines) {
      const isEmpty = line.trim() === "";
      if (!(isEmpty && prevEmpty)) fullLines.push(line);
      prevEmpty = isEmpty;
    }

    // Escape HTML then convert line breaks to <br>
    const escaped = fullLines.map(l =>
      l.replace(/&/g, "&amp;")
       .replace(/</g, "&lt;")
       .replace(/>/g, "&gt;")
    ).join("\n");

    return escaped.replace(/\n/g, "<br>");
  }

  function initTicker(el) {
    if (!el) return;

    const folder = el.dataset.folder || "";
    const file   = el.dataset.file || "";
    const speed  = parseFloat(el.dataset.speed) || 0.5;

    const container = el.querySelector(".kgsweb-ticker-container");
    const track = el.querySelector(".kgsweb-ticker-track");
    const full = el.querySelector(".kgsweb-ticker-full");
    const btnExpand = el.querySelector(".kgsweb-ticker-expand");
    if (!track || !full || !btnExpand || !container) return;

    let pos = 0;
    let playing = true;
    let trackWidth = 0;
    let currentRawText = "";

    function measureTrack() {
      const inner = track.querySelector(".kgsweb-ticker-inner");
      if (inner) trackWidth = inner.scrollWidth;
    }

    function renderScroll(scrollText) {
      if (!scrollText || !scrollText.trim()) {
        el.style.display = "none";
        console.log("[KGS Ticker] No text available — hiding ticker");
        return;
      }

      const span = document.createElement("span");
      span.className = "kgsweb-ticker-inner";
      span.textContent = scrollText;
      track.innerHTML = "";
      track.appendChild(span);

      const repeatCount = Math.ceil(container.offsetWidth / span.scrollWidth) + 2;
      span.textContent = Array(repeatCount).fill(scrollText).join(" ");

      measureTrack();
      pos = 0;
      track.style.transform = "translateX(0)";
      console.log(`[KGS Ticker] Rendered scroll text (${scrollText.length} chars)`);
    }

    function animate() {
      if (playing && !track.classList.contains("hidden")) {
        pos -= speed;
        if (Math.abs(pos) >= trackWidth) pos = 0;
        track.style.transform = `translateX(${pos}px)`;
      }
      requestAnimationFrame(animate);
    }

    async function fetchAndRender() {
      try {
        const url = `/wp-json/kgsweb/v1/ticker?folder=${encodeURIComponent(folder)}&file=${encodeURIComponent(file)}`;
        console.log(`[KGS Ticker] Fetching from ${url}`);
        const res = await fetch(url);
        if (!res.ok) {
          console.warn("[KGS Ticker] Fetch failed:", res.status);
          return;
        }
        const data = await res.json();
        if (!data.success || !data.ticker) {
          console.log("[KGS Ticker] No ticker text returned (success=false or empty)");
          return;
        }

        const raw = data.ticker;
        if (raw === currentRawText) {
          console.log("[KGS Ticker] No change detected — skipping re-render");
          return;
        }

        console.log("[KGS Ticker] New ticker text received (updating UI)");
        currentRawText = raw;
        const scrollText = buildScrollText(raw);
        const fullHTML = buildFullHTML(raw);

        renderScroll(scrollText);
        full.innerHTML = fullHTML;
      } catch (e) {
        console.error("[KGS Ticker] Fetch error:", e);
      }
    }

    // Pause on hover
    el.addEventListener("mouseenter", () => playing = false);
    el.addEventListener("mouseleave", () => playing = true);

    // Expand/collapse button
    btnExpand.addEventListener("click", () => {
      const expanded = el.classList.toggle("full-visible");
      if (expanded) {
        track.classList.add("hidden");
        full.classList.add("visible");
        btnExpand.querySelector("i").classList.replace("fa-caret-down", "fa-caret-up");
        console.log("[KGS Ticker] Expanded full text view");
      } else {
        track.classList.remove("hidden");
        full.classList.remove("visible");
        btnExpand.querySelector("i").classList.replace("fa-caret-up", "fa-caret-down");
        console.log("[KGS Ticker] Collapsed full text view");
      }
      measureTrack();
      pos = 0;
    });

    window.addEventListener("resize", () => {
      if (!track.classList.contains("hidden")) renderScroll(buildScrollText(currentRawText));
      measureTrack();
      pos = 0;
      console.log("[KGS Ticker] Resized window, re-rendered scroll text");
    });

    fetchAndRender();
    requestAnimationFrame(animate);
    setInterval(fetchAndRender, 60000);
  }

  document.addEventListener("DOMContentLoaded", () => {
    const tickers = window.KGSWEB?.qsa || (sel => Array.from(document.querySelectorAll(sel)));
    tickers(".kgsweb-ticker").forEach(initTicker);
  });

})();