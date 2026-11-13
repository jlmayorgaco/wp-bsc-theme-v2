(() => {
  const editor = document.querySelector('.bsc-workshops-admin__editor');
  if (!editor) return;

  const slots = editor.querySelector('[data-workshop-slots]');
  const template = editor.querySelector('[data-workshop-slot-template]');
  editor.querySelector('[data-add-workshop-slot]').addEventListener('click', () => {
    const key = typeof window.crypto.randomUUID === 'function'
      ? window.crypto.randomUUID()
      : Array.from(window.crypto.getRandomValues(new Uint8Array(16)), (byte, index) => {
          if (index === 6) byte = (byte & 15) | 64;
          if (index === 8) byte = (byte & 63) | 128;
          return ([4, 6, 8, 10].includes(index) ? '-' : '') + byte.toString(16).padStart(2, '0');
        }).join('');
    const row = template.content.cloneNode(true);
    row.querySelectorAll('[name]').forEach((input) => {
      input.name = input.name.replace('__new__', key);
    });
    const firstInput = row.querySelector('input');
    slots.append(row);
    firstInput.focus();
  });
  slots.addEventListener('click', (event) => {
    const button = event.target.closest('[data-remove-workshop-slot]');
    if (button) {
      button.closest('[data-workshop-slot]').remove();
      editor.querySelector('[data-add-workshop-slot]').focus();
    }
  });

  const imageId = editor.elements.image;
  const preview = editor.querySelector('[data-workshop-image]');
  editor.querySelector('[data-choose-workshop-image]')?.addEventListener('click', () => {
    const frame = wp.media({ title: 'Elegir foto del taller', library: { type: 'image' }, multiple: false });
    frame.on('select', () => {
      const attachment = frame.state().get('selection').first().toJSON();
      const img = document.createElement('img');
      img.src = attachment.sizes?.thumbnail?.url || attachment.url;
      img.alt = '';
      imageId.value = attachment.id;
      preview.replaceChildren(img);
    });
    frame.open();
  });
  editor.querySelector('[data-clear-workshop-image]')?.addEventListener('click', () => {
    imageId.value = '';
    preview.replaceChildren();
  });
  document.querySelectorAll('[data-cancel-workshop-booking]').forEach((button) => {
    button.addEventListener('click', (event) => {
      if (!window.confirm('¿Confirmas que quieres continuar con ' + button.textContent.toLowerCase() + '?')) event.preventDefault();
    });
  });
})();
