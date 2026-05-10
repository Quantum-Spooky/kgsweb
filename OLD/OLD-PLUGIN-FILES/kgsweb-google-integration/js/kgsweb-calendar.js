// js/kgsweb-calendar.js
(function () {
  // Query helpers
  function $(sel, ctx = document) {
    return ctx.querySelector(sel);
  }
  function $all(sel, ctx = document) {
    return Array.from(ctx.querySelectorAll(sel));
  }

  /**
   * Format event date without local timezone shift
   * Uses UTC components so the raw calendar time (CST in feed) is preserved
   */
  function formatDateUTC(date) {
    return date.toLocaleDateString(undefined, {
      weekday: "short",
      month: "short",
      day: "numeric",
      year: "numeric",
      timeZone: "UTC",
    });
  }

  function formatTimeUTC(date) {
    return date.toLocaleTimeString([], {
      hour: "numeric",
      minute: "2-digit",
      timeZone: "UTC",
    });
  }

  /**
   * Render events list and update pagination
   */
  function renderEvents(container, events, page = 1, perPage = 10) {
    const list = $(".events-list", container);
    if (!list) return;

    list.innerHTML = "";
    const start = (page - 1) * perPage;
    const pageEvents = events.slice(start, start + perPage);

    pageEvents.forEach((ev) => {
      const li = document.createElement("li");

      // Use raw strings as given by API, no local shift
      const startDate = new Date(ev.start);
      const endDate = new Date(ev.end);
      const isAllDay = ev.all_day;

      // Format date line
      const dateLine = formatDateUTC(startDate);

      // Format time line
      let timeLine = "All Day";
      if (!isAllDay) {
        const startTime = formatTimeUTC(startDate);
        const endTime = formatTimeUTC(endDate);
        timeLine = startTime === endTime ? startTime : `${startTime} – ${endTime}`;
      }

      li.innerHTML = `
        <div class="event-details">
          <div class="event-date"><strong>${dateLine}</strong></div>
          <div class="event-title">${ev.title || ev.summary || "(No title)"}</div>
          <div class="event-time">${timeLine}</div>
        </div>
      `;
      list.appendChild(li);
    });

    // Update pagination buttons
    const prevBtn = $(".prev", container);
    const nextBtn = $(".next", container);
    if (prevBtn) prevBtn.disabled = page <= 1;
    if (nextBtn) nextBtn.disabled = start + perPage >= events.length;

    container.dataset.currentPage = page;
    container.dataset.totalPages = Math.ceil(events.length / perPage);
  }

  /**
   * Fetch events from REST API and render first page
   */
  async function fetchEvents(container, perPage = 100) {
    const calendarId = container.dataset.calendarId || "";
    if (!calendarId) return;

    try {
      const params = new URLSearchParams({
        calendar_id: calendarId,
        page: 1,
        per_page: perPage,
      });

      let data;
      if (window.KGSWEB?.rest) {
        data = await window.KGSWEB.rest(`events?${params.toString()}`);
      } else {
        const res = await fetch(`/wp-json/kgsweb/v1/events?${params.toString()}`);
        data = await res.json();
      }

      const events = data?.events || [];
      container.dataset.events = JSON.stringify(events);
      renderEvents(container, events, 1, 10);
    } catch (e) {
      console.error("KGSWeb Calendar fetch error:", e);
    }
  }

  /**
   * Initialize a single calendar container
   */
  function initCalendar(container) {
    fetchEvents(container);

    const prevBtn = $(".prev", container);
    const nextBtn = $(".next", container);

    if (prevBtn)
      prevBtn.addEventListener("click", () => {
        const events = JSON.parse(container.dataset.events || "[]");
        let page = parseInt(container.dataset.currentPage || 1, 10);
        renderEvents(container, events, Math.max(1, page - 1));
      });

    if (nextBtn)
      nextBtn.addEventListener("click", () => {
        const events = JSON.parse(container.dataset.events || "[]");
        let page = parseInt(container.dataset.currentPage || 1, 10);
        const totalPages = parseInt(container.dataset.totalPages || 1, 10);
        if (page < totalPages) renderEvents(container, events, page + 1);
      });
  }

  // Auto-init all calendars
  document.addEventListener("DOMContentLoaded", () => {
    $all(".kgsweb-calendar").forEach(initCalendar);
  });
})();
