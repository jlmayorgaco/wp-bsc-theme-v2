document.addEventListener('DOMContentLoaded', () => {
  const minInput = document.getElementById('min_price');
  const maxInput = document.getElementById('max_price');

  minInput.addEventListener('input', () => {
    if (parseInt(minInput.value) > parseInt(maxInput.value)) {
      maxInput.value = minInput.value;
      document.getElementById('max_price_output').value = minInput.value;
    }
  });

  maxInput.addEventListener('input', () => {
    if (parseInt(maxInput.value) < parseInt(minInput.value)) {
      minInput.value = maxInput.value;
      document.getElementById('min_price_output').value = maxInput.value;
    }
  });
});


document.addEventListener("DOMContentLoaded", () => {
  const filterGroups = document.querySelectorAll(".bsc__filters-group");

  filterGroups.forEach((group) => {
    const summary = group.querySelector("summary");

    summary?.addEventListener("click", (e) => {
      e.preventDefault(); // Prevents native toggle
      const isOpen = group.classList.contains("is-open");
      document.querySelectorAll(".bsc__filters-group").forEach(g => {
        g.classList.remove("is-open")
        g.open = false;
      });
      if (!isOpen) {
        group.open = true;
        group.classList.add("is-open");
      }
    });
  });
});






jQuery(document).ready(function($) {
    $('#bscFiltersForm').on('change', 'input, select', function(e) {
        const form = $('#bscFiltersForm');
        const data = form.serialize();

        $.ajax({
            type: 'GET',
            url: bsc_ajax.ajax_url,
            data: data + '&action=bsc_filter_products',
            beforeSend: function () {
              $('#bscProductsContainer').html(`
                <div class="bsc__loading">
                  <div class="bsc__spinner"></div>
                  <p class="bsc__loading-text">Cargando productos...</p>
                </div>
              `);
            },
            success: function(response) {
                if (response.success) {
                  setTimeout(() => {
                    Object.entries(response.data).forEach((entry => {
                      const key = entry[0];
                      const value = entry[1];
                      $(key).html(value);   
                    }));
                  }, 500)
                } else {
                    $('#bscProductsContainer').html('<p>No se encontraron productos.</p>');
                }
            },
            error: function() {
                $('#bscProductsContainer').html('<p>Error al cargar productos.</p>');
            }
        });
    });
});
