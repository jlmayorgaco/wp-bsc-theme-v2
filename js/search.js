// BSC-038: client-side search cache (session-only, max 20 unique queries)
const searchCache = {};
const SEARCH_CACHE_MAX = 20;

document.addEventListener('DOMContentLoaded', () => {
  const MOBILE_BREAKPOINT = 1024;

  function isMobileView() {
    return window.innerWidth <= MOBILE_BREAKPOINT;
  }

  async function runSearch(query, resultsList, signal) {
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
        ajaxUrl + '?action=bsc_search_products&q=' + encodeURIComponent(query) + '&nonce=' + encodeURIComponent(nonce),
        { signal }
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
      if (err.name === 'AbortError') {
        return;
      }

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
      li.setAttribute('aria-selected', 'false');

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
    let activeIndex = -1;
    let activeController = null;
    const listId = resultsList.id || `bsc-search-results-${type}`;
    resultsList.id = listId;
    resultsList.setAttribute('role', 'listbox');
    searchInput.setAttribute('role', 'combobox');
    searchInput.setAttribute('aria-autocomplete', 'list');
    searchInput.setAttribute('aria-expanded', 'false');
    searchInput.setAttribute('aria-controls', listId);

    function isThisInstanceActive() {
      return type === 'mobile' ? isMobileView() : !isMobileView();
    }

    function openSearch() {
      if (!isThisInstanceActive()) return;
      searchContainer.classList.add('visible');
      if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
      searchInput.setAttribute('aria-expanded', 'true');
      searchInput.focus();
    }

    function closeSearch() {
      searchContainer.classList.remove('visible');
      if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
      searchInput.setAttribute('aria-expanded', 'false');
      searchInput.removeAttribute('aria-activedescendant');
      activeIndex = -1;
    }

    function clearSearchResults() {
      resultsList.innerHTML = '';
      searchInput.removeAttribute('aria-activedescendant');
      activeIndex = -1;
    }

    function resetSearch() {
      closeSearch();
      searchInput.value = '';
      clearSearchResults();
    }

    function getOptions() {
      return Array.from(resultsList.querySelectorAll('[role="option"]:not([aria-disabled="true"])'));
    }

    function setActiveOption(index) {
      const options = getOptions();

      if (!options.length) {
        activeIndex = -1;
        searchInput.removeAttribute('aria-activedescendant');
        return;
      }

      activeIndex = Math.max(0, Math.min(index, options.length - 1));

      options.forEach((option, optionIndex) => {
        const optionId = option.id || `${listId}-option-${optionIndex}`;
        option.id = optionId;
        const isActive = optionIndex === activeIndex;
        option.classList.toggle('is-active', isActive);
        option.setAttribute('aria-selected', isActive ? 'true' : 'false');

        if (isActive) {
          searchInput.setAttribute('aria-activedescendant', optionId);
          option.scrollIntoView({ block: 'nearest' });
        }
      });
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
      if (activeController) {
        activeController.abort();
      }

      const query = searchInput.value.trim();
      clearSearchResults();

      if (query.length < 2) return;

      searchTimeout = setTimeout(() => {
        activeController = new AbortController();
        runSearch(query, resultsList, activeController.signal);
      }, 400);
    });

    searchInput.addEventListener('keydown', (event) => {
      if (!isThisInstanceActive()) return;

      const options = getOptions();

      if (event.key === 'ArrowDown') {
        event.preventDefault();
        setActiveOption(activeIndex + 1);
      } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        setActiveOption(activeIndex <= 0 ? options.length - 1 : activeIndex - 1);
      } else if (event.key === 'Home' && options.length) {
        event.preventDefault();
        setActiveOption(0);
      } else if (event.key === 'End' && options.length) {
        event.preventDefault();
        setActiveOption(options.length - 1);
      } else if (event.key === 'Enter' && activeIndex >= 0 && options[activeIndex]) {
        event.preventDefault();
        options[activeIndex].click();
      }
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
  item.setAttribute('role', 'option');
  item.setAttribute('aria-disabled', 'true');
  item.textContent = message;
  resultsList.appendChild(item);
}
