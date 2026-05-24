(function () {
  const forms = document.querySelectorAll('.bsc-importer-admin__form');

  forms.forEach((form) => {
    const submitterButtons = form.querySelectorAll('button[type="submit"][data-confirm]');

    submitterButtons.forEach((button) => {
      button.addEventListener('click', (event) => {
        const message = button.getAttribute('data-confirm');

        if (message && !window.confirm(message)) {
          event.preventDefault();
        }
      });
    });

    form.addEventListener('submit', (event) => {
      if (form.dataset.submitting === 'true') {
        event.preventDefault();
        return;
      }

      const submitButton = event.submitter || form.querySelector('button[type="submit"]');

      if (!submitButton || submitButton.disabled) {
        return;
      }

      form.dataset.submitting = 'true';
      submitButton.classList.add('is-busy');
      submitButton.setAttribute('aria-busy', 'true');

      form.querySelectorAll('button[type="submit"]').forEach((button) => {
        button.disabled = true;
      });
    });
  });
})();
