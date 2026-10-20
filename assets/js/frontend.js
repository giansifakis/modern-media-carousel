(function () {
  'use strict';

  function initializeCarousel(carousel) {
    if (carousel.dataset.mmcReady) return;
    carousel.dataset.mmcReady = 'true';

    var track = carousel.querySelector('[data-mmc-track]');
    var slides = Array.prototype.slice.call(carousel.querySelectorAll('.mmc-slide'));
    var current = carousel.querySelector('[data-mmc-current]');
    var prev = carousel.querySelector('[data-mmc-prev]');
    var next = carousel.querySelector('[data-mmc-next]');
    var viewport = carousel.querySelector('.mmc-viewport');
    var index = 0;

    if (!track || slides.length < 2) return;

    function goTo(target) {
      index = (target + slides.length) % slides.length;
      track.style.transform = 'translate3d(-' + (index * 100) + '%, 0, 0)';
      slides.forEach(function (slide, position) {
        var active = position === index;
        slide.classList.toggle('is-active', active);
        slide.setAttribute('aria-hidden', active ? 'false' : 'true');
      });
      if (current) current.textContent = String(index + 1);
    }

    if (prev) prev.addEventListener('click', function () { goTo(index - 1); });
    if (next) next.addEventListener('click', function () { goTo(index + 1); });

    carousel.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        goTo(index - 1);
      } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        goTo(index + 1);
      }
    });

    // Native, passive touch gestures; vertical page scrolling stays available.
    if (viewport) {
      var startX = 0;
      var startY = 0;
      var tracking = false;
      viewport.addEventListener('touchstart', function (event) {
        if (event.touches.length !== 1) { tracking = false; return; }
        startX = event.touches[0].clientX;
        startY = event.touches[0].clientY;
        tracking = true;
      }, { passive: true });
      viewport.addEventListener('touchend', function (event) {
        if (!tracking || !event.changedTouches.length) return;
        tracking = false;
        var dx = event.changedTouches[0].clientX - startX;
        var dy = event.changedTouches[0].clientY - startY;
        if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy) * 1.25) {
          goTo(index + (dx < 0 ? 1 : -1));
        }
      }, { passive: true });
      viewport.addEventListener('touchcancel', function () { tracking = false; }, { passive: true });
    }
  }

  function initializeAll() {
    document.querySelectorAll('[data-mmc-carousel]').forEach(initializeCarousel);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAll);
  } else {
    initializeAll();
  }
})();
