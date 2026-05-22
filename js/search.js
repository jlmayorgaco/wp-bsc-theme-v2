// BSC-038: client-side search cache (session-only, max 20 unique queries)
const searchCache = {};
const SEARCH_CACHE_MAX = 20;

document.addEventListener('DOMContentLoaded', () => {
  const MOBILE_BREAKPOINT = 1024;

  function isMobileView() {
    return window.innerWidth <= MOBILE_BREAKPOINT;
  }

  async function runSearch(query, resultsList) {
    if (searchCache[query]) {
      renderResults(searchCache[query], resultsList);
      return;
    }

    setSingleResultMessage(resultsList, 'search-loading', 'Buscando...');

    const ajaxUrl =
      window.bsc_search && window.bsc_search.ajax_url
        ? window.bsc_search.ajax_url
        : '/wp-admin/admin-ajax.php';

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

      if (!data.success || !data.data.products || data.data.products.length === 0) {
        setSingleResultMessage(resultsList, 'search-empty', 'No se encontraron productos.');
        return;
      }

      const cacheKeys = Object.keys(searchCache);
      if (cacheKeys.length >= SEARCH_CACHE_MAX) {
        delete searchCache[cacheKeys[0]];
      }
      searchCache[query] = data.data.products;

      renderResults(data.data.products, resultsList);
    } catch (err) {
      setSingleResultMessage(resultsList, 'search-empty', 'Error al buscar. Intenta de nuevo.');
    }
  }

  function renderResults(products, resultsList) {
    resultsList.innerHTML = '';

    const placeholderImg =
      window.bsc_search && window.bsc_search.placeholder_img
        ? window.bsc_search.placeholder_img
        : '';

    products.forEach((product) => {
      const li = document.createElement('li');
      li.classList.add('search-result-item');
      li.tabIndex = 0;
      li.setAttribute('role', 'option');

      const img = document.createElement('img');
      img.alt = product.name || '';
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
      name.textContent = product.name || '';
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

      const goToProduct = () => {
        if (product.permalink) {
          window.location.href = product.permalink;
        }
      };

      li.addEventListener('click', goToProduct);
      li.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          goToProduct();
        }
      });

      resultsList.appendChild(li);
    });
  }

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
      if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
      searchInput.focus();
    }

    function closeSearch() {
      searchContainer.classList.remove('visible');
      if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
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

      if (query.length < 2) return;

      searchTimeout = setTimeout(() => {
        runSearch(query, resultsList);
      }, 400);
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

  const desktopSearch = initSearchInstance({
    type: 'desktop',
    toggleBtn: document.querySelector('.btn-search-toggle'),
    searchContainer: document.querySelector('.bsc__header--desktop .header__search'),
    searchInput: document.querySelector('.bsc__header--desktop .header-search-input'),
    resultsList: document.querySelector('.bsc__header--desktop .search-results')
  });

  const mobileSearch = initSearchInstance({
    type: 'mobile',
    toggleBtn: document.querySelector('#mobile-search-btn'),
    searchContainer: document.querySelector('.bsc-mobile-search-panel'),
    searchInput: document.querySelector('.bsc-mobile-search-panel .header-search-input'),
    resultsList: document.querySelector('.bsc-mobile-search-panel .search-results')
  });

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

function setSingleResultMessage(resultsList, className, message) {
  resultsList.innerHTML = '';
  const item = document.createElement('li');
  item.className = className;
  item.textContent = message;
  resultsList.appendChild(item);
}
