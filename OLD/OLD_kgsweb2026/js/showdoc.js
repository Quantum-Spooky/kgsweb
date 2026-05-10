/**
 * ShowDoc Universal Lightbox
 * Handles opening, printing, and downloading school documents
 */
document.addEventListener('DOMContentLoaded', function() {
    const lightbox = document.getElementById('menu-lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const caption = document.getElementById('lightbox-caption');
    const downloadLink = document.getElementById('download-link');

    if (!lightbox || !lightboxImg) return;

    // Click Listener (Event Delegation)
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('zoomable') || e.target.classList.contains('zoomable-menu')) {
            
            // Set the Image and Download Link
            lightboxImg.src = e.target.src;
            if (downloadLink) {
                downloadLink.href = e.target.src;
            }
            
            // Set the Caption
            if (caption) {
                caption.innerText = e.target.alt || "School Document";
            }

            // Reveal Lightbox
            lightboxImg.style.display = 'block';
            lightbox.style.display = 'flex';
            document.body.style.overflow = 'hidden'; 
        }
    });

    // Close function logic
    const closeKgsLightbox = () => {
        lightbox.style.display = 'none';
        lightboxImg.src = '';
        lightboxImg.style.display = 'none';
        document.body.style.overflow = 'auto';
    };

    // Close Listeners
    const closeBtn = document.querySelector('.lightbox-close');
    if (closeBtn) closeBtn.addEventListener('click', closeKgsLightbox);
    
    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) closeKgsLightbox();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === "Escape" && lightbox.style.display === 'flex') closeKgsLightbox();
    });
});

/**
 *  The Print Function 
 * (Keep this outside the DOMContentLoaded so the button's onclick can find it)
 */
function printLightboxImage() {
    const imgSrc = document.getElementById('lightbox-img').src;
    if (!imgSrc || imgSrc === window.location.href) return;

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Print Document - Kell Grade School</title>
                <style>
                    body { margin: 0; display: flex; justify-content: center; align-items: center; background: #fff; }
                    img { max-width: 100%; height: auto; }
                    @page { margin: 0.5cm; }
                </style>
            </head>
            <body onload="setTimeout(function(){ window.print(); window.close(); }, 500);">
                <img src="${imgSrc}">
            </body>
        </html>
    `);
    printWindow.document.close();
}