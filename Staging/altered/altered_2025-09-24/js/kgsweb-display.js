// js/kgsweb-display.js
(function(){
  /**
   * Injects image + zoom icon for a display container
   */
  function renderDisplay(el, data, type) {
    const cont = el.querySelector('.kgsweb-display-image');
    if (!cont) return;

    if (data && data.image_url) {
      cont.innerHTML = `
        <img src="${data.image_url}" alt="${type} display" loading="lazy" />
        <i class="fa fa-search-plus kgsweb-display-zoom" aria-hidden="true"></i>
      `;
    } else {
      cont.textContent = 'Display not available.';
    }
  }

  /**
   * Fetches display payload from REST API and renders
   */
  function loadDisplay(el) {
    const type = el.getAttribute('data-type') || 'breakfast';
    KGSWEB.rest('display?type=' + encodeURIComponent(type))
      .then(data => renderDisplay(el, data, type))
      .catch(() => {
        const cont = el.querySelector('.kgsweb-display-image');
        if (cont) cont.textContent = 'Display not available.';
      });
  }

  /**
   * Event delegation for zoom clicks
   */
  function handleZoomClick(e) {
    const zoom = e.target.closest('.kgsweb-display-zoom');
    if (!zoom) return;

    const img = zoom.previousElementSibling;
    if (!img) return;

    const modal = document.createElement('div');
    modal.className = 'kgsweb-display-modal';
    modal.innerHTML = `
      <div class="kgsweb-display-modal-content">
        <img src="${img.src}" alt="${img.alt}" />
      </div>`;
    modal.addEventListener('click', () => modal.remove());
    document.body.appendChild(modal);
  }

  /**
   * Initialize display
   */
  document.addEventListener('DOMContentLoaded', function() {
    // Load each display via REST
    (window.KGSWEB.qsa || document.querySelectorAll)
      ('.kgsweb-display')
      .forEach(loadDisplay);

    // Global listener for zoom clicks (event delegation)
    document.body.addEventListener('click', handleZoomClick);
  });
})();
