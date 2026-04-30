(function () {
  var searchInput = document.getElementById('bsc-stock-search');
  var countEl = document.getElementById('bsc-stock-count');
  var rows = document.querySelectorAll('#bsc-stock-table .bsc-admin-reports__stock-row');

  if (!searchInput || !countEl || !rows.length) {
    return;
  }

  searchInput.addEventListener('input', function () {
    var query = searchInput.value.toLowerCase().trim();
    var visible = 0;

    rows.forEach(function (row) {
      var text = row.textContent.toLowerCase();
      var matches = !query || text.indexOf(query) !== -1;

      row.classList.toggle('bsc-admin-reports__stock-row--hidden', !matches);

      if (matches) {
        visible += 1;
      }
    });

    countEl.textContent = query ? visible + ' resultado(s)' : '';
  });
})();
