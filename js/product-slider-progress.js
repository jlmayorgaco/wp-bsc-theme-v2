(function () {
  'use strict';

  function initProductSlider(slider) {
    var progress = slider.nextElementSibling;
    var thumb;
    var frameId = null;

    if (!progress || !progress.classList.contains('bsc__slider-progress')) return;

    thumb = progress.querySelector('.bsc__slider-progress-thumb');

    if (!thumb) return;

    function updateProgress() {
      var maxScroll = slider.scrollWidth - slider.clientWidth;
      var trackWidth;
      var visibleRatio;
      var thumbWidth;
      var travel;
      var position;

      frameId = null;

      progress.classList.add('is-active');
      trackWidth = progress.clientWidth;

      if (maxScroll <= 1 || trackWidth <= 0) {
        progress.classList.remove('is-active');
        progress.setAttribute('aria-valuenow', '0');
        thumb.style.transform = 'translate3d(0, 0, 0)';
        return;
      }

      progress.classList.add('is-active');
      visibleRatio = Math.min(1, slider.clientWidth / slider.scrollWidth);
      thumbWidth = Math.max(28, trackWidth * visibleRatio);
      travel = Math.max(0, trackWidth - thumbWidth);
      position = Math.min(1, Math.max(0, slider.scrollLeft / maxScroll));

      thumb.style.width = thumbWidth + 'px';
      thumb.style.transform = 'translate3d(' + (travel * position) + 'px, 0, 0)';
      progress.setAttribute('aria-valuenow', String(Math.round(position * 100)));
    }

    function requestUpdate() {
      if (frameId !== null) return;

      frameId = window.requestAnimationFrame(updateProgress);
    }

    slider.addEventListener('scroll', requestUpdate, { passive: true });
    window.addEventListener('resize', requestUpdate);

    if ('ResizeObserver' in window) {
      new window.ResizeObserver(requestUpdate).observe(slider);
    }

    requestUpdate();
  }

  document.addEventListener('DOMContentLoaded', function () {
    Array.prototype.forEach.call(
      document.querySelectorAll('.bsc__slider'),
      initProductSlider
    );
  });
}());
