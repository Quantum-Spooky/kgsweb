// js/kgsweb-calendar.js
(function() {
  // Query helpers
  function $(sel, ctx = document) { return ctx.querySelector(sel); }
  function $all(sel, ctx = document) { return Array.from(ctx.querySelectorAll(sel)); }

  // Render events list and update pagination buttons
  function renderEvents(container, events, page = 1, perPage = 10) {
    const list = $('.events-list', container);
    if (!list) return;

    list.innerHTML = '';
    const start = (page - 1) * perPage;
    const pageEvents = events.slice(start, start + perPage);

    pageEvents.forEach(ev => {
      const li = document.createElement('li');
      li.innerHTML = `<div class="event-details">
                        <div class="event-title">${ev.title || ev.summary || ''}</div>
                        <div class="event-time">
                          ${new Date(ev.start).toLocaleString()} – ${new Date(ev.end).toLocaleString()}
                        </div>
                      </div>`;
      list.appendChild(li);
    });

    // Update pagination buttons
    const prevBtn = $('.prev', container);
    const nextBtn = $('.next', container);
    if (prevBtn) prevBtn.disabled = page <= 1;
    if (nextBtn) nextBtn.disabled = start + perPage >= events.length;

    // Store current page and total pages
    container.dataset.currentPage = page;
    container.dataset.totalPages = Math.ceil(events.length / perPage);
  }

  // Fetch events from REST endpoint
  async function fetchEvents(container, page = 1, perPage = 100) {
    const calendarId = container.dataset.calendarId || '';
    if (!calendarId) return;

    try {
      const params = new URLSearchParams({ calendar_id: calendarId, page, per_page: perPage });
      let data;

      if (window.KGSWEB?.rest) {
        data = await window.KGSWEB.rest(`events?${params.toString()}`);
      } else {
        const res = await fetch(`/wp-json/kgsweb/v1/events?${params.toString()}`);
        data = await res.json();
      }

      const events = data?.events || [];
      container.dataset.events = JSON.stringify(events);
      renderEvents(container, events, 1, perPage);
    } catch (e) {
      console.error('KGSWeb Calendar fetch error:', e);
    }
  }

  // Initialize a single calendar container
  function initCalendar(container) {
    fetchEvents(container);

    const prevBtn = $('.prev', container);
    const nextBtn = $('.next', container);

    if (prevBtn) prevBtn.addEventListener('click', () => {
      const events = JSON.parse(container.dataset.events || '[]');
      let page = parseInt(container.dataset.currentPage || 1, 10);
      renderEvents(container, events, Math.max(1, page - 1));
    });

    if (nextBtn) nextBtn.addEventListener('click', () => {
      const events = JSON.parse(container.dataset.events || '[]');
      let page = parseInt(container.dataset.currentPage || 1, 10);
      const totalPages = parseInt(container.dataset.totalPages || 1, 10);
      if (page < totalPages) renderEvents(container, events, page + 1);
    });
  }

  // Auto-init all calendar elements on DOMContentLoaded
  document.addEventListener('DOMContentLoaded', () => {
    $all('.kgsweb-calendar').forEach(initCalendar);
  });

})();
