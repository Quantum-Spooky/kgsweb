(function() {

	/**
	* Event handler for zoom button clicks
	* Opens a modal with a full-size image (high-DPI if available)
	*/
	function handleZoomClick(e) {
		const zoom = e.target.closest('.kgsweb-display-zoom');
		if (!zoom) return;

		const img = zoom.previousElementSibling;
		if (!img) return;

		// Attempt to load high-res version if img has data-highres attribute
		const src = img.dataset.highres || img.src;

		const modal = document.createElement('div');
		modal.className = 'kgsweb-display-modal';
		modal.style.position = 'fixed';
		modal.style.top = 0;
		modal.style.left = 0;
		modal.style.width = '100vw';
		modal.style.height = '100vh';
		modal.style.background = 'rgba(0,0,0,0.8)';
		modal.style.display = 'flex';
		modal.style.alignItems = 'center';
		modal.style.justifyContent = 'center';
		modal.style.zIndex = 9999;
		modal.style.cursor = 'zoom-out';

		const modalContent = document.createElement('div');
		modalContent.className = 'kgsweb-display-modal-content';
		modalContent.style.maxWidth = '95%';
		modalContent.style.maxHeight = '95%';
		modalContent.style.overflow = 'auto';
		modalContent.style.boxShadow = '0 0 20px rgba(0,0,0,0.5)';
		modalContent.style.borderRadius = '4px';
		modalContent.style.background = '#fff';
		modalContent.style.padding = '4px';

		const modalImg = document.createElement('img');
		modalImg.src = src;
		modalImg.alt = img.alt || '';
		modalImg.style.width = '100%';
		modalImg.style.height = 'auto';
		modalImg.style.display = 'block';

		modalContent.appendChild(modalImg);
		modal.appendChild(modalContent);

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
