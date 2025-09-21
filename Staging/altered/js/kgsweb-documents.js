/**
 * kgsweb-documents.js
 * Handles expand/collapse toggles for the server-rendered KGSWeb Documents tree.
 * All tree HTML is now generated server-side; this JS only adds toggle functionality.
 */

document.addEventListener('DOMContentLoaded', function() {

    // Skip any trees marked as static (collapsed=false-static)
    if (document.querySelector('.kgsweb-documents-tree[data-static="true"]')) return;

    // Attach click listeners to all toggle buttons
    document.querySelectorAll('.kgsweb-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            const li = toggle.closest('li');
            const childUl = li.querySelector('ul.children');
            if (!childUl) return;

            // Toggle visibility
            if (childUl.style.display === 'none') {
                childUl.style.display = '';
                toggle.textContent = '−';
            } else {
                childUl.style.display = 'none';
                toggle.textContent = '+';
            }
        });
    });

});
