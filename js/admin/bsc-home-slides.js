/**
 * Responsive image selectors for the Home Slide editor.
 */
(function () {
  'use strict';

  function clearPreview(preview) {
    while (preview.firstChild) {
      preview.removeChild(preview.firstChild);
    }
  }

  function showEmptyState(field) {
    var preview = field.querySelector('[data-bsc-home-slide-image-preview]');
    var message = document.createElement('p');

    clearPreview(preview);
    message.textContent = field.getAttribute('data-empty-message') || '';
    preview.appendChild(message);
  }

  function showAttachment(field, attachment) {
    var preview = field.querySelector('[data-bsc-home-slide-image-preview]');
    var image = document.createElement('img');
    var medium = attachment.sizes && attachment.sizes.medium;

    clearPreview(preview);
    image.src = medium ? medium.url : attachment.url;
    image.alt = '';
    preview.appendChild(image);
  }

  document.addEventListener('click', function (event) {
    var selectButton = event.target.closest('[data-bsc-home-slide-image-select]');
    var removeButton = event.target.closest('[data-bsc-home-slide-image-remove]');
    var field;
    var input;
    var frame;

    if (!selectButton && !removeButton) {
      return;
    }

    event.preventDefault();
    field = (selectButton || removeButton).closest('[data-bsc-home-slide-image-field]');
    input = field.querySelector('[data-bsc-home-slide-image-id]');

    if (removeButton) {
      input.value = '';
      removeButton.hidden = true;
      field.querySelector('[data-bsc-home-slide-image-select]').textContent = 'Seleccionar imagen';
      showEmptyState(field);
      return;
    }

    if (!window.wp || !window.wp.media) {
      return;
    }

    frame = window.wp.media({
      title: selectButton.getAttribute('data-media-title') || 'Seleccionar imagen',
      button: {
        text: 'Usar esta imagen',
      },
      library: {
        type: 'image',
      },
      multiple: false,
    });

    frame.on('select', function () {
      var attachment = frame.state().get('selection').first().toJSON();

      input.value = attachment.id;
      selectButton.textContent = 'Cambiar imagen';
      field.querySelector('[data-bsc-home-slide-image-remove]').hidden = false;
      showAttachment(field, attachment);
    });

    frame.open();
  });
}());
