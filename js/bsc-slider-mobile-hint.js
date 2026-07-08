(function () {
  'use strict';

  var mobileQuery = '(max-width: 767px)';
  var slideDelay = 3800;

  document.addEventListener('DOMContentLoaded', function () {
    var containers = Array.prototype.slice.call(
      document.querySelectorAll('.home__about--section .about__cols')
    );

    if (!containers.length) return;

    containers.forEach(function (container) {
      var slides = Array.prototype.slice.call(container.children).filter(function (child) {
        return child.classList.contains('about__col');
      });

      if (slides.length < 2) return;

      var media = window.matchMedia ? window.matchMedia(mobileQuery) : null;
      var activeIndex = 0;
      var intervalId = null;

      function isMobile() {
        return media ? media.matches : window.innerWidth <= 767;
      }

      function setSlide(index) {
        activeIndex = (index + slides.length) % slides.length;

        slides.forEach(function (slide, slideIndex) {
          var isActive = slideIndex === activeIndex;

          slide.classList.toggle('is-active', isActive);
          slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        });
      }

      function stopTimer() {
        if (intervalId === null) return;

        window.clearInterval(intervalId);
        intervalId = null;
      }

      function startTimer() {
        stopTimer();

        if (document.hidden) return;

        intervalId = window.setInterval(function () {
          setSlide(activeIndex + 1);
        }, slideDelay);
      }

      function enable() {
        container.classList.add('is-slider-ready');
        setSlide(activeIndex);
        startTimer();
      }

      function disable() {
        stopTimer();
        container.classList.remove('is-slider-ready');

        slides.forEach(function (slide) {
          slide.classList.remove('is-active');
          slide.removeAttribute('aria-hidden');
        });
      }

      function sync() {
        if (isMobile()) {
          enable();
          return;
        }

        disable();
      }

      sync();

      if (media) {
        if (typeof media.addEventListener === 'function') {
          media.addEventListener('change', sync);
        } else if (typeof media.addListener === 'function') {
          media.addListener(sync);
        }
      } else {
        window.addEventListener('resize', sync);
      }

      document.addEventListener('visibilitychange', function () {
        if (!isMobile()) return;

        if (document.hidden) {
          stopTimer();
          return;
        }

        startTimer();
      });
    });
  });
}());
