document.addEventListener('DOMContentLoaded', () => {
  const toggleBtn = document.querySelector('.btn-search-toggle');
  const searchContainer = document.querySelector('.header__search');
  const searchInput = document.querySelector('.header-search-input');
  const resultsList = document.querySelector('.search-results');

  toggleBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    searchContainer.classList.toggle('visible');
    searchInput.focus();
  });

  // Opcional: cerrar cuando haces clic fuera
  document.addEventListener('click', (e) => {
    if (!searchContainer.contains(e.target) && !toggleBtn.contains(e.target)) {
      searchContainer.classList.remove('visible');
    }
  });

 searchInput.addEventListener('input', async () => {
  const query = searchInput.value.trim();
  resultsList.innerHTML = ''; // Limpiar

  if (query.length < 3) return;

  const response = await fetch(`/wp-json/wc/store/products?search=${encodeURIComponent(query)}`);
  const data = await response.json();

  if (data.length === 0) {
    resultsList.innerHTML = '<li>No se encontraron productos.</li>';
    return;
  }

  data.forEach(product => {
    const li = document.createElement('li');
    li.classList.add('search-result-item');

    const img = document.createElement('img');
    img.src = product.images?.[0]?.src || 'http://bsc2.local/wp-content/uploads/woocommerce-placeholder-150x150.png'; // fallback to empty if no image
    img.alt = product.name;
    img.classList.add('search-result-image'); // Optional for styling

    const span = document.createElement('span');
    span.textContent = product.name;

    li.appendChild(img);
    li.appendChild(span);

    li.addEventListener('click', () => {
      window.location.href = product.permalink;
    });

    resultsList.appendChild(li);
  });
});

});


function updateSearchPosition() {

  const root = document.documentElement;
  const pageContainerWidth = parseInt(getComputedStyle(root).getPropertyValue('--size--page-container-w')) || 1200;
  const windowWidth = window.innerWidth;

  const rightValue = (windowWidth > pageContainerWidth)
    ? `${(windowWidth - pageContainerWidth) / 2}px`
    : '0px';

  root.style.setProperty('--header-search-right', rightValue);
}

// Ejecutar al cargar y al redimensionar
window.addEventListener('load', updateSearchPosition);
window.addEventListener('resize', updateSearchPosition);