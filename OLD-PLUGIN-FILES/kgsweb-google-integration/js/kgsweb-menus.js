// js/kgsweb-menus.js
(function(){
  /**
   * Injects image + zoom icon for a menu container
   */
  function renderMenu(el, data, type) {
    const cont = el.querySelector('.kgsweb-menu-image');
    if (!cont) return;

    if (data && data.image_url) {
      cont.innerHTML = `
        <img src="${data.image_url}" alt="${type} menu" loading="lazy" />
        <i class="fa fa-search-plus kgsweb-menu-zoom" aria-hidden="true"></i>
      `;
    } else {
      cont.textContent = 'Menu not available.';
    }
  }

  /**
   * Fetches menu payload from REST API and renders
   */
  function loadMenu(el) {
    const type = el.getAttribute('data-type') || 'breakfast';
    KGSWEB.rest('menu?type=' + encodeURIComponent(type))
      .then(data => renderMenu(el, data, type))
      .catch(() => {
        const cont = el.querySelector('.kgsweb-menu-image');
        if (cont) cont.textContent = 'Menu not available.';
      });
  }

  /**
   * Event delegation for zoom clicks
   */
  function handleZoomClick(e) {
    const zoom = e.target.closest('.kgsweb-menu-zoom');
    if (!zoom) return;

    const img = zoom.previousElementSibling;
    if (!img) return;

    const modal = document.createElement('div');
    modal.className = 'kgsweb-menu-modal';
    modal.innerHTML = `
      <div class="kgsweb-menu-modal-content">
        <img src="${img.src}" alt="${img.alt}" />
      </div>`;
    modal.addEventListener('click', () => modal.remove());
    document.body.appendChild(modal);
  }

  /**
   * Initialize menus
   */
  document.addEventListener('DOMContentLoaded', function() {
    // Load each menu via REST
    (window.KGSWEB.qsa || document.querySelectorAll)
      ('.kgsweb-menu')
      .forEach(loadMenu);

    // Global listener for zoom clicks (event delegation)
    document.body.addEventListener('click', handleZoomClick);
  });
})();
