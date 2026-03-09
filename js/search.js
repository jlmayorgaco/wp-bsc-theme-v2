document.addEventListener('DOMContentLoaded', () => {

  // ── Shared AJAX search runner ─────────────────────────────────────────────
  async function runSearch(query, resultsList) {
    resultsList.innerHTML = '<li class="search-loading">Buscando\u2026</li>';

    const ajaxUrl        = (window.bsc_search && window.bsc_search.ajax_url)        ? window.bsc_search.ajax_url        : '/wp-admin/admin-ajax.php';
    const placeholderImg = (window.bsc_search && window.bsc_search.placeholder_img) ? window.bsc_search.placeholder_img : '';

    try {
      const res = await fetch(
        ajaxUrl + '?action=bsc_search_products&q=' + encodeURIComponent(query)
      );

      if (!res.ok) throw new Error('Network error ' + res.status);

      const data = await res.json();

      resultsList.innerHTML = '';

      if (!data.success || !data.data.products || data.data.products.length === 0) {
        resultsList.innerHTML = '<li class="search-empty">No se encontraron productos.</li>';
        return;
      }

      data.data.products.forEach(product => {
        const li = document.createElement('li');
        li.classList.add('search-result-item');

        const img = document.createElement('img');
        img.alt   = product.name;
        img.classList.add('search-result-image');
        if (product.image) {
          img.src = product.image;
        } else if (placeholderImg) {
          img.src = placeholderImg;
        } else {
          img.style.display = 'none';
        }

        const info = document.createElement('div');
        info.classList.add('search-result-info');

        const name = document.createElement('span');
        name.classList.add('search-result-name');
        name.textContent = product.name;
        info.appendChild(name);

        if (product.brand) {
          const brand = document.createElement('span');
          brand.classList.add('search-result-brand');
          brand.textContent = product.brand;
          info.appendChild(brand);
        }

        if (product.price) {
          const price = document.createElement('span');
          price.classList.add('search-result-price');
          price.textContent = product.price;
          info.appendChild(price);
        }

        li.appendChild(img);
        li.appendChild(info);

        li.addEventListener('click', () => { window.location.href = product.permalink; });

        resultsList.appendChild(li);
      });

    } catch (err) {
      resultsList.innerHTML = '<li class="search-empty">Error al buscar. Intenta de nuevo.</li>';
    }
  }

  // ── Init a search instance (toggle btn + panel + input + results) ─────────
  function initSearchInstance(toggleBtn, searchContainer, searchInput, resultsList) {
    if (!searchContainer || !searchInput || !resultsList) return;

    if (toggleBtn) {
      toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        searchContainer.classList.toggle('visible');
        if (searchContainer.classList.contains('visible')) searchInput.focus();
      });

      document.addEventListener('click', (e) => {
        if (!searchContainer.contains(e.target) && !toggleBtn.contains(e.target)) {
          searchContainer.classList.remove('visible');
        }
      });
    }

    let searchTimeout = null;
    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimeout);
      const query = searchInput.value.trim();
      resultsList.innerHTML = '';
      if (query.length < 3) return;
      searchTimeout = setTimeout(() => runSearch(query, resultsList), 350);
    });
  }

  // ── Desktop search ───────────────────────────────────────────────────────
  initSearchInstance(
    document.querySelector('.btn-search-toggle'),
    document.querySelector('.bsc__header--desktop .header__search'),
    document.querySelector('.bsc__header--desktop .header-search-input'),
    document.querySelector('.bsc__header--desktop .search-results')
  );

  // ── Mobile search ────────────────────────────────────────────────────────
  initSearchInstance(
    document.querySelector('#mobile-search-btn'),
    document.querySelector('.bsc-mobile-search-panel'),
    document.querySelector('.bsc-mobile-search-panel .header-search-input'),
    document.querySelector('.bsc-mobile-search-panel .search-results')
  );

});

// ── Desktop search dropdown position (right-aligned with page container) ─────
function updateSearchPosition() {
  const root = document.documentElement;
  if (!root) return;
  const pageContainerWidth = parseInt(getComputedStyle(root).getPropertyValue('--size--page-container-w')) || 1200;
  const windowWidth = window.innerWidth;
  const rightValue = (windowWidth > pageContainerWidth)
    ? `${(windowWidth - pageContainerWidth) / 2}px`
    : '0px';
  root.style.setProperty('--header-search-right', rightValue);
}

window.addEventListener('load', updateSearchPosition);
window.addEventListener('resize', updateSearchPosition);
