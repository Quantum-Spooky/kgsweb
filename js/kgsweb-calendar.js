// js/kgsweb-calendar.js
(function(){
  function renderEvents(el, payload){
    // TODO: build UI with pagination controls; use payload.events
    el.textContent = 'Events loaded: ' + (payload.events ? payload.events.length : 0);
  }
  function load(el, page){
    const cal = el.getAttribute('data-calendar-id') || '';
    const params = new URLSearchParams({ calendar_id: cal, page: page||1, per_page: 10 });
    KGSWEB.rest('events?'+params.toString()).then(data=>{
      if (data && data.events) renderEvents(el, data);
    });
  }
  document.addEventListener('DOMContentLoaded', function(){
    (window.KGSWEB.qsa||function(){} )('.kgsweb-events').forEach(el=>load(el,1));
  });
})();