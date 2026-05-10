// js/kgsweb-upload.js
(function() {

    if (typeof KGSwebUpload === 'undefined') {
        console.error('KGSwebUpload object is not defined! Did wp_localize_script run?');
        return;
    }

    // Render folder options recursively
    function renderFolderOptions(select, tree, prefix = '') {
        tree.forEach(node => {
            const label = prefix ? prefix + ' / ' + node.name : node.name || 'Root';
            select.appendChild(new Option(label, node.id));
            if (node.children && node.children.length) {
                renderFolderOptions(select, node.children, label);
            }
        });
    }

    function initUpload(el) {
        const form = el.querySelector('.kgsweb-upload-form');
        const select = el.querySelector(KGSwebUpload.folderSelect);
        const rootId = el.dataset.uploadFolder || el.dataset.folder;

        // Fetch folder tree
        if (select && rootId) {
            fetch(KGSwebUpload.restFoldersUrl + '?root=' + rootId)
                .then(r => r.json())
                .then(tree => {
                    select.innerHTML = '';
                    renderFolderOptions(select, tree);
                })
                .catch(() => {
                    console.error('Failed to fetch folder tree');
                });
        }

        if (!form) return;

        const passInput = form.querySelector('.kgsweb-pass-input');
        const passBtn   = form.querySelector('.kgsweb-pass-submit');
        const passMsg   = form.querySelector('.kgsweb-pass-message');

        const uploadFields = Array.from(form.querySelectorAll('input, select, button'))
            .filter(elm => !elm.classList.contains('kgsweb-pass-input') && !elm.classList.contains('kgsweb-pass-submit') && elm.tagName !== 'SPAN');

        // Hide upload fields until password is correct
        if (passInput && passBtn && passMsg) {
            uploadFields.forEach(f => f.style.display = 'none');

            passBtn.addEventListener('click', () => {
                const password = passInput.value;
                passMsg.textContent = '';
                passMsg.style.color = 'black';

                fetch(KGSwebUpload.restCheckUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': KGSwebUpload.nonce
                    },
                    body: JSON.stringify({ password })
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        passMsg.style.color = 'green';
                        passMsg.textContent = 'Password correct!';
                        passInput.style.display = 'none';
                        passBtn.style.display = 'none';

                        // Show all labels, folder-select, upload status
                        el.querySelectorAll('label, .kgsweb-upload-status, .kgsweb-folder-select').forEach(f => {
                            f.style.display = '';
                        });

                        uploadFields.forEach(f => f.style.display = '');
                    } else {
                        passMsg.style.color = 'red';
                        passMsg.textContent = res.message || 'Invalid password';
                    }
                })
                .catch(() => {
                    passMsg.style.color = 'red';
                    passMsg.textContent = 'Error checking password';
                });
            });
        }

        // Handle file upload
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const fd = new FormData(form);

            fetch(KGSwebUpload.restUploadUrl, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    const status = el.querySelector('.kgsweb-upload-status');
                    if (status) status.textContent = res.message || 'Upload done';
                })
                .catch(() => {
                    const status = el.querySelector('.kgsweb-upload-status');
                    if (status) status.textContent = 'Error uploading file';
                });
        });

        // Show selected file name
        const fileInput = form.querySelector('input[type="file"]');
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                const fileName = this.files[0]?.name || '';
                const status = form.querySelector('.kgsweb-upload-status');
                if (status) status.textContent = fileName;
            });
        }
    }

    // Initialize all upload blocks
    document.addEventListener('DOMContentLoaded', function() {
        const blocks = window.KGSWEB.qsa
            ? window.KGSWEB.qsa('.kgsweb-upload')
            : document.querySelectorAll('.kgsweb-upload');
        blocks.forEach(initUpload);
    });

})();
