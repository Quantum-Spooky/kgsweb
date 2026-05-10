// js/kgsweb-cache.js
(function(){
  window.KGSWEB = window.KGSWEB || {};
  KGSWEB.sget = (k) => { try { return JSON.parse(sessionStorage.getItem(k)); } catch(e){ return null; } };
  KGSWEB.sset = (k,v) => { try { sessionStorage.setItem(k, JSON.stringify(v)); } catch(e){} };


// ------------------------
// User-Accessible Cache Refresh 
// ------------------------	

	document.addEventListener('DOMContentLoaded', () => {
		const footerCopyright = document.getElementById('copyright-symbol');
		if (!footerCopyright) return;

		// Create a fixed-position toast
		const toast = document.createElement('div');
		toast.style.position = 'fixed';
		toast.style.bottom = '0.5rem';
		toast.style.right = '0.5rem';
		toast.style.padding = '0.3rem 0.6rem';
		toast.style.background = 'rgba(0,0,0,0.25)';
		toast.style.color = '#fff';
		toast.style.borderRadius = '0.25rem';
		toast.style.fontSize = '0.8rem';
		toast.style.pointerEvents = 'none'; // clicks pass through
		toast.style.opacity = 0;
		toast.style.transition = 'opacity 0.3s ease';
		document.body.appendChild(toast);

		function showToast(msg, duration = 2000) {
			toast.textContent = msg;
			toast.style.opacity = 1;
			setTimeout(() => toast.style.opacity = 0, duration);
		}

		footerCopyright.addEventListener('click', (e) => {
			e.preventDefault();

			const secret = KGSwebCache?.secret || '';
			const url = KGSwebCache?.restUrl;
			if (!secret || !url) return;

			fetch(`${url}?secret=${secret}`, {
				method: 'POST',
				credentials: 'same-origin',
			})
			.then(r => r.json())
			.then(data => {
				if (data.success) {
					console.log('KGSWEB: Cache refreshed successfully.');
					showToast('✓');  // subtle visual confirmation
				} else {
					console.warn('KGSWEB: Cache refresh failed:', data.message);
					showToast('✗');  // indicate failure
				}
			})
			.catch(err => {
				console.error('KGSWEB: Cache refresh error', err);
				showToast('✗'); // indicate error
			});
		});
	});



})();


