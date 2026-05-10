// js/kgsweb-upload.js
(function() {

  /**
   * Event handler for zoom button clicks
   * Opens a modal with the full-size image
   */
  function handleZoomClick(e) {
    const zoomBtn = e.target.closest('.kgsweb-display-zoom-btn');
    if (!zoomBtn) return;

    const inner = zoomBtn.closest('.kgsweb-display-inner');
    if (!inner) return;

    const img = inner.querySelector('img');
    if (!img) return;

    // Create modal
    const modal = document.createElement('div');
    modal.className = 'kgsweb-display-modal';
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.width = '100vw';
    modal.style.height = '100vh';
    modal.style.background = 'rgba(0,0,0,0.85)';
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    modal.style.zIndex = '9999';
    modal.style.cursor = 'zoom-out';

    // Modal content
    const content = document.createElement('div');
    content.className = 'kgsweb-display-modal-content';
    content.style.maxWidth = '95%';
    content.style.maxHeight = '95%';
    content.style.overflow = 'auto';

	const modalImg = document.createElement('img');
	// use data-full if present, fallback to src
	modalImg.src = img.dataset.full || img.src;
	modalImg.alt = img.alt;
	modalImg.style.width = '100%';
	modalImg.style.height = 'auto';
	modalImg.style.display = 'block';

    content.appendChild(modalImg);
    modal.appendChild(content);

    modal.addEventListener('click', () => modal.remove());
    document.body.appendChild(modal);
  }

  /**
   * Optional: add hover effect to show cursor pointer
   */
  document.addEventListener('mouseover', function(e) {
    if (e.target.closest('.kgsweb-display-zoom-btn')) {
      e.target.style.cursor = 'zoom-in';
    }
  });

  /**
   * Initialize event delegation
   */
  document.addEventListener('DOMContentLoaded', function() {
    document.body.addEventListener('click', handleZoomClick);
  });

})();
