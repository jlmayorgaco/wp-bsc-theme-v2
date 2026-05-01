(function () {
  'use strict';

  var root = document.body;

  if (!root || !root.classList.contains('bsc-order-label-print')) {
    return;
  }

  var defaultWidth = parseFloat(root.dataset.defaultWidthMm || '100');
  var defaultHeight = parseFloat(root.dataset.defaultHeightMm || '153');
  var autoPrint = root.dataset.autoprint === '1';
  var storageWidthKey = 'bsc-order-label-width-mm';
  var storageHeightKey = 'bsc-order-label-height-mm';
  var widthInput = document.getElementById('bsc-label-width-mm');
  var heightInput = document.getElementById('bsc-label-height-mm');
  var sizeStyle = document.getElementById('bsc-label-dynamic-size');
  var pdfWidthInput = document.getElementById('bsc-label-pdf-width');
  var pdfHeightInput = document.getElementById('bsc-label-pdf-height');
  var pdfForm = document.getElementById('bsc-label-pdf-export-form');

  if (!widthInput || !heightInput || !sizeStyle || !pdfWidthInput || !pdfHeightInput || !pdfForm) {
    return;
  }

  function normalizeMm(value, fallbackValue) {
    var parsed = parseFloat(value);

    if (!isFinite(parsed) || parsed < 10) {
      return fallbackValue;
    }

    return Math.round(parsed * 100) / 100;
  }

  function buildSizeCss(widthMm, heightMm) {
    return [
      '@page { size: ' + widthMm + 'mm ' + heightMm + 'mm; margin: 0; }',
      '.bsc-labels-toolbar { max-width: calc(' + widthMm + 'mm + 20px); }',
      '.bsc-print-label {',
      '  width: ' + widthMm + 'mm;',
      '  height: ' + heightMm + 'mm;',
      '  max-width: ' + widthMm + 'mm;',
      '  max-height: ' + heightMm + 'mm;',
      '}',
      '@media print {',
      '  @page { size: ' + widthMm + 'mm ' + heightMm + 'mm; margin: 0; }',
      '  .bsc-print-label {',
      '    width: ' + widthMm + 'mm !important;',
      '    height: ' + heightMm + 'mm !important;',
      '    max-width: ' + widthMm + 'mm !important;',
      '    max-height: ' + heightMm + 'mm !important;',
      '  }',
      '}',
    ].join('\n');
  }

  function applySize(widthMm, heightMm, persist) {
    var normalizedWidth = normalizeMm(widthMm, defaultWidth);
    var normalizedHeight = normalizeMm(heightMm, defaultHeight);

    widthInput.value = normalizedWidth;
    heightInput.value = normalizedHeight;
    pdfWidthInput.value = normalizedWidth;
    pdfHeightInput.value = normalizedHeight;
    sizeStyle.textContent = buildSizeCss(normalizedWidth, normalizedHeight);

    if (persist) {
      window.localStorage.setItem(storageWidthKey, String(normalizedWidth));
      window.localStorage.setItem(storageHeightKey, String(normalizedHeight));
    }
  }

  var api = {
    downloadPdf: function () {
      applySize(widthInput.value, heightInput.value, true);
      pdfForm.submit();
    },
    print: function () {
      applySize(widthInput.value, heightInput.value, true);
      window.print();
    },
    resetSize: function () {
      applySize(defaultWidth, defaultHeight, true);
    },
    closeWindow: function () {
      window.close();
    },
  };

  window.BSCLabelPrint = api;

  widthInput.addEventListener('input', function () {
    applySize(widthInput.value, heightInput.value, true);
  });

  heightInput.addEventListener('input', function () {
    applySize(widthInput.value, heightInput.value, true);
  });

  window.addEventListener('beforeprint', function () {
    applySize(widthInput.value, heightInput.value, true);
  });

  document.addEventListener('click', function (event) {
    var actionButton = event.target.closest('[data-bsc-label-action]');

    if (!actionButton) {
      return;
    }

    event.preventDefault();

    switch (actionButton.dataset.bscLabelAction) {
      case 'download-pdf':
        api.downloadPdf();
        break;
      case 'print':
        api.print();
        break;
      case 'reset-size':
        api.resetSize();
        break;
      case 'close-window':
        api.closeWindow();
        break;
      default:
        break;
    }
  });

  applySize(
    window.localStorage.getItem(storageWidthKey) || defaultWidth,
    window.localStorage.getItem(storageHeightKey) || defaultHeight,
    false
  );

  if (autoPrint) {
    window.addEventListener('load', function () {
      api.print();
    });
  }
})();
