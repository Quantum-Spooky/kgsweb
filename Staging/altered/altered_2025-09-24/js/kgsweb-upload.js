/**
 * js/kgsweb-upload.js
 *
 * Merged version — preserves Current JS functionality and adds Suggested fixes:
 *  - Keeps namespaced selectors (.kgsweb-*)
 *  - Uses FontAwesome eye toggle
 *  - Keeps file validation, folder population, error/success UI, upload field names
 *  - Adds nonce usage (kgsweb_ajax.nonce) for AJAX CSRF protection
 *  - Adds verifiedPassword caching to avoid "Missing password." when the password input
 *    was hidden after verification
 *  - Ensures upload UI is hidden after a hard refresh unless sessionStorage still marks verified
 *
 * IMPORTANT: Ensure PHP localizes `kgsweb_ajax` with properties:
 *  - kgsweb_ajax.ajax_url  (admin-ajax.php)
 *  - kgsweb_ajax.nonce     (a nonce value for actions)
 *
 * If you do not use `kgsweb_ajax` in PHP, replace `kgsweb_ajax` usages with your ajax object.
 */

document.addEventListener('DOMContentLoaded', () => {

  // small helper to query a single element within an optional root
  const el = (q, root = document) => root.querySelector(q);

  // key used in sessionStorage to remember that the current tab trusted the password
  const SESSION_KEY = 'kgsweb_upload_verified';

  // --- Upload form session expiry (10 min) ---
  const now = Date.now();
  const verifiedAt = sessionStorage.getItem('kgsweb_upload_verified_at');
  if (!verifiedAt || now - parseInt(verifiedAt, 10) > 10 * 60 * 1000) {
    sessionStorage.removeItem(SESSION_KEY);
    sessionStorage.removeItem('kgsweb_upload_verified_at');
  }

  // -----------------------------
  // Password toggle (eye icon)
  // -----------------------------
  // Keep FontAwesome classes (fa-eye / fa-eye-slash) behavior exactly as Current JS did.
  document.querySelectorAll('.toggle_password').forEach(toggle => {
    // find password or text input inside nearest .password-container
    const input = toggle.closest('.password-container')?.querySelector('input[type="password"], input[type="text"]');
    if (!input) return;

    // toggle input type and swap FontAwesome classes
    toggle.addEventListener('click', () => {
      if (input.type === 'password') {
        input.type = 'text';
        // replace fa-eye -> fa-eye-slash
        toggle.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
        input.type = 'password';
        // replace fa-eye-slash -> fa-eye
        toggle.classList.replace('fa-eye-slash', 'fa-eye');
      }
    });
  });

  // -----------------------------
  // Service Account JSON toggle (admin)
  // -----------------------------
  // Preserves Current JS behavior (toggles textarea AND the button text)
  (function () {
    const toggleJsonBtn = document.getElementById('toggle-service-json');
    const jsonTextarea = document.getElementById('service-account-json'); // keep same id as Current
    if (!toggleJsonBtn || !jsonTextarea) return;

    toggleJsonBtn.addEventListener('click', () => {
      // toggle visibility
      const visible = jsonTextarea.style.display === 'block';
      jsonTextarea.style.display = visible ? 'none' : 'block';
      // update button label as Current JS did
      toggleJsonBtn.textContent = visible ? 'Show JSON' : 'Hide JSON';
    });
  })();

  // -----------------------------
  // Admin: Upload destination toggle (show WP row)
  // -----------------------------
  // Preserves Current JS behavior and uses table-row so layout remains consistent.
  (function () {
    const destSelect = document.querySelector('select[name="upload_destination"]');
    const wpRow = document.querySelector('.wp-upload-row');
    if (!destSelect || !wpRow) return;

    const toggleWpRow = () => {
      wpRow.style.display = destSelect.value === 'wordpress' ? 'table-row' : 'none';
    };
    destSelect.addEventListener('change', toggleWpRow);
    toggleWpRow();
  })();

  // -----------------------------
  // Main: handle every .kgsweb-secure-upload-form on the page
  // This preserves multi-form support from Current JS.
  // -----------------------------
  document.querySelectorAll('.kgsweb-secure-upload-form').forEach(form => {

    // Grab the expected elements using the original selectors (Current JS)
    const passwordForm   = el('.kgsweb-password-form', form);        // the password entry form
    const passwordInput  = el('input[name="kgsweb_upload_pass"]', form); // password input
    const passwordError  = el('.kgsweb-password-error', form);       // inline password error element
    const uploadUI       = el('.kgsweb-upload-ui', form);            // the upload UI container
    const folderSelect   = el('.kgsweb-upload-folder', uploadUI);    // select box for target folder
    const fileInput      = el('.kgsweb-upload-file', uploadUI);      // file input
    const uploadBtn      = el('.kgsweb-upload-btn', uploadUI);       // upload button

    // If folderSelect doesn't exist, nothing to do (mirrors Current)
    if (!folderSelect) return;

    // -----------------------------
    // Error / Success containers
    // -----------------------------
    // Current JS appended visible but hidden divs inside uploadUI; we replicate that.
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

    // -----------------------------
    // File validation (preserve Current allowedExt & limits)
    // - returns a string error message or null if OK
    // -----------------------------
    const allowedExt = new Set([
      'txt','rtf','pdf','doc','docx','ppt','pptx','ppsx','xls','xlsx','csv',
      'png','jpg','jpeg','gif','webp','mp3','wav','mp4','m4v','mov','avi'
    ]);
    const maxSizeDefault = 100 * 1024 * 1024; // 100MB
    const maxSizeVideo   = 500 * 1024 * 1024; // 500MB

    const validateFile = (file) => {
      // preserve the Current JS behavior: return error string if invalid
      if (!file) return 'No file selected';
      const ext = (file.name.split('.').pop() || '').toLowerCase();
      if (!allowedExt.has(ext)) return 'File type not allowed';
      const isVideo = ['mp4','m4v','mov','avi'].includes(ext);
      const limit = isVideo ? maxSizeVideo : maxSizeDefault;
      if (file.size > limit) return isVideo ? 'File too large — max 500MB for video' : 'File too large — max 100MB';
      return null;
    };

    // -----------------------------
    // Folder population helper
    // Preserves Current logic:
    //  - Uses per-form data-upload-folder root
    //  - Shows "Loading…" immediately
    //  - Uses kgsweb_get_cached_folders action and includes nonce for safety
    // -----------------------------
    const populateFolders = async (rootFolder) => {
      // immediate UX feedback while loading
      folderSelect.innerHTML = '<option>Loading…</option>';

      if (!rootFolder) {
        // preserve Current fallback behavior
        folderSelect.innerHTML = '<option value="">No root folder specified</option>';
        return;
      }

      try {
        // include nonce param for security (Suggested improvement)
        const url = `${kgsweb_ajax.ajax_url}?action=kgsweb_get_cached_folders&root=${encodeURIComponent(rootFolder)}&nonce=${encodeURIComponent(kgsweb_ajax.nonce)}`;
        const res = await fetch(url, { credentials: 'same-origin' });
        const json = await res.json();

        folderSelect.innerHTML = '';

        if (json.success && Array.isArray(json.data) && json.data.length) {
          // preserve Current label fallback: f.label || f.name || f.id || 'Unnamed folder'
          json.data.forEach(f => {
            const opt = document.createElement('option');
            opt.value = f.id || '';
            opt.textContent = f.label || f.name || f.id || 'Unnamed folder';
            folderSelect.appendChild(opt);
          });
        } else {
          folderSelect.innerHTML = '<option value="">No folders available</option>';
        }
      } catch (err) {
        // log error and show fallback option
        console.error('Failed to fetch folders', err);
        folderSelect.innerHTML = '<option value="">Error loading folders</option>';
      }
    };

    // Initialize folders (per form) using dataset.uploadFolder (Current)
    const rootFolder = form.dataset.uploadFolder?.trim() || '';
    populateFolders(rootFolder);

    // -----------------------------
    // Session / password verification + upload gating
    // - setUploadEnabled enables/disables the upload button
    // - Preserves Current dataset verified and sessionStorage key name
    // - Adds verifiedPassword caching (Suggested)
    // - Hides uploadUI on hard refresh unless sessionStorage still says verified
    // -----------------------------
    const setUploadEnabled = (enabled) => {
      if (uploadBtn) uploadBtn.disabled = !enabled;
    };

    // by default: if we have a verified session key, show upload UI
    if (sessionStorage.getItem(SESSION_KEY) === 'true') {
      if (uploadUI) uploadUI.style.display = 'block';
      if (passwordForm) passwordForm.style.display = 'none';
      if (passwordInput) passwordInput.dataset.verified = 'true';
      setUploadEnabled(true);
    } else {
      // HARD-REFRESH FIX: explicitly hide upload UI and ensure user must re-verify
      if (uploadUI) uploadUI.style.display = 'none';
      if (passwordForm) passwordForm.style.display = 'block';
      setUploadEnabled(false);
    }

    // keep dynamic enable/disable behavior as Current: typing into password input
    if (passwordInput && uploadBtn) {
      passwordInput.addEventListener('input', () => {
        // enable the upload button if the input itself has been marked verified or session says verified
        setUploadEnabled(passwordInput.dataset.verified === 'true' || sessionStorage.getItem(SESSION_KEY) === 'true');
      });
    }

    // storage for the verified password after a successful check
    // (Suggested improvement — avoids "Missing password" when the input becomes hidden)
    let verifiedPassword = '';

    // -----------------------------
    // Password submit (AJAX)
    // - Uses form.submit so Enter key works
    // - Sends nonce
    // - On success: hide password form, show upload UI, cache password in verifiedPassword
    // - On failure: show styled error (darkred + bold if 'Locked' present)
    // -----------------------------
    if (passwordForm && passwordInput) {
      passwordForm.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        // hide previous error
        passwordError.style.display = 'none';

        const pw = (passwordInput.value || '').trim();
        if (!pw) return; // nothing to do if empty

        try {
          // send check request — include nonce and use same-origin credentials
          const res = await fetch(kgsweb_ajax.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
              action: 'kgsweb_check_password',
              password: pw,
              nonce: kgsweb_ajax.nonce // Suggested improvement: include nonce
            }),
            credentials: 'same-origin'
          });
          const json = await res.json();

          if (json.success) {
            // success: show upload UI, hide password form, mark dataset verified and sessionStorage
            uploadUI.style.display = 'block';
            passwordForm.style.display = 'none';
            passwordInput.dataset.verified = 'true';
            sessionStorage.setItem(SESSION_KEY, 'true');
            sessionStorage.setItem('kgsweb_upload_verified_at', Date.now().toString());

            // store the verified password in memory so upload button can use it even if input gets hidden
            verifiedPassword = pw;

            // enable upload button
            setUploadEnabled(true);
          } else {
            // show password error and differentiate lockout messages
            const msg = json.data?.message || 'Password invalid';
            passwordError.style.color = msg.includes('Locked') ? 'darkred' : 'red';
            passwordError.style.fontWeight = msg.includes('Locked') ? '700' : '400';
            passwordError.textContent = msg;
            passwordError.style.display = 'block';
            // clear any cached password
            verifiedPassword = '';
          }
        } catch (err) {
          // network/error fallback
          console.error('password check error', err);
          passwordError.textContent = 'An error occurred while checking password.';
          passwordError.style.display = 'block';
          verifiedPassword = '';
        }
      });
    }

    // -----------------------------
    // Upload button click (AJAX)
    // - Uses the preserved field names expected by PHP:
    //   'kgsweb_upload_file', 'upload_folder_id', 'kgsweb_upload_pass'
    // - Appends nonce to formData for server verification
    // - Disables button and changes text while uploading
    // - Restores UI and shows link on success (json.url)
    // -----------------------------
    if (uploadBtn) {
      uploadBtn.addEventListener('click', async (ev) => {
        ev.preventDefault();

        // hide previous messages
        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';

        // validate file
        const file = fileInput?.files?.[0];
        const fileErr = validateFile(file);
        if (fileErr) {
          errorDiv.textContent = fileErr;
          errorDiv.style.display = 'block';
          return;
        }

        // validate folder selection
        const folderId = folderSelect?.value || '';
        if (!folderId) {
          errorDiv.textContent = 'Please select a folder.';
          errorDiv.style.display = 'block';
          return;
        }

        // use either the visible input or the cached verifiedPassword
        const password = (passwordInput?.value || verifiedPassword || '').trim();
        if (!password) {
          errorDiv.textContent = 'Missing password.';
          errorDiv.style.display = 'block';
          return;
        }

        // build form data exactly as Current PHP expects
        const formData = new FormData();
        formData.append('action', 'kgsweb_handle_upload');
        formData.append('upload_folder_id', folderId);
        formData.append('kgsweb_upload_file', file);
        formData.append('kgsweb_upload_pass',verifiedPassword || (passwordInput?.value?.trim() || ''));
        // include nonce for server-side verification
        formData.append('nonce', kgsweb_ajax.nonce);

        // UI: disable button and show uploading text
        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Uploading…';

        try {
          const res = await fetch(kgsweb_ajax.ajax_url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
          });
          const json = await res.json();

          // Re-enable and restore button text immediately after response
          uploadBtn.disabled = false;
          uploadBtn.textContent = 'Upload';

          if (json.success) {
            // show success and attach a link if json.url exists (preserve Current behavior)
            const link = json.url ? ` <a href="${json.url}" target="_blank" rel="noopener">View file</a>` : '';
            successDiv.innerHTML = '✅ Upload successful!' + link;
            successDiv.style.display = 'block';

            // reset file input for UX
            if (fileInput) fileInput.value = '';
          } else {
            // robustly pick error message (mirrors Current fallbacks)
            errorDiv.textContent = json.message || (json.data && json.data.message) || 'Upload failed.';
            errorDiv.style.display = 'block';
          }
        } catch (err) {
          // network error fallback
          console.error('upload error', err);
          errorDiv.textContent = 'An error occurred during upload.';
          errorDiv.style.display = 'block';
          uploadBtn.disabled = false;
          uploadBtn.textContent = 'Upload';
        }
      });
    }

  }); // end forEach form

}); // end DOMContentLoaded
