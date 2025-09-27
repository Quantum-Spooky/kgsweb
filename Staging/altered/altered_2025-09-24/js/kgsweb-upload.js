document.addEventListener('DOMContentLoaded', function () {

	// -----------------------------
	// Password toggle
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
        toggleWpRow(); // initialize
    }

    // -----------------------------
    // AJAX password submit
    // -----------------------------
    document.querySelectorAll('.kgsweb-secure-upload-form').forEach(form => {
        const passwordForm = form.querySelector('.kgsweb-password-form');
        const passwordInput = passwordForm?.querySelector('input[name="kgsweb_upload_pass"]');
        const uploadUI = form.querySelector('.kgsweb-upload-ui');
        const errorDiv = passwordForm?.querySelector('.kgsweb-password-error');

        if (!passwordForm || !passwordInput || !uploadUI || !errorDiv) return;

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
                } else {
                    errorDiv.textContent = data.data?.message || 'Password invalid';
                    errorDiv.style.display = 'block';
                }
            })
            .catch(err => {
                console.error(err);
                errorDiv.textContent = 'An error occurred';
                errorDiv.style.display = 'block';
            });
        });
    });

});
