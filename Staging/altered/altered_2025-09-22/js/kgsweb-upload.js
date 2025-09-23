// js/kgsweb-upload.js
(function(){
  function gatePasswordUI(root, settings){
    const gate = root.querySelector('.kgsweb-upload-gate');
    const form = root.querySelector('.kgsweb-upload-form');
    gate.innerHTML = '<label>Password <input type="password" class="kgsweb-upload-pass"/></label><button>Continue</button>';
    const btn = gate.querySelector('button');
    btn.addEventListener('click', function(){
      form.removeAttribute('hidden'); // Server will validate on submit; this is UX-only gate
      gate.remove();
    });
  }
  function populateFolders(select, rootId){
    // TODO: get upload tree (may share REST path or separate), then fill select
    select.innerHTML = '';
  }
  function initUpload(el){
    const form = el.querySelector('.kgsweb-upload-form');
    const select = el.querySelector('.kgsweb-upload-dest');
	const rootId = el.getAttribute('data-upload-folder') || el.getAttribute('data-root-id');
	console.log('Resolved upload folder ID:', rootId);
    populateFolders(select, rootId);

    form.addEventListener('submit', function(e){
      e.preventDefault();
      const fd = new FormData(form);
      const file = form.querySelector('input[type=file]').files[0];
      if (!file) return;
      fd.append('folder_id', select.value);
      KGSWEB.rest('upload', { method:'POST', body: fd }).then(res=>{
        // TODO: handle success/failure UI
        console.log(res);
      });
    });

    // Choose gate behavior based on settings (client may not know auth mode; keep simple)
    gatePasswordUI(el);
  }
  document.addEventListener('DOMContentLoaded', function(){
    (window.KGSWEB.qsa||function(){} )('.kgsweb-upload').forEach(initUpload);
  });
})();