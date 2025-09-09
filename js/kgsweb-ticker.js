// js/kgsweb-ticker.js
(function(){
  function initTicker(el){
    const id = el.getAttribute('data-folder') || '';
    const speed = parseFloat(el.getAttribute('data-speed')) || 0.5;
    const key = 'kgsweb_ticker_' + id;
    const track = el.querySelector('.kgsweb-ticker-track');
    const full = el.querySelector('.kgsweb-ticker-full');
    const btnToggle = el.querySelector('.kgsweb-ticker-toggle');
    const btnExpand = el.querySelector('.kgsweb-ticker-expand');

    function render(text){
      track.textContent = text || '';
      full.textContent = text || '';
    }

    function fetchAndRender(){
      KGSWEB.rest('ticker?id='+encodeURIComponent(id)).then(data=>{
        if (data && data.text !== undefined) {
          KGSWEB.sset(key, data);
          render(data.text);
          start();
        }
      });
    }

    let rafId = null, pos=0, playing=true;
    function frame(){
      pos -= speed;
      track.style.transform = 'translateX('+pos+'px)';
      rafId = requestAnimationFrame(frame);
    }
    function start(){ if(!rafId){ rafId = requestAnimationFrame(frame); playing=true; } }
    function stop(){ if(rafId){ cancelAnimationFrame(rafId); rafId=null; playing=false; } }

    el.addEventListener('mouseenter', stop);
    el.addEventListener('mouseleave', ()=> playing && start());
    btnToggle.addEventListener('click', ()=> { playing ? stop() : start(); });
    btnExpand.addEventListener('click', ()=>{
      const hidden = full.hasAttribute('hidden');
      if (hidden) full.removeAttribute('hidden'); else full.setAttribute('hidden','');
      btnExpand.setAttribute('aria-expanded', hidden ? 'true':'false');
    });

    const cached = KGSWEB.sget(key);
    if (cached) { render(cached.text); start(); }
    else { fetchAndRender(); }
  }

  document.addEventListener('DOMContentLoaded', function(){
    (window.KGSWEB.qsa||function(){} )('.kgsweb-ticker').forEach(initTicker);
  });
})();