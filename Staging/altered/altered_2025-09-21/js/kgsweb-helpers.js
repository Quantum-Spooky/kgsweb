// js/kgsweb-helpers.js
(function(){
  window.KGSWEB = window.KGSWEB || {};
  KGSWEB.qs = (sel, el=document) => el.querySelector(sel);
  KGSWEB.qsa = (sel, el=document) => Array.prototype.slice.call(el.querySelectorAll(sel));
  KGSWEB.rest = (path, opts={}) => {
    const url = (KGSWEB_CFG.rest.root.replace(/\/$/, '') + '/' + path.replace(/^\//,''));
    const headers = opts.headers || {};
    headers['X-WP-Nonce'] = KGSWEB_CFG.rest.nonce;
    return fetch(url, Object.assign({ headers }, opts )).then(r => r.json());
  };
})();