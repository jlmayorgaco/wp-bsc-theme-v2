document.addEventListener('DOMContentLoaded', () => {
  const root = document.querySelector('.bsc-skin-quiz');
  const layout = document.querySelector('.bsc-skin-quiz__layout');
  const forms = Array.from(document.querySelectorAll('[data-bsc-skin-quiz-form]'));
  const results = document.querySelector('[data-bsc-skin-quiz-bundles]');
  const modeTabs = Array.from(document.querySelectorAll('[data-bsc-quiz-mode-tab]'));
  const compare = document.querySelector('[data-bsc-ai-compare]');
  const diagnosis = document.querySelector('[data-bsc-ai-diagnosis]');
  const diagnosisSkinType = document.querySelector('[data-bsc-ai-skin-type]');
  const diagnosisNeeds = document.querySelector('[data-bsc-ai-needs]');
  const diagnosisConfidence = document.querySelector('[data-bsc-ai-confidence]');
  const beforeImage = document.querySelector('[data-bsc-before-image]');
  const afterImage = document.querySelector('[data-bsc-after-image]');
  const afterWrap = document.querySelector('[data-bsc-after-wrap]');
  const compareRange = document.querySelector('[data-bsc-compare-range]');
  const aiNotes = document.querySelector('[data-bsc-ai-notes]');
  const previewPromises = new WeakMap();
  let updateCompare = () => {};

  if (!forms.length || !results) {
    return;
  }

  const config = window.bscGrowthQuiz || {};
  const ajaxUrl = config.ajaxUrl || '/wp-admin/admin-ajax.php';
  const nonce = config.nonce || '';

  modeTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      setMode(tab.getAttribute('data-bsc-quiz-mode-tab') || 'normal');
    });
  });

  forms.forEach((form) => {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      runRecommendation(form);
    });

    const fileInput = form.querySelector('[data-bsc-skin-photo]');
    if (fileInput) {
      fileInput.addEventListener('change', () => {
        const file = fileInput.files && fileInput.files[0];
        updateUploadLabel(form, file);
        previewPromises.set(fileInput, previewBeforeImage(file));
      });
    }
  });

  results.addEventListener('click', async (event) => {
    const productButton = event.target.closest('[data-bsc-add-products]');
    const bundleButton = event.target.closest('[data-bsc-add-bundle]');
    const button = productButton || bundleButton;

    if (!button) {
      return;
    }

    button.disabled = true;
    const previousText = button.textContent;
    button.textContent = 'Agregando...';
    setActiveStatus('');

    const payload = new FormData();
    payload.set('nonce', nonce);

    if (productButton) {
      payload.set('action', 'bsc_growth_add_products_to_cart');
      payload.set('product_ids', productButton.getAttribute('data-bsc-add-products') || '');
    } else {
      payload.set('action', 'bsc_growth_add_bundle_to_cart');
      payload.set('bundle_id', bundleButton.getAttribute('data-bsc-add-bundle') || '');
    }

    try {
      const data = await postForm(payload);

      button.textContent = 'Agregada';
      setActiveStatus('Rutina agregada al carrito.');
      updateCartCounters(data.cart_count);
    } catch (error) {
      button.disabled = false;
      button.textContent = previousText;
      setActiveStatus(error.message || 'No fue posible agregar la rutina.', true);
    }
  });

  if (compareRange && afterWrap) {
    updateCompare = () => {
      if (compare) {
        compare.style.setProperty('--bsc-compare-split', `${compareRange.value}%`);
      }
      afterWrap.style.setProperty('--bsc-compare-split', `${compareRange.value}%`);
      const frame = compare && compare.querySelector('.bsc-skin-quiz__compare-frame');
      if (frame && afterImage) {
        afterImage.style.width = `${frame.getBoundingClientRect().width}px`;
      }
    };
    compareRange.addEventListener('input', updateCompare);
    window.addEventListener('resize', updateCompare);
    updateCompare();
  }

  const initialMode = config.initialMode === 'ai' ? 'ai' : 'normal';
  setMode(initialMode);

  if (config.initialRoutine && initialMode === 'normal') {
    const normalForm = forms.find((form) => form.dataset.quizMode === 'normal');
    if (normalForm) {
      normalForm.requestSubmit();
    }
  }

  async function runRecommendation(form) {
    const mode = form.dataset.quizMode || 'normal';
    const statusText = mode === 'ai' ? 'Analizando foto con Gemini...' : 'Buscando rutina...';
    setStatus(form, statusText);

    const payload = new FormData(form);
    payload.set('action', mode === 'ai' ? 'bsc_skin_quiz_ai_recommend' : 'bsc_skin_quiz_recommend');
    payload.set('nonce', nonce);

    if (mode === 'ai') {
      await ensureAiPreview(form);
    }

    if (config.initialRoutine && mode === 'normal') {
      payload.set('routine', config.initialRoutine);
    }

    try {
      const data = await postForm(payload);

      renderBundles(data.bundles || []);
      renderAiVisual(data.ai || null, mode);
      const fallbackLabel = data.ai && data.ai.fallback ? 'Rutina lista. AI pendiente.' : 'Rutina AI lista.';
      setStatus(form, mode === 'ai' ? fallbackLabel : 'Rutina lista.');
    } catch (error) {
      setStatus(form, error.message || 'No fue posible recomendar una rutina.', true);
    }
  }

  async function postForm(payload) {
    const response = await fetch(ajaxUrl, {
      method: 'POST',
      body: payload
    });
    const data = await response.json();

    if (!response.ok || !data.success) {
      throw new Error(data.data && data.data.message ? data.data.message : 'No fue posible completar la accion.');
    }

    return data.data || {};
  }

  function setMode(mode) {
    if (root) {
      root.classList.toggle('is-ai-mode', mode === 'ai');
    }

    if (layout) {
      layout.classList.toggle('is-ai-mode', mode === 'ai');
    }

    forms.forEach((form) => {
      const isActive = form.dataset.quizMode === mode;
      form.classList.toggle('is-hidden', !isActive);
    });

    modeTabs.forEach((tab) => {
      const isActive = tab.getAttribute('data-bsc-quiz-mode-tab') === mode;
      tab.classList.toggle('is-active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    if (compare) {
      compare.classList.toggle('is-visible', mode === 'ai');
    }

    if (diagnosis) {
      diagnosis.classList.toggle('is-visible', mode === 'ai');
    }

    window.requestAnimationFrame(updateCompare);
  }

  function renderBundles(bundles) {
    results.innerHTML = '';
    results.classList.toggle('has-ai-recommendation', currentMode() === 'ai');

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

    if (Array.isArray(bundle.steps) && bundle.steps.length) {
      article.appendChild(createSteps(bundle.steps));
    }

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
    button.disabled = !bundle.product_count;
    button.textContent = 'Agregar rutina';

    if (Array.isArray(bundle.product_ids) && bundle.product_ids.length) {
      button.setAttribute('data-bsc-add-products', bundle.product_ids.join(','));
    } else {
      button.setAttribute('data-bsc-add-bundle', bundle.id || '');
    }

    actions.appendChild(button);
    article.appendChild(actions);

    return article;
  }

  function currentMode() {
    const activeForm = forms.find((form) => !form.classList.contains('is-hidden'));
    return activeForm ? activeForm.dataset.quizMode || 'normal' : 'normal';
  }

  function createSteps(steps) {
    const list = document.createElement('ol');
    list.className = 'bsc-skin-quiz__steps';

    steps.forEach((step) => {
      const item = document.createElement('li');
      const label = document.createElement('strong');
      const why = document.createElement('span');

      label.textContent = step.label || '';
      why.textContent = step.why || '';
      item.appendChild(label);
      item.appendChild(why);
      list.appendChild(item);
    });

    return list;
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

  function previewBeforeImage(file) {
    return new Promise((resolve) => {
      if (!file || !beforeImage || !afterImage || !compare) {
        resolve('');
        return;
      }

      const reader = new FileReader();
      reader.addEventListener('load', () => {
        const src = String(reader.result || '');
        beforeImage.src = src;
        afterImage.src = src;
        afterImage.classList.add('is-fallback');
        compare.classList.remove('is-empty');
        compare.classList.add('is-visible');
        resetDiagnosis('Foto lista para Gemini. Completa las respuestas y analiza la rutina.');
        window.requestAnimationFrame(updateCompare);
        resolve(src);
      });
      reader.addEventListener('error', () => {
        resolve('');
      });
      reader.readAsDataURL(file);
    });
  }

  function updateUploadLabel(form, file) {
    const label = form.querySelector('[data-bsc-upload-file]');

    if (!label) {
      return;
    }

    label.textContent = file ? file.name : 'JPG, PNG o WebP hasta 4MB';
  }

  async function ensureAiPreview(form) {
    const fileInput = form.querySelector('[data-bsc-skin-photo]');

    if (!fileInput || !fileInput.files || !fileInput.files[0]) {
      return;
    }

    const pendingPreview = previewPromises.get(fileInput) || previewBeforeImage(fileInput.files[0]);
    previewPromises.set(fileInput, pendingPreview);
    await pendingPreview;
  }

  function renderAiVisual(ai, mode) {
    if (mode !== 'ai' || !compare) {
      return;
    }

    compare.classList.add('is-visible');

    if (ai && ai.after_image_data_uri && afterImage) {
      afterImage.src = ai.after_image_data_uri;
      afterImage.classList.remove('is-fallback');
      afterImage.addEventListener('load', updateCompare, { once: true });
    } else if (afterImage) {
      afterImage.classList.add('is-fallback');
    }

    renderDiagnosis(ai);
    window.requestAnimationFrame(updateCompare);
  }

  function setStatus(form, message, isError = false) {
    const status = form.querySelector('[data-bsc-skin-quiz-status]');

    if (!status) {
      return;
    }

    status.textContent = message;
    status.classList.toggle('is-error', isError);
  }

  function setActiveStatus(message, isError = false) {
    const activeForm = forms.find((form) => !form.classList.contains('is-hidden')) || forms[0];
    setStatus(activeForm, message, isError);
  }

  function setAiNotes(message) {
    if (aiNotes) {
      aiNotes.textContent = message;
    }
  }

  function resetDiagnosis(message = '') {
    if (diagnosis) {
      diagnosis.classList.add('is-empty');
    }

    if (diagnosisSkinType) {
      diagnosisSkinType.textContent = 'Pendiente de analisis';
    }

    if (diagnosisConfidence) {
      diagnosisConfidence.textContent = '';
      diagnosisConfidence.style.width = '';
    }

    if (diagnosisNeeds) {
      diagnosisNeeds.innerHTML = '';
    }

    setAiNotes(message || 'La lectura cosmetica aparecera aqui despues de analizar la foto.');
  }

  function renderDiagnosis(ai) {
    if (!diagnosis) {
      return;
    }

    const profile = ai && ai.skin_profile ? ai.skin_profile : {};
    const needs = Array.isArray(profile.needs) ? profile.needs : [];
    const confidence = Number(profile.confidence || 0);
    const readableSkinType = profile.skin_type ? labelFor(profile.skin_type) : 'Rutina sugerida';
    const notes = [];

    diagnosis.classList.remove('is-empty');

    if (diagnosisSkinType) {
      diagnosisSkinType.textContent = readableSkinType;
    }

    if (diagnosisConfidence) {
      const percent = Math.max(0, Math.min(100, Math.round(confidence * 100)));
      diagnosisConfidence.textContent = percent > 0 ? `${percent}% confianza` : '';
      diagnosisConfidence.style.width = percent > 0 ? `${percent}%` : '';
    }

    if (diagnosisNeeds) {
      diagnosisNeeds.innerHTML = '';
      needs.forEach((need) => {
        const item = document.createElement('li');
        item.textContent = labelFor(need);
        diagnosisNeeds.appendChild(item);
      });
    }

    if (ai && ai.after_description) {
      notes.push(ai.after_description);
    }

    if (ai && Array.isArray(ai.notes) && ai.notes.length) {
      notes.push(ai.notes.join(' '));
    }

    if (!notes.length && needs.length) {
      notes.push('Gemini cruzo la foto, tus respuestas y el catalogo BSC para armar esta rutina.');
    }

    setAiNotes(notes.join(' '));
  }

  function labelFor(value) {
    const labels = {
      acne: 'Brotes',
      barrera: 'Barrera',
      glow: 'Glow',
      grasa: 'Grasa',
      hidratacion: 'Hidratacion',
      manchas: 'Manchas',
      mixta: 'Mixta',
      normal: 'Normal',
      'protector-solar': 'Protector solar',
      seca: 'Seca',
      sensible: 'Sensible'
    };

    return labels[value] || String(value || '').replace(/-/g, ' ');
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
