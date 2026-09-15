(() => {
  const form = document.querySelector('#bsc-customer-editor-form');

  if (!form) {
    return;
  }

  const submitButton = form.querySelector('button[type="submit"]');
  const initialButtonLabel = submitButton?.textContent || '';
  let isDirty = false;

  const markDirty = () => {
    isDirty = true;
  };

  form.addEventListener('input', markDirty);
  form.addEventListener('change', markDirty);

  form.addEventListener('submit', () => {
    isDirty = false;
    form.setAttribute('aria-busy', 'true');

    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = submitButton.dataset.savingLabel || initialButtonLabel;
    }
  });

  window.addEventListener('beforeunload', (event) => {
    if (!isDirty) {
      return;
    }

    event.preventDefault();
    event.returnValue = '';
  });

  window.addEventListener('pageshow', () => {
    form.removeAttribute('aria-busy');

    if (submitButton) {
      submitButton.disabled = false;
      submitButton.textContent = initialButtonLabel;
    }
  });

  const firstInvalidField = form.querySelector('[aria-invalid="true"]');
  if (firstInvalidField instanceof HTMLElement) {
    firstInvalidField.focus();
  }
})();
