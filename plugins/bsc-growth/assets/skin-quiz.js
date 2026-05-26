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
  const savedRoutine = document.querySelector('[data-bsc-saved-routine]');
  const savedRoutineTitle = document.querySelector('[data-bsc-saved-routine-title]');
  const restoreRoutineButton = document.querySelector('[data-bsc-restore-routine]');
  const previewPromises = new WeakMap();
  const visionPromises = new WeakMap();
  const cameraStates = new WeakMap();
  const lastRoutineKey = 'bscSkinQuizLastRoutine';
  let updateCompare = () => {};

  if (!forms.length || !results) {
    return;
  }

  const config = window.bscGrowthQuiz || {};
  const ajaxUrl = config.ajaxUrl || '/wp-admin/admin-ajax.php';
  const nonce = config.nonce || '';

  modeTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      const mode = tab.getAttribute('data-bsc-quiz-mode-tab') || 'normal';
      setMode(mode);
      trackQuizEvent('mode_change', { mode });
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
        const state = cameraStates.get(form);
        if (state) {
          state.capturedFile = null;
        }
        resetCameraMirror(form);
        clearPhotoQuality(form);
        updateUploadLabel(form, file);
        previewPromises.set(fileInput, previewBeforeImage(file));
        visionPromises.set(fileInput, analyzeLocalPhoto(file).then((signals) => {
          setVisionSignals(form, signals);
          return signals;
        }));
        if (file) {
          trackQuizEvent('photo_selected', { source: 'upload' });
        }
      });
    }

    initCameraControls(form);
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
      trackQuizEvent('routine_add_to_cart', {
        mode: currentMode(),
        source: productButton ? 'dynamic_products' : 'bundle'
      });
    } catch (error) {
      button.disabled = false;
      button.textContent = previousText;
      setActiveStatus(error.message || 'No fue posible agregar la rutina.', true);
    }
  });

  if (restoreRoutineButton) {
    restoreRoutineButton.addEventListener('click', () => {
      const routine = getSavedRoutine();
      if (!routine || !Array.isArray(routine.bundles)) {
        return;
      }

      setMode(routine.mode === 'ai' ? 'ai' : 'normal');
      renderBundles(routine.bundles);
      renderAiVisual(routine.ai || null, routine.mode === 'ai' ? 'ai' : 'normal');
      setActiveStatus('Recomendación guardada cargada.');
      trackQuizEvent('routine_restore', { mode: routine.mode || 'normal' });
    });
  }

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
  initSavedRoutine();
  trackQuizEvent('quiz_view', { mode: initialMode });

  if (config.initialRoutine && initialMode === 'normal') {
    const normalForm = forms.find((form) => form.dataset.quizMode === 'normal');
    if (normalForm) {
      normalForm.requestSubmit();
    }
  }

  async function runRecommendation(form) {
    const mode = form.dataset.quizMode || 'normal';
    const statusText = mode === 'ai' ? 'Analizando tu foto...' : 'Preparando tu rutina...';
    setStatus(form, statusText);
    trackQuizEvent('quiz_submit', { mode });

    if (mode === 'ai') {
      await ensureAiPreview(form);
      const quality = getPhotoQuality(form);
      if (quality && !quality.ok) {
        setStatus(form, quality.message, true);
        trackQuizEvent('photo_quality_blocked', { reason: quality.reason || 'quality' });
        return;
      }
    }

    const payload = new FormData(form);
    payload.set('action', mode === 'ai' ? 'bsc_skin_quiz_ai_recommend' : 'bsc_skin_quiz_recommend');
    payload.set('nonce', nonce);

    if (mode === 'ai') {
      const capturedFile = getCapturedFile(form);
      if (capturedFile) {
        payload.set('skin_photo', capturedFile, capturedFile.name || 'bsc-skin-quiz-selfie.jpg');
      }
    }

    if (config.initialRoutine && mode === 'normal') {
      payload.set('routine', config.initialRoutine);
    }

    try {
      const data = await postForm(payload);

      renderBundles(data.bundles || []);
      renderAiVisual(data.ai || null, mode);
      saveRoutine({
        mode,
        bundles: data.bundles || [],
        ai: data.ai || null,
        createdAt: new Date().toISOString()
      });
      trackQuizEvent('quiz_result', {
        mode,
        fallback: data.ai && data.ai.fallback ? '1' : '0'
      });
      const fallbackLabel = data.ai && data.ai.fallback ? 'Rutina lista. Lectura AI pendiente.' : 'Rutina personalizada lista.';
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
      throw new Error(data.data && data.data.message ? data.data.message : 'No fue posible completar la acción.');
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
        resetDiagnosis('Foto lista. Completa las respuestas y crea tu rutina.');
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

    label.textContent = file ? file.name : 'Galería o archivo';
  }

  function initCameraControls(form) {
    const startButton = form.querySelector('[data-bsc-camera-start]');
    const shotButton = form.querySelector('[data-bsc-camera-shot]');
    const stopButton = form.querySelector('[data-bsc-camera-stop]');
    const video = form.querySelector('[data-bsc-camera-video]');
    const canvas = form.querySelector('[data-bsc-camera-canvas]');
    const empty = form.querySelector('[data-bsc-camera-empty]');
    const shell = form.querySelector('[data-bsc-camera-shell]');

    if (!startButton || !shotButton || !stopButton || !video || !canvas) {
      return;
    }

    const state = {
      stream: null,
      capturedFile: null
    };
    cameraStates.set(form, state);

    startButton.addEventListener('click', async () => {
      try {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
          throw new Error('Tu navegador no permite abrir la cámara desde aquí.');
        }

        state.stream = await navigator.mediaDevices.getUserMedia({
          video: {
            facingMode: 'user',
            width: { ideal: 1280 },
            height: { ideal: 960 }
          },
          audio: false
        });
        video.srcObject = state.stream;
        await video.play();
        const fileInput = form.querySelector('[data-bsc-skin-photo]');
        if (fileInput) {
          fileInput.value = '';
        }
        state.capturedFile = null;
        if (shell) {
          shell.classList.add('is-camera-active');
          shell.classList.remove('has-photo-preview');
        }
        video.classList.remove('is-hidden');
        canvas.classList.add('is-hidden');
        if (empty) {
          empty.classList.add('is-hidden');
        }
        startButton.classList.add('is-hidden');
        shotButton.classList.remove('is-hidden');
        stopButton.classList.remove('is-hidden');
        clearPhotoQuality(form);
        trackQuizEvent('camera_started', { mode: 'ai' });
      } catch (error) {
        setStatus(form, error.message || 'No fue posible abrir la cámara.', true);
      }
    });

    shotButton.addEventListener('click', async () => {
      if (!state.stream || !video.videoWidth || !video.videoHeight) {
        return;
      }

      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      const context = canvas.getContext('2d');
      if (!context) {
        return;
      }

      context.translate(canvas.width, 0);
      context.scale(-1, 1);
      context.drawImage(video, 0, 0, canvas.width, canvas.height);
      context.setTransform(1, 0, 0, 1, 0, 0);

      const file = await canvasToFile(canvas, 'bsc-skin-quiz-selfie.jpg');
      if (!file) {
        setStatus(form, 'No fue posible guardar la foto de la cámara.', true);
        return;
      }
      state.capturedFile = file;
      if (shell) {
        shell.classList.add('has-photo-preview');
      }
      canvas.classList.remove('is-hidden');
      video.classList.add('is-hidden');
      stopCamera(form);
      updateUploadLabel(form, file);
      previewPromises.set(form, previewBeforeImage(file));
      visionPromises.set(form, analyzeLocalPhoto(file).then((signals) => {
        setVisionSignals(form, signals);
        return signals;
      }));
      trackQuizEvent('camera_captured', { mode: 'ai' });
    });

    stopButton.addEventListener('click', () => {
      stopCamera(form);
      if (!state.capturedFile && empty) {
        empty.classList.remove('is-hidden');
      }
    });
  }

  function stopCamera(form) {
    const state = cameraStates.get(form);
    const startButton = form.querySelector('[data-bsc-camera-start]');
    const shotButton = form.querySelector('[data-bsc-camera-shot]');
    const stopButton = form.querySelector('[data-bsc-camera-stop]');
    const video = form.querySelector('[data-bsc-camera-video]');
    const shell = form.querySelector('[data-bsc-camera-shell]');

    if (state && state.stream) {
      state.stream.getTracks().forEach((track) => track.stop());
      state.stream = null;
    }

    if (video) {
      video.pause();
      video.srcObject = null;
      video.classList.add('is-hidden');
    }

    if (shell) {
      shell.classList.remove('is-camera-active');
    }

    if (startButton) {
      startButton.classList.remove('is-hidden');
    }
    if (shotButton) {
      shotButton.classList.add('is-hidden');
    }
    if (stopButton) {
      stopButton.classList.add('is-hidden');
    }
  }

  function resetCameraMirror(form) {
    const canvas = form.querySelector('[data-bsc-camera-canvas]');
    const empty = form.querySelector('[data-bsc-camera-empty]');
    const shell = form.querySelector('[data-bsc-camera-shell]');

    stopCamera(form);

    if (canvas) {
      canvas.classList.add('is-hidden');
    }
    if (empty) {
      empty.classList.remove('is-hidden');
    }
    if (shell) {
      shell.classList.remove('is-camera-active', 'has-photo-preview');
    }
  }

  function canvasToFile(canvas, name) {
    return new Promise((resolve) => {
      canvas.toBlob((blob) => {
        if (!blob) {
          resolve(null);
          return;
        }
        resolve(new File([blob], name, { type: 'image/jpeg' }));
      }, 'image/jpeg', 0.9);
    });
  }

  function getCapturedFile(form) {
    const state = cameraStates.get(form);
    return state && state.capturedFile ? state.capturedFile : null;
  }

  async function ensureAiPreview(form) {
    const fileInput = form.querySelector('[data-bsc-skin-photo]');
    const capturedFile = getCapturedFile(form);

    if (capturedFile) {
      const pendingPreview = previewPromises.get(form) || previewBeforeImage(capturedFile);
      const pendingVision = visionPromises.get(form) || analyzeLocalPhoto(capturedFile).then((signals) => {
        setVisionSignals(form, signals);
        return signals;
      });
      previewPromises.set(form, pendingPreview);
      visionPromises.set(form, pendingVision);
      await Promise.all([pendingPreview, pendingVision]);
      return;
    }

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
    updatePhotoQuality(form, signals || {});
  }

  function updatePhotoQuality(form, signals) {
    const quality = assessPhotoQuality(signals);
    form.dataset.bscPhotoQualityOk = quality.ok ? '1' : '0';
    form.dataset.bscPhotoQualityMessage = quality.message || '';
    form.dataset.bscPhotoQualityReason = quality.reason || '';

    const warning = form.querySelector('[data-bsc-photo-quality]');
    if (!warning) {
      return;
    }

    warning.textContent = quality.ok ? '' : quality.message;
    warning.classList.toggle('is-visible', !quality.ok);
  }

  function clearPhotoQuality(form) {
    form.dataset.bscPhotoQualityOk = '';
    form.dataset.bscPhotoQualityMessage = '';
    form.dataset.bscPhotoQualityReason = '';

    const warning = form.querySelector('[data-bsc-photo-quality]');
    if (warning) {
      warning.textContent = '';
      warning.classList.remove('is-visible');
    }
  }

  function getPhotoQuality(form) {
    if (!form.dataset.bscPhotoQualityOk) {
      return null;
    }

    return {
      ok: form.dataset.bscPhotoQualityOk === '1',
      message: form.dataset.bscPhotoQualityMessage || 'La foto no permite leer bien la piel. Toma otra con más luz, de frente y sin sombras fuertes.',
      reason: form.dataset.bscPhotoQualityReason || 'quality'
    };
  }

  function assessPhotoQuality(signals) {
    const flags = Array.isArray(signals.quality_flags) ? signals.quality_flags : [];
    const faceDetection = signals.face_detection || 'unsupported';
    const faceCount = Number(signals.face_count || 0);
    const skinRatio = Number(signals.skin_pixel_ratio || 0);

    if (Number(signals.width || 0) < 180 || Number(signals.height || 0) < 180) {
      return {
        ok: false,
        reason: 'small_image',
        message: 'La foto está muy pequeña para una asesoría clara. Usa una imagen más grande o toma otra foto.'
      };
    }

    if (faceDetection === 'supported' && faceCount < 1) {
      return {
        ok: false,
        reason: 'no_face',
        message: 'No logro ubicar el rostro. Toma la foto de frente, con buena luz y sin cubrir la cara.'
      };
    }

    if (faceDetection === 'supported' && faceCount > 1) {
      return {
        ok: false,
        reason: 'multiple_faces',
        message: 'La foto debe tener solo un rostro para que la recomendación sea personal.'
      };
    }

    if (faceDetection === 'supported' && Number(signals.face_area_ratio || 0) < 0.08) {
      return {
        ok: false,
        reason: 'face_too_small',
        message: 'El rostro queda muy lejos. Acércate un poco y vuelve a tomar la foto.'
      };
    }

    if (faceDetection === 'supported' && Number(signals.face_center_score || 1) < 0.45) {
      return {
        ok: false,
        reason: 'off_center',
        message: 'Centra mejor el rostro para poder leer mejillas, frente y zona T.'
      };
    }

    if (flags.includes('low_light')) {
      return {
        ok: false,
        reason: 'low_light',
        message: 'La foto está muy oscura. Busca luz natural de frente y vuelve a tomarla.'
      };
    }

    if (flags.includes('overexposed')) {
      return {
        ok: false,
        reason: 'overexposed',
        message: 'La foto está muy iluminada y quema detalles de la piel. Baja la luz directa e intenta de nuevo.'
      };
    }

    if (flags.includes('skin_area_unclear') && skinRatio < 0.03) {
      return {
        ok: false,
        reason: 'skin_area_unclear',
        message: 'No se ve suficiente piel del rostro. Toma una foto frontal, sin filtros ni sombras fuertes.'
      };
    }

    return {
      ok: true,
      reason: '',
      message: ''
    };
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
        image.addEventListener('load', async () => {
          try {
            resolve(await readImageSignals(image));
          } catch (error) {
            resolve({});
          }
        });
        image.addEventListener('error', () => resolve({}));
        image.src = String(reader.result || '');
      });
      reader.addEventListener('error', () => resolve({}));
      reader.readAsDataURL(file);
    });
  }

  async function readImageSignals(image) {
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
    if (textureSignal < 0.018 && Math.sqrt(variance) < 0.08) {
      qualityFlags.push('soft_or_blurry');
    }
    if (!useSkinSample) {
      qualityFlags.push('skin_area_unclear');
    }

    const faceSignals = await detectFaceSignals(canvas, size);

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
      quality_flags: qualityFlags,
      ...faceSignals
    };
  }

  async function detectFaceSignals(canvas, size) {
    if (!('FaceDetector' in window)) {
      return {
        face_detection: 'unsupported',
        face_count: 0,
        face_area_ratio: 0,
        face_center_score: 0
      };
    }

    try {
      const detector = new window.FaceDetector({
        fastMode: true,
        maxDetectedFaces: 2
      });
      const faces = await detector.detect(canvas);
      const firstFace = faces && faces[0] ? faces[0].boundingBox : null;

      if (!firstFace) {
        return {
          face_detection: 'supported',
          face_count: 0,
          face_area_ratio: 0,
          face_center_score: 0
        };
      }

      const faceCenterX = firstFace.x + (firstFace.width / 2);
      const faceCenterY = firstFace.y + (firstFace.height / 2);
      const centerDistance = Math.hypot((faceCenterX / size) - 0.5, (faceCenterY / size) - 0.5);
      const centerScore = Math.max(0, 1 - (centerDistance * 2));

      return {
        face_detection: 'supported',
        face_count: faces.length,
        face_area_ratio: roundSignal((firstFace.width * firstFace.height) / (size * size)),
        face_center_score: roundSignal(centerScore)
      };
    } catch (error) {
      return {
        face_detection: 'failed',
        face_count: 0,
        face_area_ratio: 0,
        face_center_score: 0
      };
    }
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
      diagnosisSkinType.textContent = 'Lista para analizar';
    }

    if (diagnosisConfidence) {
      diagnosisConfidence.textContent = '';
      diagnosisConfidence.style.width = '';
    }

    if (diagnosisNeeds) {
      diagnosisNeeds.innerHTML = '';
    }

    setAiNotes(message || 'Tu lectura cosmética aparecerá aquí después de analizar la foto.');
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
      notes.push('Cruzamos la foto, tus respuestas y el catálogo BSC para armar esta rutina.');
    }

    setAiNotes(notes.join(' '));
  }

  function labelFor(value) {
    const labels = {
      acne: 'Brotes / acné',
      barrera: 'Barrera',
      glow: 'Glow',
      grasa: 'Grasa',
      hidratacion: 'Hidratación',
      manchas: 'Manchas',
      mixta: 'Mixta',
      normal: 'Normal',
      'protector-solar': 'Protector solar',
      seca: 'Seca',
      sensible: 'Sensible'
    };

    return labels[value] || String(value || '').replace(/-/g, ' ');
  }

  function initSavedRoutine() {
    const routine = getSavedRoutine();

    if (!routine || !Array.isArray(routine.bundles) || !routine.bundles.length || !savedRoutine) {
      return;
    }

    const firstBundle = routine.bundles[0] || {};
    if (savedRoutineTitle) {
      savedRoutineTitle.textContent = firstBundle.title || 'Recupera tu recomendación guardada';
    }

    savedRoutine.classList.remove('is-hidden');
  }

  function getSavedRoutine() {
    if (config.savedRoutine && Array.isArray(config.savedRoutine.bundles)) {
      return config.savedRoutine;
    }

    try {
      const raw = window.localStorage ? window.localStorage.getItem(lastRoutineKey) : '';
      const routine = raw ? JSON.parse(raw) : null;
      return routine && Array.isArray(routine.bundles) ? routine : null;
    } catch (error) {
      return null;
    }
  }

  function saveRoutine(routine) {
    if (!routine || !Array.isArray(routine.bundles) || !routine.bundles.length || !window.localStorage) {
      return;
    }

    const stored = {
      mode: routine.mode || 'normal',
      bundles: routine.bundles,
      ai: routine.ai || null,
      createdAt: routine.createdAt || new Date().toISOString()
    };

    try {
      window.localStorage.setItem(lastRoutineKey, JSON.stringify(stored));
      if (savedRoutine && savedRoutineTitle) {
        savedRoutineTitle.textContent = stored.bundles[0] && stored.bundles[0].title ? stored.bundles[0].title : 'Recupera tu recomendación guardada';
        savedRoutine.classList.remove('is-hidden');
      }
    } catch (error) {
      // Storage can fail in private browsing; the server still saves for logged-in users.
    }
  }

  function trackQuizEvent(eventName, meta = {}) {
    if (!nonce) {
      return;
    }

    const payload = new FormData();
    payload.set('action', 'bsc_skin_quiz_track');
    payload.set('nonce', nonce);
    payload.set('event', eventName);
    payload.set('mode', meta.mode || currentMode());
    payload.set('meta', JSON.stringify(meta));

    fetch(ajaxUrl, {
      method: 'POST',
      body: payload
    }).catch(() => {});
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
