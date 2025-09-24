// js/kgsweb-slides.js
(function($){
  $(function(){

    $('.kgsweb-slides-wrapper').each(function(){
        const wrapper = this;
        const iframe = wrapper.querySelector('.kgsweb-slides-iframe');
        const thumbs = wrapper.querySelectorAll('.kgsweb-slide-thumb');
        let currentIndex = 0;
        let autoplayInterval = null;

        // Create arrow buttons if not already in HTML
        if (!wrapper.querySelector('.kgsweb-slide-prev')) {
            const arrowPrev = document.createElement('button');
            arrowPrev.className = 'kgsweb-slide-arrow kgsweb-slide-prev';
            arrowPrev.innerHTML = '&#10094;'; // left arrow
            wrapper.appendChild(arrowPrev);

            const arrowNext = document.createElement('button');
            arrowNext.className = 'kgsweb-slide-arrow kgsweb-slide-next';
            arrowNext.innerHTML = '&#10095;'; // right arrow
            wrapper.appendChild(arrowNext);
        }

        const arrowPrev = wrapper.querySelector('.kgsweb-slide-prev');
        const arrowNext = wrapper.querySelector('.kgsweb-slide-next');

        // Set slide
        function setSlide(index){
            if (!thumbs[index]) return;
            thumbs.forEach(t=>t.classList.remove('active-slide'));
            thumbs[index].classList.add('active-slide');
            iframe.src = thumbs[index].dataset.src || iframe.src;
            thumbs[index].scrollIntoView({behavior:'smooth',inline:'center',block:'nearest'});
            currentIndex = index;
        }

        // Thumbnail click
        thumbs.forEach((thumb,i)=>{
            thumb.addEventListener('click', ()=> setSlide(i));
        });

        // Arrow click
        arrowPrev?.addEventListener('click', ()=> setSlide((currentIndex-1+thumbs.length)%thumbs.length));
        arrowNext?.addEventListener('click', ()=> setSlide((currentIndex+1)%thumbs.length));

        // Keyboard navigation
        document.addEventListener('keydown', function(e){
            if (!wrapper.contains(document.activeElement)) return;
            if (e.key === "ArrowLeft")  setSlide((currentIndex-1+thumbs.length)%thumbs.length);
            if (e.key === "ArrowRight") setSlide((currentIndex+1)%thumbs.length);
        });

        // Autoplay
        const autoplay = wrapper.dataset.autoplay === 'true';
        const delay = parseInt(wrapper.dataset.delay) || 3000;
        if(autoplay && thumbs.length>1){
            autoplayInterval = setInterval(()=> setSlide((currentIndex+1)%thumbs.length), delay);
            // pause on hover
            wrapper.addEventListener('mouseenter', ()=> clearInterval(autoplayInterval));
            wrapper.addEventListener('mouseleave', ()=> autoplayInterval = setInterval(()=> setSlide((currentIndex+1)%thumbs.length), delay));
        }

        // Initialize first slide
        setSlide(0);
    });

  });
})(jQuery);
