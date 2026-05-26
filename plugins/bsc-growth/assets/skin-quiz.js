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
  const visionPromises = new WeakMap();
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
        visionPromises.set(fileInput, analyzeLocalPhoto(file).then((signals) => {
          setVisionSignals(form, signals);
          return signals;
        }));
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
        if (afterWrap) {
          afterWrap.classList.add('is-fallback');
        }
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
    const pendingVision = visionPromises.get(fileInput) || analyzeLocalPhoto(fileInput.files[0]).then((signals) => {
      setVisionSignals(form, signals);
      return signals;
    });
    previewPromises.set(fileInput, pendingPreview);
    visionPromises.set(fileInput, pendingVision);
    await Promise.all([pendingPreview, pendingVision]);
  }

  function setVisionSignals(form, signals) {
    const input = form.querySelector('[data-bsc-vision-signals]');

    if (!input) {
      return;
    }

    input.value = JSON.stringify(signals || {});
  }

  function analyzeLocalPhoto(file) {
    return new Promise((resolve) => {
      if (!file || !window.FileReader || !window.Image || !document.createElement('canvas').getContext) {
        resolve({});
        return;
      }

      const reader = new FileReader();
      reader.addEventListener('load', () => {
        const image = new Image();
        image.addEventListener('load', () => {
          resolve(readImageSignals(image));
        });
        image.addEventListener('error', () => resolve({}));
        image.src = String(reader.result || '');
      });
      reader.addEventListener('error', () => resolve({}));
      reader.readAsDataURL(file);
    });
  }

  function readImageSignals(image) {
    const size = 96;
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d', { willReadFrequently: true });

    if (!context) {
      return {};
    }

    canvas.width = size;
    canvas.height = size;

    const scale = Math.max(size / image.naturalWidth, size / image.naturalHeight);
    const width = image.naturalWidth * scale;
    const height = image.naturalHeight * scale;
    const x = (size - width) / 2;
    const y = (size - height) / 2;
    context.drawImage(image, x, y, width, height);

    const pixels = context.getImageData(0, 0, size, size).data;
    const samples = [];
    const lumas = [];
    let skinCount = 0;
    let brightness = 0;
    let saturation = 0;

    for (let index = 0; index < pixels.length; index += 4) {
      const r = pixels[index] / 255;
      const g = pixels[index + 1] / 255;
      const b = pixels[index + 2] / 255;
      const max = Math.max(r, g, b);
      const min = Math.min(r, g, b);
      const sat = max === 0 ? 0 : (max - min) / max;
      const luma = (0.2126 * r) + (0.7152 * g) + (0.0722 * b);
      const skin = isLikelySkinPixel(r, g, b, luma, sat);

      samples.push({ r, g, b, luma, sat, skin });
      lumas.push(luma);
      if (skin) {
        skinCount += 1;
        brightness += luma;
        saturation += sat;
      }
    }

    const count = lumas.length || 1;
    const skinRatio = skinCount / count;
    const useSkinSample = skinRatio >= 0.08;
    const analysisSamples = useSkinSample ? samples.filter((sample) => sample.skin) : samples;
    const analysisCount = analysisSamples.length || 1;

    if (!useSkinSample) {
      brightness = samples.reduce((sum, sample) => sum + sample.luma, 0);
      saturation = samples.reduce((sum, sample) => sum + sample.sat, 0);
    }

    const mean = brightness / analysisCount;
    const variance = analysisSamples.reduce((sum, sample) => sum + ((sample.luma - mean) ** 2), 0) / analysisCount;
    let shine = 0;
    let redness = 0;
    let darkSpots = 0;
    let texture = 0;
    let texturePairs = 0;

    analysisSamples.forEach((sample) => {
      if (sample.luma > Math.min(0.9, mean + 0.22) && sample.sat < 0.34) {
        shine += 1;
      }

      if (sample.r > sample.g * 1.12 && sample.r > sample.b * 1.18 && sample.sat > 0.2) {
        redness += 1;
      }

      if (sample.luma < mean - 0.16 && sample.sat > 0.14) {
        darkSpots += 1;
      }
    });

    for (let row = 1; row < size; row += 1) {
      for (let col = 1; col < size; col += 1) {
        const currentIndex = (row * size) + col;
        const leftIndex = currentIndex - 1;
        const topIndex = ((row - 1) * size) + col;
        const current = samples[currentIndex];
        const left = samples[leftIndex];
        const top = samples[topIndex];

        if (!useSkinSample || (current.skin && left.skin)) {
          texture += Math.abs(current.luma - left.luma);
          texturePairs += 1;
        }

        if (!useSkinSample || (current.skin && top.skin)) {
          texture += Math.abs(current.luma - top.luma);
          texturePairs += 1;
        }
      }
    }

    const textureSignal = texturePairs ? texture / texturePairs : 0;
    const qualityFlags = [];
    if (mean < 0.28) {
      qualityFlags.push('low_light');
    }
    if (mean > 0.82) {
      qualityFlags.push('overexposed');
    }
    if (Math.sqrt(variance) < 0.11) {
      qualityFlags.push('low_contrast');
    }
    if (textureSignal < 0.045) {
      qualityFlags.push('soft_or_blurry');
    }
    if (!useSkinSample) {
      qualityFlags.push('skin_area_unclear');
    }

    return {
      width: image.naturalWidth,
      height: image.naturalHeight,
      brightness: roundSignal(mean),
      contrast: roundSignal(Math.min(1, Math.sqrt(variance) * 2.5)),
      saturation: roundSignal(saturation / analysisCount),
      skin_pixel_ratio: roundSignal(skinRatio),
      shine_signal: roundSignal(shine / analysisCount),
      redness_signal: roundSignal(redness / analysisCount),
      dark_spot_signal: roundSignal(darkSpots / analysisCount),
      texture_signal: roundSignal(Math.min(1, textureSignal)),
      sharpness: roundSignal(Math.min(1, textureSignal / 1.2)),
      quality_flags: qualityFlags
    };
  }

  function isLikelySkinPixel(r, g, b, luma, saturation) {
    const red = r * 255;
    const green = g * 255;
    const blue = b * 255;
    const cb = 128 - (0.168736 * red) - (0.331264 * green) + (0.5 * blue);
    const cr = 128 + (0.5 * red) - (0.418688 * green) - (0.081312 * blue);
    const ycbcrSkin = cb >= 70 && cb <= 150 && cr >= 125 && cr <= 190;
    const rgbSkin = r > b * 0.75 && g > b * 0.45 && saturation > 0.05 && saturation < 0.72;

    return luma > 0.12 && luma < 0.95 && (ycbcrSkin || rgbSkin);
  }

  function roundSignal(value) {
    return Math.round(Math.max(0, Math.min(1, Number(value) || 0)) * 10000) / 10000;
  }

  function renderAiVisual(ai, mode) {
    if (mode !== 'ai' || !compare) {
      return;
    }

    compare.classList.add('is-visible');

    if (ai && ai.after_image_data_uri && afterImage) {
      afterImage.src = ai.after_image_data_uri;
      afterImage.classList.remove('is-fallback');
      if (afterWrap) {
        afterWrap.classList.remove('is-fallback');
      }
      afterImage.addEventListener('load', updateCompare, { once: true });
    } else if (afterImage) {
      afterImage.classList.add('is-fallback');
      if (afterWrap) {
        afterWrap.classList.add('is-fallback');
      }
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
