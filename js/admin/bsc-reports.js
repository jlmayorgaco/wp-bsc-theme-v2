(function () {
  var stockForm = document.querySelector('[data-bsc-stock-search-form]');
  var perPageSelect = document.getElementById('bsc-stock-per-page');
  var searchInput = document.getElementById('bsc-stock-search');

  if (!stockForm) {
    return;
  }

  function submitStockForm() {
    if (typeof stockForm.requestSubmit === 'function') {
      stockForm.requestSubmit();
      return;
    }

    stockForm.submit();
  }

  if (perPageSelect) {
    perPageSelect.addEventListener('change', submitStockForm);
  }

  if (searchInput) {
    searchInput.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') {
        return;
      }

      if (searchInput.value === '') {
        return;
      }

      searchInput.value = '';
      submitStockForm();
    });
  }
})();
