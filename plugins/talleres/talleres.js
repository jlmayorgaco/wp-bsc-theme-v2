(() => {
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
