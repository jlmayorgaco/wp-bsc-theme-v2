(function () {
  'use strict';

  document.addEventListener('click', function (event) {
    const trigger = event.target.closest('[data-bsc-confirm]');

    if (!trigger) {
      return;
    }

    const message = trigger.getAttribute('data-bsc-confirm') || 'Confirmar acción';

    if (!window.confirm(message)) {
      event.preventDefault();
    }
  });
})();
