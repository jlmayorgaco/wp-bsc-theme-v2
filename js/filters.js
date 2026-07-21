document.addEventListener('DOMContentLoaded', () => {
  const minInput = document.getElementById('min_price');
  const maxInput = document.getElementById('max_price');
  const minOutput = document.getElementById('min_price_output');
  const maxOutput = document.getElementById('max_price_output');
  const priceWrapper = minInput?.closest('.bsc__filters-price-wrapper');

  if (!minInput || !maxInput || !minOutput || !maxOutput || !priceWrapper) {
    return;
  }

  const priceFormatter = new Intl.NumberFormat('es-CO', {
    maximumFractionDigits: 0,
  });

  const formatPrice = (value) => `$${priceFormatter.format(Number(value))}`;

  const syncOutputs = () => {
    const rangeMin = Number(minInput.min);
    const rangeMax = Number(minInput.max);
    const rangeSize = rangeMax - rangeMin || 1;
    const minPercent = ((Number(minInput.value) - rangeMin) / rangeSize) * 100;
    const maxPercent = ((Number(maxInput.value) - rangeMin) / rangeSize) * 100;
    const formattedMin = formatPrice(minInput.value);
    const formattedMax = formatPrice(maxInput.value);

    minOutput.value = formattedMin;
    maxOutput.value = formattedMax;
    minInput.setAttribute('aria-valuetext', formattedMin);
    maxInput.setAttribute('aria-valuetext', formattedMax);
    priceWrapper.style.setProperty('--bsc-range-min', `${minPercent}%`);
    priceWrapper.style.setProperty('--bsc-range-max', `${maxPercent}%`);
  };

  minInput.addEventListener('input', () => {
    if (parseInt(minInput.value) > parseInt(maxInput.value)) {
      maxInput.value = minInput.value;
    }

    syncOutputs();
  });

  maxInput.addEventListener('input', () => {
    if (parseInt(maxInput.value) < parseInt(minInput.value)) {
      minInput.value = maxInput.value;
    }

    syncOutputs();
  });

  syncOutputs();
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






document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('bscFiltersForm');
  const activeFilters = document.querySelector('[data-bsc-active-filters]');

  if (!form || !activeFilters) {
    return;
  }

  const getInputLabel = (input) => {
    const label = input.closest('.bsc__filters-option');
    const text = label ? label.textContent.trim() : '';

    return text || input.value;
  };

  const getSelectedFilterInputs = () => Array.from(
    form.querySelectorAll('.bsc__filters-input[type="checkbox"]:checked, .bsc__filters-input[type="radio"]:checked')
  );

  const updateActiveFilters = () => {
    const selectedInputs = getSelectedFilterInputs();
    const fragment = document.createDocumentFragment();

    selectedInputs.forEach((input) => {
      const label = getInputLabel(input);
      const badge = document.createElement('button');
      const badgeLabel = document.createElement('span');
      const closeIcon = document.createElement('span');

      badge.type = 'button';
      badge.className = 'bsc__active-filter-badge';
      badge.dataset.filterName = input.name;
      badge.dataset.filterValue = input.value;
      badge.setAttribute('aria-label', `Quitar filtro ${label}`);

      badgeLabel.className = 'bsc__active-filter-badge-label';
      badgeLabel.textContent = label;

      closeIcon.className = 'bsc__active-filter-badge-close';
      closeIcon.setAttribute('aria-hidden', 'true');
      closeIcon.textContent = 'x';

      badge.append(badgeLabel, closeIcon);
      fragment.appendChild(badge);
    });

    activeFilters.replaceChildren(fragment);
    activeFilters.hidden = selectedInputs.length === 0;
  };

  form.addEventListener('change', (event) => {
    if (event.target.matches('.bsc__filters-input')) {
      updateActiveFilters();
    }
  });

  activeFilters.addEventListener('click', (event) => {
    const badge = event.target.closest('.bsc__active-filter-badge');

    if (!badge) {
      return;
    }

    const input = Array.from(form.querySelectorAll('.bsc__filters-input')).find((candidate) => (
      candidate.name === badge.dataset.filterName && candidate.value === badge.dataset.filterValue
    ));

    if (!input) {
      return;
    }

    input.checked = false;
    updateActiveFilters();
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });

  updateActiveFilters();
});

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('bscFiltersForm');
  const sidebar = form?.closest('.shop__sidebar');
  const toggle = sidebar?.querySelector('.bsc__filters-mobile-toggle');
  const closeButton = sidebar?.querySelector('.bsc__filters-mobile-close');
  const count = sidebar?.querySelector('.bsc__filters-mobile-count');

  if (!form || !sidebar || !toggle || !closeButton || !count) {
    return;
  }

  sidebar.classList.add('has-mobile-filter-toggle');

  const setOpen = (isOpen, returnFocus = false) => {
    sidebar.classList.toggle('is-filters-open', isOpen);
    toggle.setAttribute('aria-expanded', String(isOpen));

    if (returnFocus) {
      toggle.focus();
    }
  };

  const updateCount = () => {
    const selectedCount = form.querySelectorAll(
      '.bsc__filters-input[type="checkbox"]:checked, .bsc__filters-input[type="radio"]:checked'
    ).length;

    count.textContent = String(selectedCount);
    count.hidden = selectedCount === 0;
  };

  toggle.addEventListener('click', () => {
    setOpen(!sidebar.classList.contains('is-filters-open'));
  });

  closeButton.addEventListener('click', () => {
    setOpen(false, true);
  });

  form.addEventListener('change', updateCount);

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && sidebar.classList.contains('is-filters-open')) {
      setOpen(false, true);
    }
  });

  updateCount();
});


jQuery(document).ready(function($) {
    $('#bscFiltersForm').on('change', 'input, select', function(e) {
        const form = $('#bscFiltersForm');
        const data = form.serialize();

        $.ajax({
            type: 'GET',
            url: bsc_ajax.ajax_url,
            data: data + '&action=bsc_filter_products&nonce=' + encodeURIComponent(bsc_ajax.nonce),
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
                    $('#bscProductsContainer').html(bsc_filters.empty_products_html);
                }
            },
            error: function() {
                $('#bscProductsContainer').html('<p>Error al cargar productos.</p>');
            }
        });
    });
});
