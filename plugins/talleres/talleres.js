(() => {
  const carousel = document.querySelector('[data-workshop-carousel]');
  if (carousel) {
    const slides = [...carousel.querySelectorAll('.bsc-workshops__slide')];
    const videos = [...carousel.querySelectorAll('video')];
    videos.forEach((video) => video.addEventListener('play', () => {
      videos.forEach((other) => { if (other !== video && !other.paused) other.pause(); });
    }));

    if (slides.length > 1) {
      const status = carousel.querySelector('[data-workshop-slide-status]');
      let current = slides.findIndex((slide) => slide.dataset.position === '0');
      const step = (direction) => {
        current = (current + direction + slides.length) % slides.length;
        slides.forEach((slide) => { delete slide.dataset.position; });
        videos.forEach((video) => { if (!video.paused) video.pause(); });
        const next = (current + 1) % slides.length;
        if (slides.length > 2) slides[(current + slides.length - 1) % slides.length].dataset.position = '-1';
        slides[current].dataset.position = '0';
        slides[next].dataset.position = '1';
        slides.filter((slide) => slide.hasAttribute('data-position')).forEach((slide) => {
          const video = slide.querySelector('video');
          if (video) video.preload = 'metadata';
        });
        status.textContent = slides[current].querySelector('figcaption').textContent;
      };
      carousel.querySelectorAll('[data-workshop-direction]').forEach((button) => {
        button.hidden = false;
        button.addEventListener('click', () => step(Number(button.dataset.workshopDirection)));
      });
      let touchStart;
      carousel.addEventListener('touchstart', (event) => {
        touchStart = undefined;
        if (event.touches.length !== 1 || event.target.closest('button')) return;
        const {clientX, clientY} = event.touches[0];
        const video = event.target.closest('video');
        if (video && (!video.paused || clientY > video.getBoundingClientRect().bottom - 48)) return;
        touchStart = {clientX, clientY};
      }, {passive: true});
      carousel.addEventListener('touchend', (event) => {
        if (!touchStart || event.touches.length) return;
        const x = event.changedTouches[0].clientX - touchStart.clientX;
        const y = event.changedTouches[0].clientY - touchStart.clientY;
        touchStart = undefined;
        if (Math.abs(x) > 42 && Math.abs(x) > Math.abs(y) * 1.3) step(x < 0 ? 1 : -1);
      }, {passive: true});
      carousel.addEventListener('touchcancel', () => { touchStart = undefined; });
    }
  }

  const next = document.querySelector('.bsc-workshops__next');
  if (next) {
    const cells = next.querySelectorAll('[data-workshop-countdown]');
    const start = Number(next.dataset.start);
    const render = () => {
      const minutes = Math.max(0, Math.ceil((start - Date.now() / 1000) / 60));
      const values = [Math.floor(minutes / 1440), Math.floor(minutes % 1440 / 60), minutes % 60];
      cells.forEach((cell, index) => { cell.textContent = String(values[index]).padStart(2, '0'); });
      return minutes > 0;
    };
    if (render()) {
      const timer = window.setInterval(() => { if (!render()) window.clearInterval(timer); }, 5000);
    }
  }

  const numberFormat = new Intl.NumberFormat('es-CO', { useGrouping: 'always' });
  document.querySelectorAll('.bsc-workshops__booking').forEach((form) => {
    const seats = form.elements.seats;
    if (!seats) return;
    const remaining = form.querySelector('[data-remaining]');
    const price = form.querySelector('[data-price]');
    const unitPrice = Number(price.dataset.price);
    const less = form.querySelector('[data-step="-1"]');
    const more = form.querySelector('[data-step="1"]');

    const refresh = (normalize = false) => {
      const slot = form.querySelector('input[name="slot"]:checked');
      const available = Number(slot.dataset.seats);
      const quantity = Math.max(1, Math.min(available, Math.trunc(seats.valueAsNumber || 1)));
      seats.max = available;
      if (normalize) seats.value = quantity;
      less.disabled = quantity <= 1;
      more.disabled = quantity >= available;
      remaining.textContent = available === 1 ? '¡Queda el último cupo!' :
        (available <= 3 ? '¡Quedan los últimos ' + available + ' cupos!' : 'Quedan ' + available + ' cupos');
      price.textContent = unitPrice ? '$' + numberFormat.format(unitPrice * quantity) : 'Gratis';
    };
    form.querySelectorAll('input[name="slot"]').forEach((radio) => radio.addEventListener('change', () => refresh(true)));
    less.addEventListener('click', () => { seats.stepDown(); refresh(true); });
    more.addEventListener('click', () => { seats.stepUp(); refresh(true); });
    seats.addEventListener('input', () => refresh());
    seats.addEventListener('change', () => refresh(true));
    form.addEventListener('invalid', (event) => {
      const details = event.target.closest('details');
      if (details) details.open = true;
    }, true);
    refresh();
  });
})();
