/**
 * js/kgsweb-upload.js
 */

document.addEventListener('DOMContentLoaded', () => {

    // --- OAuth window return redirect handler (added) ---
    // If Google login opened a popup and returned here
    if (window.opener) {
        window.opener.location.href = kgsweb_upload_ajax.originUrl || window.opener.location.href;
        window.close();
    }

    // --- Globals / constants ---
    const SESSION_KEY = 'kgsweb_upload_verified';
    const VERIFIED_AT_KEY = 'kgsweb_upload_verified_at';
    const VERSION_KEY = 'kgsweb_settings_version';
    let authorized = false;
    let verifiedPassword = ''; // used only in password cases
    const rootFolder = kgsweb_upload_ajax.uploadRootFolderId || '';

    const el = (q, root = document) => root ? root.querySelector(q) : null;

    // --- Session expiry (10 min) ---
    const now = Date.now();
    const verifiedAt = sessionStorage.getItem(VERIFIED_AT_KEY);
    if (!verifiedAt || now - parseInt(verifiedAt, 10) > 10 * 60 * 1000) {
        sessionStorage.removeItem(SESSION_KEY);
        sessionStorage.removeItem(VERIFIED_AT_KEY);
    }

    // --- Settings version check ---
    if (sessionStorage.getItem(VERSION_KEY) !== String(kgsweb_upload_ajax.settingsVersion)) {
        sessionStorage.clear();
        sessionStorage.setItem(VERSION_KEY, String(kgsweb_upload_ajax.settingsVersion));
    }

    // --- Password toggle ---
    document.querySelectorAll('.toggle_password').forEach(toggle => {
        const input = toggle.closest('.password-container')?.querySelector('input[type="password"], input[type="text"]');
        if (!input) return;
        toggle.addEventListener('click', () => {
            input.type = input.type === 'password' ? 'text' : 'password';
            toggle.classList.toggle('fa-eye');
            toggle.classList.toggle('fa-eye-slash');
        });
    });

    // --- Admin JSON toggle ---
    const toggleJsonBtn = document.getElementById('toggle-service-json');
    const jsonTextarea = document.getElementById('service-account-json');
    if (toggleJsonBtn && jsonTextarea) {
        toggleJsonBtn.addEventListener('click', () => {
            const visible = jsonTextarea.style.display === 'block';
            jsonTextarea.style.display = visible ? 'none' : 'block';
            toggleJsonBtn.textContent = visible ? 'Show JSON' : 'Hide JSON';
        });
    }

    // --- Upload destination toggle ---
    const destSelect = document.querySelector('select[name="upload_destination"]');
    const wpRow = document.querySelector('.wp-upload-row');
    if (destSelect && wpRow) {
        const toggleWpRow = () => { wpRow.style.display = destSelect.value === 'wordpress' ? 'table-row' : 'none'; };
        destSelect.addEventListener('change', toggleWpRow);
        toggleWpRow();
    }

    // --- Google login redirect patch ---
    setTimeout(() => {
        const loginBtn = document.querySelector('.nsl-button-google');
        if (loginBtn && loginBtn.href && !loginBtn.href.includes('redirect=')) {
            const currentUrl = encodeURIComponent(window.location.href);
            loginBtn.href += (loginBtn.href.includes('?') ? '&' : '?') + 'redirect=' + currentUrl;
        }
    }, 500);

    // --- Main per-form logic ---
    document.querySelectorAll('.kgsweb-secure-upload-form').forEach(async form => {
        if (!form) return;

        const allowPassword = form.dataset.allowPassword === 'true';
        const allowGroup = form.dataset.allowGroup === 'true';

        // --- Log initial state ---
        console.log('[JS Initial State] allowPassword:', allowPassword,
                    'allowGroup:', allowGroup,
                    'authorized:', authorized,
                    'session:', sessionStorage.getItem(SESSION_KEY),
                    'cookie_kgsweb_group_auth_verified:', document.cookie.split('; ').find(c => c.startsWith('kgsweb_group_auth_verified='))?.split('=')[1] ?? 'none');

        // --- Identify use case ---
        let useCase = 'Unknown';
        if (!allowPassword && !allowGroup) useCase='Case 1: No auth enabled';
        else if (allowPassword && !allowGroup && !authorized) useCase='Case 2: Password only, not validated';
        else if (allowPassword && !allowGroup && authorized) useCase='Case 3: Password only, validated';
        else if (!allowPassword && allowGroup && !authorized) useCase='Case 4: Google auth required';
        else if (!allowPassword && allowGroup && authorized) useCase='Case 5: Google auth approved';
        else if (allowPassword && allowGroup && !authorized) useCase='Case 6: Password and Google auth enabled, not authorized';
        else if (allowPassword && allowGroup && authorized) useCase='Case 7: Password and Google auth enabled, authorized';
        else if (sessionStorage.getItem(SESSION_KEY) === 'true') useCase='Case 8: Session cached';
        console.log('[JS Final State] Secure Upload Use ', useCase);

        // --- Elements ---
        const passwordForm = el('.kgsweb-password-form', form);
        const passwordInput = el('input[name="kgsweb_upload_pass"]', form);
        const passwordError = el('.kgsweb-password-error', form);
        const uploadUI = el('.kgsweb-upload-ui', form);
        const folderSelect = el('.kgsweb-upload-folder', uploadUI);
        const fileInput = el('.kgsweb-upload-file', uploadUI);
        const uploadBtn = el('.kgsweb-upload-btn', uploadUI);
        const oauthBtn = el('.kgsweb-nextend-login', form);
        const notAuthorizedMsg = el('.kgsweb-upload-not-authorized', form);

        if (!uploadUI || !folderSelect || !uploadBtn) return;

        // --- UI helpers ---
        const setUploadEnabled = enabled => { uploadBtn.disabled = !enabled; };
        const showUploadUI = () => {
            if (uploadUI) uploadUI.style.display = 'block';
            if (passwordForm) passwordForm.style.display = 'none';
            if (oauthBtn) oauthBtn.style.display = 'none';
            if (notAuthorizedMsg) notAuthorizedMsg.style.display = 'none';
            setUploadEnabled(true);
        };
        const hideUploadUI = () => { if (uploadUI) uploadUI.style.display = 'none'; setUploadEnabled(false); };
        const showNotAuthorized = msg => { if (notAuthorizedMsg) { notAuthorizedMsg.style.display = 'block'; notAuthorizedMsg.textContent = msg; } };

        // --- Feedback divs ---
        const errorDiv = document.createElement('div');
        errorDiv.style.color = 'red';
        errorDiv.style.marginTop = '0.5rem';
        errorDiv.style.display = 'none';
        uploadUI.appendChild(errorDiv);

        const successDiv = document.createElement('div');
        successDiv.style.color = 'green';
        successDiv.style.marginTop = '0.5rem';
        successDiv.style.display = 'none';
        uploadUI.appendChild(successDiv);

        // --- File validation ---
        const allowedExt = new Set(['txt','rtf','pdf','doc','docx','ppt','pptx','ppsx','xls','xlsx','csv','png','jpg','jpeg','gif','webp','mp3','wav','mp4','m4v','mov','avi']);
        const maxSizeDefault = 100 * 1024 * 1024;
        const maxSizeVideo = 500 * 1024 * 1024;
        const validateFile = file => {
            if (!file) return 'No file selected';
            const ext = (file.name.split('.').pop() || '').toLowerCase();
            if (!allowedExt.has(ext)) return 'File type not allowed';
            const limit = ['mp4','m4v','mov','avi'].includes(ext) ? maxSizeVideo : maxSizeDefault;
            if (file.size > limit) return `File too large — max ${limit === maxSizeVideo ? 500 : 100}MB`;
            return null;
        };

        // --- Populate folders ---
        const populateFolders = async rootFolder => {
            folderSelect.innerHTML = '<option>Loading…</option>';
            if (!rootFolder) { folderSelect.innerHTML = '<option value="">No root folder specified</option>'; return; }
            try {
                const url = `${kgsweb_upload_ajax.ajax_url}?action=kgsweb_get_cached_folders&root=${encodeURIComponent(rootFolder)}&nonce=${encodeURIComponent(kgsweb_upload_ajax.nonce)}`;
                const res = await fetch(url, { credentials: 'same-origin' });
                const json = await res.json();
                folderSelect.innerHTML = '';
                if (json.success && Array.isArray(json.data) && json.data.length) {
                    json.data.forEach(f => {
                        const opt = document.createElement('option');
                        opt.value = f.id || '';
                        opt.textContent = f.label || f.name || f.id || 'Unnamed folder';
                        folderSelect.appendChild(opt);
                    });
                } else folderSelect.innerHTML = '<option value="">No folders available</option>';
            } catch (err) {
                console.error('Failed to fetch folders', err);
                folderSelect.innerHTML = '<option value="">Error loading folders</option>';
            }
        };

        // --- Session-based immediate authorization ---
        if (sessionStorage.getItem(SESSION_KEY) === 'true') {
            authorized = true;
            showUploadUI();
            await populateFolders(rootFolder);
        }

        // --- Immediate Google group check ---
        if (allowGroup && !authorized) {
            try {
                const res = await fetch(kgsweb_upload_ajax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'kgsweb_check_group', _wpnonce: kgsweb_upload_ajax.nonce }),
                    credentials: 'same-origin'
                });
                const json = await res.json();
                if (json.group_ok) {
                    authorized = true;
                    sessionStorage.setItem(SESSION_KEY, 'true');
                    sessionStorage.setItem(VERIFIED_AT_KEY, Date.now().toString());
                    showUploadUI();
                    await populateFolders(rootFolder);
                }
            } catch (err) { console.error('Group check failed:', err); }
        }

        // --- UI initialization ---
        if (authorized || sessionStorage.getItem(SESSION_KEY) === 'true') showUploadUI();
        else {
            hideUploadUI();
            if (allowPassword && passwordForm) passwordForm.style.display = 'block';

            if (allowGroup && oauthBtn) oauthBtn.style.display = 'inline-block';

            if (!allowPassword && allowGroup && !authorized) showNotAuthorized('You are not authorized to upload. Try signing in with a different account.');

            // --- Added integration: show login button if not verified ---
            if (sessionStorage.getItem(SESSION_KEY) !== 'true') {
                // Equivalent to showLoginButton() / hideUploadForm()
                hideUploadUI();
                if (allowGroup && oauthBtn) oauthBtn.style.display = 'inline-block';
            }
        }

        // --- Password form submission ---
        if (passwordForm && passwordInput) {
            passwordForm.addEventListener('submit', async ev => {
                ev.preventDefault();
                if (passwordError) passwordError.style.display = 'none';
                const pw = (passwordInput.value || '').trim();
                if (!pw) return;
                try {
                    const res = await fetch(kgsweb_upload_ajax.ajax_url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ action: 'kgsweb_check_password', password: pw, nonce: kgsweb_upload_ajax.nonce }),
                        credentials: 'same-origin'
                    });
                    const json = await res.json();
                    if (json.success) {
                        showUploadUI();
                        verifiedPassword = pw;
                        sessionStorage.setItem(SESSION_KEY, 'true');
                        sessionStorage.setItem(VERIFIED_AT_KEY, Date.now().toString());
                    } else {
                        const msg = json.data?.message || 'Password invalid';
                        if (passwordError) {
                            passwordError.textContent = msg;
                            passwordError.style.color = msg.includes('Locked') ? 'darkred' : 'red';
                            passwordError.style.fontWeight = msg.includes('Locked') ? '700' : '400';
                            passwordError.style.display = 'block';
                        }
                        verifiedPassword = '';
                    }
                } catch (err) {
                    console.error(err);
                    if (passwordError) { passwordError.textContent = 'Error verifying password.'; passwordError.style.display = 'block'; }
                    verifiedPassword = '';
                }
            });
        }

        // --- OAuth polling ---
        const oauthPollInterval = setInterval(async () => {
            const loginBtn = form.querySelector('.nsl-social-login-button.google');
            if (!loginBtn) return;
            try {
                const res = await fetch(kgsweb_upload_ajax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'kgsweb_check_group', _wpnonce: kgsweb_upload_ajax.nonce }),
                    credentials: 'same-origin'
                });
                const json = await res.json();
                if (json.group_ok) {
                    authorized = true;
                    sessionStorage.setItem(SESSION_KEY, 'true');
                    sessionStorage.setItem(VERIFIED_AT_KEY, Date.now().toString());
                    showUploadUI();
                    await populateFolders(rootFolder);
                    clearInterval(oauthPollInterval);
                }
            } catch (err) { console.error(err); }
        }, 500);
        setTimeout(() => clearInterval(oauthPollInterval), 10000);

        // --- Upload action ---
        uploadBtn.addEventListener('click', async ev => {
            ev.preventDefault();
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';

            const file = fileInput?.files?.[0];
            const fileErr = validateFile(file);
            if (fileErr) { errorDiv.textContent = fileErr; errorDiv.style.display = 'block'; return; }

            const folderId = folderSelect?.value || '';
            if (!folderId) { errorDiv.textContent = 'Please select a folder.'; errorDiv.style.display = 'block'; return; }

            const bypassPassword = (!allowPassword && allowGroup && authorized) || authorized || sessionStorage.getItem(SESSION_KEY) === 'true';
            const password = (passwordInput?.value || verifiedPassword || '').trim();
            if (!bypassPassword && !password) { errorDiv.textContent = 'Missing password.'; errorDiv.style.display = 'block'; return; }

            const formData = new FormData();
            formData.append('action', 'kgsweb_handle_upload');
            formData.append('upload_folder_id', folderId);
            formData.append('kgsweb_upload_file', file);
            formData.append('nonce', kgsweb_upload_ajax.nonce);
            if (!bypassPassword) formData.append('kgsweb_upload_pass', password);

            uploadBtn.disabled = true;
            uploadBtn.textContent = 'Uploading…';

            try {
                const res = await fetch(kgsweb_upload_ajax.ajax_url, { method: 'POST', body: formData, credentials: 'same-origin' });
                const json = await res.json();
                uploadBtn.disabled = false;
                uploadBtn.textContent = 'Upload';
                if (json.success) {
                    const link = json.url ? ` <a href="${json.url}" target="_blank" rel="noopener">View file</a>` : '';
                    successDiv.innerHTML = '✅ Upload successful!' + link;
                    successDiv.style.display = 'block';
                    if (fileInput) fileInput.value = '';
                } else {
                    errorDiv.textContent = json.message || (json.data && json.data.message) || 'Upload failed.';
                    errorDiv.style.display = 'block';
                }
            } catch (err) {
                console.error(err);
                errorDiv.textContent = 'An error occurred during upload.';
                errorDiv.style.display = 'block';
                uploadBtn.disabled = false;
                uploadBtn.textContent = 'Upload';
            }
        });

    }); // end per-form

}); // end DOMContentLoaded

// --- Nextend login event handler ---
document.addEventListener('nsl_after_login', async (event, userData) => {
    console.log('Nextend login detected:', userData);
    try {
        const res = await fetch(kgsweb_upload_ajax.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'kgsweb_check_group', _wpnonce: kgsweb_upload_ajax.nonce }),
            credentials: 'same-origin'
        });
        const json = await res.json();
        if (json.group_ok) {
            console.log('User approved, showing upload form.');
            document.querySelectorAll('.kgsweb-secure-upload-form').forEach(async form => {
                const uploadUI = form.querySelector('.kgsweb-upload-ui');
                const nextendLogin = form.querySelector('.kgsweb-nextend-login');
                const notAuthorizedMsg = form.querySelector('.kgsweb-upload-not-authorized');
                if (uploadUI) uploadUI.style.display = 'block';
                if (nextendLogin) nextendLogin.style.display = 'none';
                if (notAuthorizedMsg) notAuthorizedMsg.style.display = 'none';
                sessionStorage.setItem('kgsweb_upload_verified', 'true');
                sessionStorage.setItem('kgsweb_upload_verified_at', Date.now().toString());

                const folderSelect = form.querySelector('.kgsweb-upload-folder');
                if (folderSelect) {
                    folderSelect.innerHTML = '<option>Loading…</option>';
                    try {
                        const res2 = await fetch(`${kgsweb_upload_ajax.ajax_url}?action=kgsweb_get_cached_folders&root=${encodeURIComponent(kgsweb_upload_ajax.uploadRootFolderId || '')}&nonce=${encodeURIComponent(kgsweb_upload_ajax.nonce)}`, { credentials: 'same-origin' });
                        const json2 = await res2.json();
                        folderSelect.innerHTML = '';
                        if (json2.success && Array.isArray(json2.data) && json2.data.length) {
                            json2.data.forEach(f => {
                                const opt = document.createElement('option');
                                opt.value = f.id || '';
                                opt.textContent = f.label || f.name || f.id || 'Unnamed folder';
                                folderSelect.appendChild(opt);
                            });
                        } else folderSelect.innerHTML = '<option value="">No folders available</option>';
                    } catch (err) {
                        console.error('Failed to fetch folders', err);
                        folderSelect.innerHTML = '<option value="">Error loading folders</option>';
                    }
                }
            });
        }
    } catch (err) {
        console.error('Error checking group after login:', err);
    }
});
