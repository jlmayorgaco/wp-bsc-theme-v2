(function () {
  const dialog = document.getElementById('bsc-bp-confirm');
  if (!dialog) return;

  const summary = document.getElementById('bsc-bp-confirm-summary');
  const confirmButton = dialog.querySelector('[data-bp-confirm]');
  const format = new Intl.NumberFormat('es-CO');
  let pending = null;
  let submitting = false;

  function submitAdjustment() {
    if (!pending) return;
    const { form, operation } = pending;
    pending = null;
    submitting = true;
    form.elements.op.value = operation;
    form.querySelectorAll('[data-bp-op]').forEach((button) => { button.disabled = true; });
    form.setAttribute('aria-busy', 'true');
    form.submit();
  }

  document.querySelectorAll('[data-bp-adjust]').forEach((form) => {
    const amountInput = form.elements.amount;
    const error = form.querySelector('[role="alert"]');
    form.addEventListener('submit', (event) => event.preventDefault());
    amountInput.addEventListener('input', () => { error.hidden = true; });
    form.querySelectorAll('[data-bp-op]').forEach((button) => {
      button.addEventListener('click', () => {
        if (pending || submitting || !form.reportValidity()) return;

        const operation = button.dataset.bpOp;
        const amount = amountInput.valueAsNumber;
        const balance = Number(form.dataset.balance);
        const nextBalance = balance + (operation === 'add' ? amount : -amount);
        if (nextBalance < 0 || nextBalance > 2147483647) {
          error.textContent = nextBalance < 0
            ? 'El saldo no alcanza para este ajuste.'
            : 'El ajuste supera el saldo máximo permitido.';
          error.hidden = false;
          return;
        }
        error.hidden = true;

        pending = { form, operation, button };
        const action = operation === 'add' ? 'Sumar' : 'Restar';
        summary.textContent = action + ' ' + format.format(amount) + ' puntos a ' + form.dataset.client +
          '. Saldo actual: ' + format.format(balance) + '. Saldo estimado: ' + format.format(nextBalance) + '.';
        confirmButton.textContent = action + ' puntos';
        confirmButton.classList.toggle('bsc-bp-dialog__confirm--reduce', operation === 'reduce');

        if (typeof dialog.showModal === 'function') {
          dialog.showModal();
        } else if (window.confirm(summary.textContent)) {
          submitAdjustment();
        } else {
          pending = null;
        }
      });
    });
  });

  confirmButton.addEventListener('click', () => {
    submitAdjustment();
    dialog.close();
  });
  dialog.querySelector('[data-bp-cancel]').addEventListener('click', () => dialog.close());
  dialog.addEventListener('close', () => {
    if (pending) pending.button.focus();
    pending = null;
  });
})();
