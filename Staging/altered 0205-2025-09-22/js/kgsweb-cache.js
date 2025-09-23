// js/kgsweb-cache.js
(function(){
  window.KGSWEB = window.KGSWEB || {};
  KGSWEB.sget = (k) => { try { return JSON.parse(sessionStorage.getItem(k)); } catch(e){ return null; } };
  KGSWEB.sset = (k,v) => { try { sessionStorage.setItem(k, JSON.stringify(v)); } catch(e){} };
})();
