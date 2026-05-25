document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('[data-bsc-skin-quiz-form]');
  const results = document.querySelector('[data-bsc-skin-quiz-bundles]');
  const status = document.querySelector('[data-bsc-skin-quiz-status]');

  if (!form || !results) {
    return;
  }

  const config = window.bscGrowthQuiz || {};
  const ajaxUrl = config.ajaxUrl || '/wp-admin/admin-ajax.php';
  const nonce = config.nonce || '';

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    setStatus('Buscando rutina...');

    const payload = new FormData(form);
    payload.set('action', 'bsc_skin_quiz_recommend');
    payload.set('nonce', nonce);

    if (config.initialRoutine) {
      payload.set('routine', config.initialRoutine);
    }

    try {
      const response = await fetch(ajaxUrl, {
        method: 'POST',
        body: payload
      });
      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(data.data && data.data.message ? data.data.message : 'No fue posible recomendar una rutina.');
      }

      renderBundles(data.data.bundles || []);
      setStatus('Rutina lista.');
    } catch (error) {
      setStatus(error.message || 'No fue posible recomendar una rutina.', true);
    }
  });

  results.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-bsc-add-bundle]');

    if (!button) {
      return;
    }

    const bundleId = button.getAttribute('data-bsc-add-bundle');

    if (!bundleId) {
      return;
    }

    button.disabled = true;
    const previousText = button.textContent;
    button.textContent = 'Agregando...';
    setStatus('');

    const payload = new FormData();
    payload.set('action', 'bsc_growth_add_bundle_to_cart');
    payload.set('nonce', nonce);
    payload.set('bundle_id', bundleId);

    try {
      const response = await fetch(ajaxUrl, {
        method: 'POST',
        body: payload
      });
      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(data.data && data.data.message ? data.data.message : 'No fue posible agregar la rutina.');
      }

      button.textContent = 'Agregada';
      setStatus('Rutina agregada al carrito.');
      updateCartCounters(data.data.cart_count);
    } catch (error) {
      button.disabled = false;
      button.textContent = previousText;
      setStatus(error.message || 'No fue posible agregar la rutina.', true);
    }
  });

  if (config.initialRoutine) {
    form.requestSubmit();
  }

  function renderBundles(bundles) {
    results.innerHTML = '';

    if (!bundles.length) {
      const empty = document.createElement('p');
      empty.className = 'bsc-skin-quiz__status';
      empty.textContent = 'No encontramos una rutina disponible para estas respuestas.';
      results.appendChild(empty);
      return;
    }

    bundles.forEach((bundle) => {
      results.appendChild(createBundleCard(bundle));
    });
  }

  function createBundleCard(bundle) {
    const article = document.createElement('article');
    article.className = 'bsc-skin-quiz__bundle';
    article.setAttribute('data-bundle-id', bundle.id || '');

    const header = document.createElement('div');
    header.className = 'bsc-skin-quiz__bundle-header';

    if (bundle.badge) {
      const badge = document.createElement('span');
      badge.className = 'bsc-skin-quiz__badge';
      badge.textContent = bundle.badge;
      header.appendChild(badge);
    }

    const title = document.createElement('h3');
    title.textContent = bundle.title || '';
    header.appendChild(title);

    const summary = document.createElement('p');
    summary.textContent = bundle.summary || '';
    header.appendChild(summary);
    article.appendChild(header);

    if (Array.isArray(bundle.products) && bundle.products.length) {
      const list = document.createElement('ul');
      list.className = 'bsc-skin-quiz__products';

      bundle.products.forEach((product) => {
        list.appendChild(createProductItem(product));
      });

      article.appendChild(list);
    }

    const actions = document.createElement('div');
    actions.className = 'bsc-skin-quiz__bundle-actions';

    const label = document.createElement('span');
    label.textContent = bundle.discount_label || '';
    actions.appendChild(label);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'bsc__button bsc-skin-quiz__bundle-button';
    button.setAttribute('data-bsc-add-bundle', bundle.id || '');
    button.disabled = !bundle.product_count;
    button.textContent = 'Agregar rutina';
    actions.appendChild(button);

    article.appendChild(actions);

    return article;
  }

  function createProductItem(product) {
    const item = document.createElement('li');
    const link = document.createElement('a');
    link.href = product.permalink || '#';

    const image = document.createElement('img');
    image.src = product.image || '';
    image.alt = product.name || '';
    link.appendChild(image);

    const name = document.createElement('span');
    name.textContent = product.name || '';
    link.appendChild(name);

    item.appendChild(link);
    return item;
  }

  function setStatus(message, isError = false) {
    if (!status) {
      return;
    }

    status.textContent = message;
    status.classList.toggle('is-error', isError);
  }

  function updateCartCounters(count) {
    if (typeof count === 'undefined') {
      return;
    }

    document.querySelectorAll('.footer__cart-count, .header__cart-count').forEach((counter) => {
      counter.textContent = String(count);
    });
  }
});
