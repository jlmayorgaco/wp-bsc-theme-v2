document.addEventListener('DOMContentLoaded', function () {
  var printButton = document.getElementById('bsc-packing-print');
  var closeButton = document.getElementById('bsc-packing-close');
  var ajaxUrl = document.body.getAttribute('data-ajax-url');
  var nonce = document.body.getAttribute('data-nonce');

  if (printButton) {
    printButton.addEventListener('click', function () {
      window.print();
    });
  }

  if (closeButton) {
    closeButton.addEventListener('click', function () {
      window.close();
    });
  }

  document.querySelectorAll('.bsc-packing-confirm').forEach(function (button) {
    button.addEventListener('click', function () {
      var row = button.closest('tr[data-order-id][data-item-id]');
      var source = row ? row.querySelector('.bsc-packing-source') : null;
      var feedback = row ? row.querySelector('.bsc-packing-feedback') : null;

      if (!row || !source || !ajaxUrl || !nonce) {
        return;
      }

      button.disabled = true;
      source.disabled = true;
      if (feedback) {
        feedback.textContent = 'Descontando stock...';
        feedback.classList.remove('is-error');
      }

      var formData = new FormData();
      formData.append('action', 'bsc_pack_order_item_stock');
      formData.append('nonce', nonce);
      formData.append('order_id', row.getAttribute('data-order-id'));
      formData.append('item_id', row.getAttribute('data-item-id'));
      formData.append('source', source.value);

      fetch(ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (payload) {
          if (!payload || !payload.success) {
            throw new Error((payload && payload.data && payload.data.message) || 'No se pudo descontar stock.');
          }

          row.classList.add('is-packed');
          var label = source.value === 'tienda' ? 'showroom' : 'bodega';
          var actionCell = row.querySelector('.bsc-packing-action');
          if (actionCell) {
            actionCell.innerHTML = '<span class="bsc-packing-packed">Descontado de ' + label + '</span>';
          }
        })
        .catch(function (error) {
          button.disabled = false;
          source.disabled = false;
          if (feedback) {
            feedback.textContent = error.message;
            feedback.classList.add('is-error');
          }
        });
    });
  });
});
