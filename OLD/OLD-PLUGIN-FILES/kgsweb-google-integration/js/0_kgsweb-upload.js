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

    async function initUpload(el) {
        const form = el.querySelector('.kgsweb-upload-form');
        if (!form) return;

        // Upload fields container
        const uploadFields = el.querySelector('.kgsweb-upload-fields');
        if (uploadFields) uploadFields.style.display = 'none';

        // Auth elements
        const passInput = form.querySelector('.kgsweb-pass-input');
        const passBtn   = form.querySelector('.kgsweb-pass-submit');
        const passMsg   = form.querySelector('.kgsweb-pass-message');

        const googleBtn = form.querySelector('.kgsweb-google-login');
        const googleMsg = form.querySelector('.kgsweb-google-message');

        // Folder select
        const select = form.querySelector(KGSwebUpload.folderSelect);
        const rootId = el.dataset.uploadFolder || el.dataset.folder;

        console.log('[KGSWEB] Initializing upload block:', el);

        // --- Password login ---
        if (passBtn && passInput && passMsg) {
            passBtn.addEventListener('click', async () => {
                passMsg.textContent = '';
                try {
                   const response = await fetch(KGSwebUpload.restCheckUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    password: passInput.value,
                    nonce: KGSwebUpload.nonce 
                })
            });
            const res = await response.json();
            console.log('[KGSWEB DEBUG] Password check response:', res);

                    if (res.success) {
                        passMsg.style.color = 'green';
                        passMsg.textContent = 'Password correct!';
                        passInput.style.display = 'none';
                        passBtn.style.display = 'none';
                        if (uploadFields) uploadFields.style.display = '';
                    } else {
                        passMsg.style.color = 'red';
                        passMsg.textContent = res.message || 'Invalid password';
                        if (res.debug) console.log('[KGSWEB DEBUG] PHP debug info:', res.debug);
                    }
                } catch (err) {
                    passMsg.style.color = 'red';
                    passMsg.textContent = 'Error checking password';
                    console.error('[KGSWEB DEBUG] Fetch error:', err);
                }
            });
        }

        // --- Google login ---
        if (googleBtn && googleMsg) {
            googleBtn.addEventListener('click', async () => {
                googleMsg.textContent = 'Signing in…';
                try {
                    const token = await getGoogleIdToken(); // implement Google client logic
                    const response = await fetch(KGSwebUpload.restCheckUrl, {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({
							google_token: token,
							nonce: KGSwebUpload.nonce
						})
					});
					const res = await response.json();
                    console.log('[KGSWEB DEBUG] Google login response:', res);

                    if (res.success) {
                        googleMsg.style.color = 'green';
                        googleMsg.textContent = 'Google login success!';
                        googleBtn.style.display = 'none';
                        if (uploadFields) uploadFields.style.display = '';
                    } else {
                        googleMsg.style.color = 'red';
                        googleMsg.textContent = res.message || 'Google login failed';
                        if (res.debug) console.log('[KGSWEB DEBUG] PHP debug info:', res.debug);
                    }
                } catch (e) {
                    googleMsg.style.color = 'red';
                    googleMsg.textContent = 'Error during Google login';
                    console.error(e);
                }
            });
        }

        // --- Folder tree fetch ---
        if (select && rootId) {
            fetch(KGSwebUpload.restFoldersUrl + '?root=' + rootId)
                .then(r => r.json())
                .then(tree => {
                    select.innerHTML = '';
                    renderFolderOptions(select, tree);
                })
                .catch(() => console.error('Failed to fetch folder tree'));
        }

        // --- File input display ---
        const fileInput = form.querySelector('input[type="file"]');
        if (fileInput) {
            fileInput.addEventListener('change', () => {
                const status = el.querySelector('.kgsweb-upload-status');
                if (status) status.textContent = fileInput.files[0]?.name || '';
            });
        }

        // --- Upload submit ---
        form.addEventListener('submit', e => {
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
    }

    document.addEventListener('DOMContentLoaded', () => {
        const blocks = window.KGSWEB.qsa
            ? window.KGSWEB.qsa('.kgsweb-upload')
            : document.querySelectorAll('.kgsweb-upload');
        blocks.forEach(initUpload);
    });

})();
