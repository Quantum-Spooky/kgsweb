// js/kgsweb-upload.js
document.addEventListener('DOMContentLoaded', () => {

  // small helper
  const el = (q, root = document) => root.querySelector(q);

  // -----------------------------
  // Password toggle (eye)
  // -----------------------------
  document.querySelectorAll('.toggle_password').forEach(toggle => {
    const input = toggle.closest('.password-container')?.querySelector('input[type="password"], input[type="text"]');
    if (!input) return;
    toggle.addEventListener('click', () => {
      if (input.type === 'password') {
        input.type = 'text';
        toggle.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
        input.type = 'password';
        toggle.classList.replace('fa-eye-slash', 'fa-eye');
      }
    });
  });

  // -----------------------------
  // Service Account JSON toggle (admin)
  // -----------------------------
  (function () {
    const toggleJsonBtn = document.getElementById('toggle-service-json');
    const jsonTextarea = document.getElementById('service-account-json');
    if (!toggleJsonBtn || !jsonTextarea) return;
    toggleJsonBtn.addEventListener('click', () => {
      const visible = jsonTextarea.style.display === 'block';
      jsonTextarea.style.display = visible ? 'none' : 'block';
      toggleJsonBtn.textContent = visible ? 'Show JSON' : 'Hide JSON';
    });
  })();

  // -----------------------------
  // Admin: Upload destination toggle (show WP row)
  // -----------------------------
  (function () {
    const destSelect = document.querySelector('select[name="upload_destination"]');
    const wpRow = document.querySelector('.wp-upload-row');
    if (!destSelect || !wpRow) return;
    const toggleWpRow = () => { wpRow.style.display = destSelect.value === 'wordpress' ? 'table-row' : 'none'; };
    destSelect.addEventListener('change', toggleWpRow);
    toggleWpRow();
  })();

  // -----------------------------
  // Main form initialization
  // -----------------------------
  document.querySelectorAll('.kgsweb-secure-upload-form').forEach(form => {
    const passwordForm = el('.kgsweb-password-form', form);
    const passwordInput = el('input[name="kgsweb_upload_pass"]', passwordForm);
    const passwordError = el('.kgsweb-password-error', passwordForm);
    const uploadUI = el('.kgsweb-upload-ui', form);
    const folderSelect = el('.kgsweb-upload-folder', uploadUI);
    const fileInput = el('.kgsweb-upload-file', uploadUI);
    const uploadBtn = el('.kgsweb-upload-btn', uploadUI);

    // create local error/success elements once
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

    // Helper: populate folder dropdown from cached endpoint
    const populateFolders = (rootFolder) => {
      if (!folderSelect || !rootFolder) return;
      folderSelect.innerHTML = '<option>Loading…</option>';
      const url = `${kgsweb.ajax_url}?action=kgsweb_get_cached_folders&root=${encodeURIComponent(rootFolder)}`;
      fetch(url, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(j => {
          folderSelect.innerHTML = '';
          if (j.success && Array.isArray(j.data) && j.data.length) {
            j.data.forEach(f => {
              const opt = document.createElement('option');
              opt.value = f.id;
              opt.textContent = f.label || f.name || f.id;
              folderSelect.appendChild(opt);
            });
          } else {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'No folders available';
            folderSelect.appendChild(opt);
          }
        })
        .catch(err => {
          console.error('Failed to fetch folders', err);
          folderSelect.innerHTML = '<option value="">Error loading folders</option>';
        });
    };

    // File validation parameters
    const allowedExt = new Set([
      'txt','rtf','pdf','doc','docx','ppt','pptx','ppsx','xls','xlsx','csv',
      'png','jpg','jpeg','gif','webp','mp3','wav','mp4','m4v','mov','avi'
    ]);
    const maxSizeDefault = 100 * 1024 * 1024; // 100MB
    const maxSizeVideo = 500 * 1024 * 1024;   // 500MB

    function validateFile(file) {
      if (!file) return 'No file';
      const ext = (file.name.split('.').pop() || '').toLowerCase();
      if (!allowedExt.has(ext)) return 'File type not allowed.';
      const isVideo = ['mp4','m4v','mov','avi'].includes(ext);
      const limit = isVideo ? maxSizeVideo : maxSizeDefault;
      if (file.size > limit) return isVideo ? 'File too large — max 500MB for video.' : 'File too large — max 100MB.';
      return null;
    }

    // Persisted unlocked state (session)
    const SESSION_KEY = 'kgsweb_upload_verified';

    // If previously verified in this session, show upload UI immediately
    if (sessionStorage.getItem(SESSION_KEY) === 'true') {
      if (passwordForm) passwordForm.style.display = 'none';
      if (uploadUI) uploadUI.style.display = 'block';
      if (passwordInput) passwordInput.dataset.verified = 'true';
      if (uploadBtn) uploadBtn.disabled = false;
    } else {
      if (uploadBtn) uploadBtn.disabled = true;
    }

    // attach password submit (AJAX)
    if (passwordForm && passwordInput) {
      passwordForm.addEventListener('submit', (ev) => {
        ev.preventDefault();
        passwordError.style.display = 'none';
        const pw = (passwordInput.value || '').trim();
        if (!pw) return;

        // POST to check password
        fetch(kgsweb.ajax_url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            action: 'kgsweb_check_password',
            password: pw
          }),
          credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(json => {
          if (json.success) {
            // show upload UI, mark verified
            if (uploadUI) uploadUI.style.display = 'block';
            if (passwordForm) passwordForm.style.display = 'none';
            passwordInput.dataset.verified = 'true';
            sessionStorage.setItem(SESSION_KEY, 'true');
            if (uploadBtn) uploadBtn.disabled = false;
          } else {
            const msg = json.data?.message || 'Password invalid';
            passwordError.style.color = msg.includes('Locked') ? 'darkred' : 'red';
            passwordError.style.fontWeight = msg.includes('Locked') ? '700' : '400';
            passwordError.textContent = msg;
            passwordError.style.display = 'block';
          }
        })
        .catch(err => {
          console.error('password check error', err);
          passwordError.textContent = 'An error occurred while checking password.';
          passwordError.style.display = 'block';
        });
      });
    }

    // populate folders from data-upload-folder attribute on the form
    const rootFolder = form.dataset.uploadFolder || '';
    if (rootFolder) populateFolders(rootFolder);

    // wire upload button (client validation + AJAX upload)
    if (uploadBtn) {
      uploadBtn.addEventListener('click', (ev) => {
        ev.preventDefault();
        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';

        const file = fileInput?.files?.[0];
        const fileErr = validateFile(file);
        if (fileErr) {
          errorDiv.textContent = fileErr;
          errorDiv.style.display = 'block';
          return;
        }

        const folderId = folderSelect?.value || '';
        if (!folderId) {
          errorDiv.textContent = 'Please select a folder.';
          errorDiv.style.display = 'block';
          return;
        }

        const password = (passwordInput?.value || '').trim();
        if (!password) {
          errorDiv.textContent = 'Missing password.';
          errorDiv.style.display = 'block';
          return;
        }

        const formData = new FormData();
        formData.append('action', 'kgsweb_handle_upload');
        formData.append('kgsweb_upload_file', file);
        formData.append('upload_folder_id', folderId);
        formData.append('kgsweb_upload_pass', password);

        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Uploading…';

        fetch(kgsweb.ajax_url, {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(json => {
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
        })
        .catch(err => {
          console.error('upload error', err);
          errorDiv.textContent = 'An error occurred during upload.';
          errorDiv.style.display = 'block';
          uploadBtn.disabled = false;
          uploadBtn.textContent = 'Upload';
        });
      });
    }

    // enable/disable upload btn when password input changes and verified flag toggles
    if (passwordInput && uploadBtn) {
      passwordInput.addEventListener('input', () => {
        uploadBtn.disabled = !(passwordInput.dataset.verified === 'true' || sessionStorage.getItem(SESSION_KEY) === 'true');
      });
    }
  });

});
