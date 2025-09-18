// js/kgsweb-menus.js
(function(){
  function load(el){
    const type = el.getAttribute('data-type') || 'breakfast';
    KGSWEB.rest('menu?type='+encodeURIComponent(type)).then(data=>{
      const cont = el.querySelector('.kgsweb-menu-image');
      if (data && data.image_url) {
        cont.innerHTML = '<img src="'+data.image_url+'" alt="'+type+' menu" />';
        // TODO: add zoom icon and modal behavior
      } else {
        cont.textContent = 'Menu not available.';
      }
    });
  }
  document.addEventListener('DOMContentLoaded', function(){
    (window.KGSWEB.qsa||function(){} )('.kgsweb-menu').forEach(load);
  });
})();