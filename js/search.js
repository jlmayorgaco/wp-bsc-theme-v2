document.addEventListener('DOMContentLoaded', () => {
  const MOBILE_BREAKPOINT = 1024;

  function isMobileView() {
    return window.innerWidth <= MOBILE_BREAKPOINT;
  }

  // ── Shared AJAX search runner ─────────────────────────────────────────────
  async function runSearch(query, resultsList) {
    resultsList.innerHTML = '<li class="search-loading">Buscando…</li>';

    const ajaxUrl =
      window.bsc_search && window.bsc_search.ajax_url
        ? window.bsc_search.ajax_url
        : '/wp-admin/admin-ajax.php';

    const placeholderImg =
      window.bsc_search && window.bsc_search.placeholder_img
        ? window.bsc_search.placeholder_img
        : '';

    const nonce =
      window.bsc_search && window.bsc_search.nonce
        ? window.bsc_search.nonce
        : '';

    try {
      const res = await fetch(
        ajaxUrl + '?action=bsc_search_products&q=' + encodeURIComponent(query) + '&nonce=' + encodeURIComponent(nonce)
      );

      if (!res.ok) {
        throw new Error('Network error ' + res.status);
      }

      const data = await res.json();

      resultsList.innerHTML = '';

      if (!data.success || !data.data.products || data.data.products.length === 0) {
        resultsList.innerHTML = '<li class="search-empty">No se encontraron productos.</li>';
        return;
      }

      data.data.products.forEach((product) => {
        const li = document.createElement('li');
        li.classList.add('search-result-item');

        const img = document.createElement('img');
        img.alt = product.name;
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
          price.textContent = decodeHtmlEntities(product.price);
          info.appendChild(price);
        }

        li.appendChild(img);
        li.appendChild(info);

        li.addEventListener('click', () => {
          window.location.href = product.permalink;
        });

        resultsList.appendChild(li);
      });
    } catch (err) {
      resultsList.innerHTML = '<li class="search-empty">Error al buscar. Intenta de nuevo.</li>';
    }
  }

  // ── Init a search instance ────────────────────────────────────────────────
  function initSearchInstance({
    type,
    toggleBtn,
    searchContainer,
    searchInput,
    resultsList
  }) {
    if (!searchContainer || !searchInput || !resultsList) return null;

    let searchTimeout = null;

    function isThisInstanceActive() {
      return type === 'mobile' ? isMobileView() : !isMobileView();
    }

    function openSearch() {
      if (!isThisInstanceActive()) return;
      searchContainer.classList.add('visible');
      searchInput.focus();
    }

    function closeSearch() {
      searchContainer.classList.remove('visible');
    }

    function clearSearchResults() {
      resultsList.innerHTML = '';
    }

    function resetSearch() {
      closeSearch();
      searchInput.value = '';
      clearSearchResults();
    }

    if (toggleBtn) {
      toggleBtn.addEventListener('click', (e) => {
        if (!isThisInstanceActive()) return;

        e.preventDefault();
        e.stopPropagation();

        const isVisible = searchContainer.classList.contains('visible');

        if (isVisible) {
          closeSearch();
        } else {
          openSearch();
        }
      });
    }

    document.addEventListener('click', (e) => {
      if (!isThisInstanceActive()) return;

      const clickedInsideContainer = searchContainer.contains(e.target);
      const clickedToggle = toggleBtn ? toggleBtn.contains(e.target) : false;

      if (!clickedInsideContainer && !clickedToggle) {
        closeSearch();
      }
    });

    document.addEventListener('keydown', (e) => {
      if (!isThisInstanceActive()) return;

      if (e.key === 'Escape') {
        closeSearch();
      }
    });

    searchInput.addEventListener('input', () => {
      if (!isThisInstanceActive()) return;

      clearTimeout(searchTimeout);

      const query = searchInput.value.trim();
      clearSearchResults();

      if (query.length < 3) return;

      searchTimeout = setTimeout(() => {
        runSearch(query, resultsList);
      }, 350);
    });

    return {
      openSearch,
      closeSearch,
      resetSearch,
      container: searchContainer,
      input: searchInput,
      results: resultsList
    };
  }

  // ── Desktop search ────────────────────────────────────────────────────────
  const desktopSearch = initSearchInstance({
    type: 'desktop',
    toggleBtn: document.querySelector('.btn-search-toggle'),
    searchContainer: document.querySelector('.bsc__header--desktop .header__search'),
    searchInput: document.querySelector('.bsc__header--desktop .header-search-input'),
    resultsList: document.querySelector('.bsc__header--desktop .search-results')
  });

  // ── Mobile search ─────────────────────────────────────────────────────────
  const mobileSearch = initSearchInstance({
    type: 'mobile',
    toggleBtn: document.querySelector('#mobile-search-btn'),
    searchContainer: document.querySelector('.bsc-mobile-search-panel'),
    searchInput: document.querySelector('.bsc-mobile-search-panel .header-search-input'),
    resultsList: document.querySelector('.bsc-mobile-search-panel .search-results')
  });

  // ── Reset states on resize between desktop/mobile ─────────────────────────
  let lastIsMobile = isMobileView();

  window.addEventListener('resize', () => {
    const currentIsMobile = isMobileView();

    if (currentIsMobile !== lastIsMobile) {
      if (desktopSearch) desktopSearch.resetSearch();
      if (mobileSearch) mobileSearch.resetSearch();
      lastIsMobile = currentIsMobile;
    }

    updateSearchPosition();
  });

  // ── Desktop search dropdown position ──────────────────────────────────────
  function updateSearchPosition() {
    const root = document.documentElement;
    if (!root) return;

    const pageContainerWidth =
      parseInt(getComputedStyle(root).getPropertyValue('--size--page-container-w')) || 1200;

    const windowWidth = window.innerWidth;
    const rightValue =
      windowWidth > pageContainerWidth
        ? `${(windowWidth - pageContainerWidth) / 2}px`
        : '0px';

    root.style.setProperty('--header-search-right', rightValue);
  }

  window.addEventListener('load', updateSearchPosition);
  updateSearchPosition();
});



function decodeHtmlEntities(str) {
  const txt = document.createElement('textarea');
  txt.innerHTML = str;
  return txt.value;
}