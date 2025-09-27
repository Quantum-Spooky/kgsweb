// js/kgsweb-upload.js

document.addEventListener('DOMContentLoaded', function () {

    // -----------------------------
    // Password toggle
    // -----------------------------
    document.querySelectorAll('.toggle_password').forEach(toggle => {
        const input = toggle.closest('.password-container')
            ?.querySelector('input[type="password"], input[type="text"]');
        if (!input) return;

        toggle.addEventListener('click', () => {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            toggle.classList.replace(show ? 'fa-eye' : 'fa-eye-slash', show ? 'fa-eye-slash' : 'fa-eye');
        });
    });

    // -----------------------------
    // Service Account JSON toggle
    // -----------------------------
    const toggleJsonBtn = document.getElementById('toggle-service-json');
    const jsonTextarea = document.getElementById('service-account-json');
    if (toggleJsonBtn && jsonTextarea) {
        toggleJsonBtn.addEventListener('click', () => {
            const visible = jsonTextarea.style.display === 'block';
            jsonTextarea.style.display = visible ? 'none' : 'block';
            toggleJsonBtn.textContent = visible ? 'Show JSON' : 'Hide JSON';
        });
    }

    // -----------------------------
    // Upload destination toggle
    // -----------------------------
    const destSelect = document.querySelector('select[name="upload_destination"]');
    const wpRow = document.querySelector('.wp-upload-row');
    if (destSelect && wpRow) {
        const toggleWpRow = () => {
            wpRow.style.display = destSelect.value === 'wordpress' ? 'table-row' : 'none';
        };
        destSelect.addEventListener('change', toggleWpRow);
        toggleWpRow(); // init
    }

    // -----------------------------
    // Secure upload form handling
    // -----------------------------
    document.querySelectorAll('.kgsweb-secure-upload-form').forEach(form => {
        const passwordForm = form.querySelector('.kgsweb-password-form');
        const passwordInput = passwordForm?.querySelector('input[name="kgsweb_upload_pass"]');
        const uploadUI = form.querySelector('.kgsweb-upload-ui');
        const uploadBtn = uploadUI?.querySelector('.kgsweb-upload-btn');
        const folderSelect = uploadUI?.querySelector('.kgsweb-upload-folder');
        const fileInput = uploadUI?.querySelector('.kgsweb-upload-file');
        const errorDiv = document.createElement('div');
        const successDiv = document.createElement('div');

        if (!passwordForm || !passwordInput || !uploadUI || !uploadBtn || !folderSelect || !fileInput) return;

        // Message areas
        errorDiv.style.color = 'red';
        errorDiv.style.marginTop = '0.5rem';
        errorDiv.style.display = 'none';
        successDiv.style.color = 'green';
        successDiv.style.marginTop = '0.5rem';
        successDiv.style.display = 'none';
        uploadUI.appendChild(errorDiv);
        uploadUI.appendChild(successDiv);

        // -----------------------------
        // Password AJAX verification
        // -----------------------------
        passwordForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const password = passwordInput.value.trim();
            if (!password) return;

            fetch(kgsweb.ajax_url, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'kgsweb_check_password',
                    password: password
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    uploadUI.style.display = 'block';
                    passwordForm.style.display = 'none';
                    errorDiv.style.display = 'none';

                    passwordInput.dataset.verified = "true";
                    uploadBtn.disabled = false;

                    // persist session
                    sessionStorage.setItem('kgsweb_upload_verified', 'true');
                } else {
                    const msg = data.data?.message || 'Password invalid';
                    errorDiv.style.color = msg.includes('Locked') ? 'darkred' : 'red';
                    errorDiv.style.fontWeight = msg.includes('Locked') ? 'bold' : 'normal';
                    errorDiv.textContent = msg;
                    errorDiv.style.display = 'block';
                }
            })
            .catch(err => {
                console.error(err);
                errorDiv.textContent = 'An error occurred';
                errorDiv.style.display = 'block';
            });
        });

        // -----------------------------
        // Auto-unlock if already verified
        // -----------------------------
        if (sessionStorage.getItem('kgsweb_upload_verified') === 'true') {
            passwordForm.style.display = 'none';
            uploadUI.style.display = 'block';
            passwordInput.dataset.verified = "true";
            uploadBtn.disabled = false;
        } else {
            uploadBtn.disabled = true;
        }

        // -----------------------------
        // Folder dropdown (cached)
        // -----------------------------
        const rootFolder = form.dataset.uploadFolder || '';
        if (rootFolder) {
            fetch(kgsweb.ajax_url + '?action=kgsweb_get_cached_folders&root=' + encodeURIComponent(rootFolder))
                .then(res => res.json())
                .then(data => {
                    if (data.success && Array.isArray(data.data)) {
                        folderSelect.innerHTML = '';
                        data.data.forEach(folder => {
                            const opt = document.createElement('option');
                            opt.value = folder.id;
                            opt.textContent = folder.name;
                            folderSelect.appendChild(opt);
                        });
                    }
                });
        }

        // -----------------------------
        // File validation
        // -----------------------------
        const allowedExt = [
            'txt','rtf','pdf','doc','docx','ppt','pptx','ppsx','xls','xlsx','csv',
            'png','jpg','jpeg','gif','webp','mp3','wav','mp4','m4v','mov','avi'
        ];
        const maxSize = {
            default: 100 * 1024 * 1024, // 100MB
            video: 500 * 1024 * 1024    // 500MB
        };
        function validateFile(file) {
            const ext = file.name.split('.').pop().toLowerCase();
            if (!allowedExt.includes(ext)) return 'File type not allowed.';
            const isVideo = ['mp4','m4v','mov','avi'].includes(ext);
            const limit = isVideo ? maxSize.video : maxSize.default;
            if (file.size > limit) return `File too large. Max ${isVideo ? '500MB' : '100MB'}.`;
            return null;
        }

        // -----------------------------
        // Handle upload
        // -----------------------------
        uploadBtn.addEventListener('click', e => {
            e.preventDefault();
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';

            const file = fileInput.files[0];
            if (!file) {
                errorDiv.textContent = 'Please select a file to upload.';
                errorDiv.style.display = 'block';
                return;
            }
            const fileError = validateFile(file);
            if (fileError) {
                errorDiv.textContent = fileError;
                errorDiv.style.display = 'block';
                return;
            }
            if (!folderSelect.value) {
                errorDiv.textContent = 'Please select a folder.';
                errorDiv.style.display = 'block';
                return;
            }

            const formData = new FormData();
            formData.append('action', 'kgsweb_handle_upload');
            formData.append('kgsweb_upload_file', file);
            formData.append('upload_folder_id', folderSelect.value);
            formData.append('kgsweb_upload_pass', passwordInput.value);

            uploadBtn.disabled = true;
            uploadBtn.textContent = 'Uploading...';

            fetch(kgsweb.ajax_url, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    uploadBtn.disabled = false;
                    uploadBtn.textContent = 'Upload';
                    if (res.success) {
                        successDiv.innerHTML = '✅ Upload successful! ' +
                            (res.url ? `<a href="${res.url}" target="_blank">View file</a>` : '');
                        successDiv.style.display = 'block';
                        fileInput.value = '';
                    } else {
                        errorDiv.textContent = res.message || 'Upload failed.';
                        errorDiv.style.display = 'block';
                    }
                })
                .catch(err => {
                    console.error(err);
                    errorDiv.textContent = 'An error occurred during upload.';
                    errorDiv.style.display = 'block';
                    uploadBtn.disabled = false;
                    uploadBtn.textContent = 'Upload';
                });
        });

        // -----------------------------
        // Disable upload until verified
        // -----------------------------
        passwordInput.addEventListener('input', () => {
            uploadBtn.disabled = !passwordInput.dataset.verified;
        });
    });
});
