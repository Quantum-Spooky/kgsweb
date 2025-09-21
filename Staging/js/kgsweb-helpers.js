// js/kgsweb-helpers.js	
					   
(function(){
  window.KGSWEB = window.KGSWEB || {};
  KGSWEB.Helpers = KGSWEB.Helpers || {}; 

  // -------------------
  // DOM helpers
  // -------------------
  KGSWEB.Helpers.qs = (sel, el=document) => el.querySelector(sel);
  KGSWEB.Helpers.qsa = (sel, el=document) => Array.prototype.slice.call(el.querySelectorAll(sel));

  // -------------------
  // REST helper
  // -------------------
  KGSWEB.Helpers.rest = (path, opts={}) => {
    const url = (KGSWEB_CFG.rest.root.replace(/\/$/, '') + '/' + path.replace(/^\//,''));
    const headers = opts.headers || {};
    headers['X-WP-Nonce'] = KGSWEB_CFG.rest.nonce;
    return fetch(url, Object.assign({ headers }, opts )).then(r => r.json());
  };

  // -------------------
  // Sort helper (folders/files)
  // -------------------
									   

  KGSWEB.Helpers.sortItems = function(items, opts={}) {
    const mode = opts.mode || 'alpha-asc';  // alpha/date + asc/desc
    const foldersFirst = opts.foldersFirst ?? true;

    return items.slice().sort((a,b) => {
	// Folders first				  
      if(foldersFirst){
        if(a.type==='folder' && b.type!=='folder') return -1;
        if(a.type!=='folder' && b.type==='folder') return 1;
      }

	// Determine comparison value					   
      let valA, valB;

      if(mode.startsWith('alpha')){
        valA = (a.name||'').toLowerCase();
        valB = (b.name||'').toLowerCase();
      } else if(mode.startsWith('date')){
        valA = KGSWEB.Format.extractDate(a.name) || '99999999';
        valB = KGSWEB.Format.extractDate(b.name) || '99999999';
      }

	// Compare			
      let cmp = valA.localeCompare(valB, undefined, { sensitivity:'base' });
      if(mode.endsWith('-desc')) cmp *= -1;

      return cmp;
    });
  };

  // -------------------
  // Minimal date extractor
  // -------------------
	KGSWEB.Format = KGSWEB.Format || {};
	/**
	 * Minimal date extractor
	 * Tries to extract YYYYMMDD from a string like "2025-09-19 Document"
	 * Returns '99999999' if no date found, which keeps items sorted at the end.
	 */
	KGSWEB.Format.extractDate = function(str) {
	  if (!str) return '99999999';
	  const match = str.match(/(\d{4})[-_]?(\d{2})[-_]?(\d{2})/);
	  if (match) return match[1] + match[2] + match[3];
	  return '99999999';
	};
  
  KGSWEB.Format.formatFolderName = function(name) {
		return name ? name.replace(/_/g, ' ') : '';
	};

})(); 
