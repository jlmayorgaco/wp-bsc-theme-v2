document.addEventListener('DOMContentLoaded', function () {
  var printButton = document.getElementById('bsc-packing-print');
  var closeButton = document.getElementById('bsc-packing-close');

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
});
