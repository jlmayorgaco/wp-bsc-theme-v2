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
  const diagnosisBars = document.querySelector('[data-bsc-ai-diagnostic-bars]');
  const diagnosisSummary = document.querySelector('[data-bsc-ai-summary]');
  const diagnosisFitScore = document.querySelector('[data-bsc-ai-fit-score]');
  const diagnosisPrimaryFocus = document.querySelector('[data-bsc-ai-primary-focus]');
  const diagnosisBaseNote = document.querySelector('[data-bsc-ai-base-note]');
  const beforeImage = document.querySelector('[data-bsc-before-image]');
  const afterImage = document.querySelector('[data-bsc-after-image]');
  const afterWrap = document.querySelector('[data-bsc-after-wrap]');
  const webglCanvas = document.querySelector('[data-bsc-webgl-preview]');
  const compareRange = document.querySelector('[data-bsc-compare-range]');
  const analysisOverlay = document.querySelector('[data-bsc-ai-analysis]');
  const analysisStage = document.querySelector('[data-bsc-ai-analysis-stage]');
  const analysisText = document.querySelector('[data-bsc-ai-analysis-text]');
  const analysisProgress = document.querySelector('[data-bsc-ai-analysis-progress]');
  const analysisProgressBar = analysisOverlay ? analysisOverlay.querySelector('.bsc-skin-quiz__analysis-progress') : null;
  const analysisSteps = Array.from(document.querySelectorAll('[data-bsc-ai-analysis-steps] span'));
  const aiNotes = document.querySelector('[data-bsc-ai-notes]');
  const photoSignalItems = Array.from(document.querySelectorAll('[data-bsc-photo-signal]'));
  const savedRoutine = document.querySelector('[data-bsc-saved-routine]');
  const savedRoutineTitle = document.querySelector('[data-bsc-saved-routine-title]');
  const restoreRoutineButton = document.querySelector('[data-bsc-restore-routine]');
  const previewPromises = new WeakMap();
  const visionPromises = new WeakMap();
  const cameraStates = new WeakMap();
  const lastRoutineKey = 'bscSkinQuizLastRoutine';
  const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let skinPreviewRenderer = null;
  let lastVisionSignals = {};
  let webglRenderTicket = 0;
  let aiAnalysisTimer = 0;
  let aiAnalysisHideTimer = 0;
  let aiAnalysisProgress = 0;
  let aiLoaderTimer = 0;
  let aiLoaderHideTimer = 0;
  let aiLoaderStartedAt = 0;
  let currentRunId = 0;
  let quizVariant = 'control';
  let afterIntensityMode = 'natural';
  let modeTransitionTimer = 0;
  let updateCompare = () => {};
  const config = window.bscGrowthQuiz || {};
  const ajaxUrl = config.ajaxUrl || '/wp-admin/admin-ajax.php';
  const nonce = config.nonce || '';
  const checkoutUrl = config.checkoutUrl || config.cartUrl || '/checkout/';
  const currentEmail = config.currentEmail || '';
  quizVariant = initQuizVariant();

  if (!forms.length || !results) {
    initAccountDeletion();
    return;
  }

  const analysisStages = [
    { max: 22, stage: 'Mapeando rostro', text: 'Detectando luz, sombras y encuadre' },
    { max: 42, stage: 'Leyendo piel', text: 'Separando brillo, textura y tono visible' },
    { max: 62, stage: 'Máscara cosmética', text: 'Diferenciando manchas, sombras y rojeces' },
    { max: 82, stage: 'Catálogo BSC', text: 'Cruzando señales con productos compatibles' },
    { max: 101, stage: 'Rutina final', text: 'Generando rutina y simulación visual' }
  ];
  const aiLoaderDuration = 60000;
  const aiLoaderPhrases = [
    { at: 0, phrase: 'Preparando tu lectura', detail: 'Validando foto, luz y respuestas.' },
    { at: 8000, phrase: 'Leyendo senales visibles', detail: 'Revisando textura, brillo y tono.' },
    { at: 18000, phrase: 'Cruzando con productos BSC', detail: 'Buscando rutina compatible con tu piel.' },
    { at: 32000, phrase: 'Ajustando pasos AM y PM', detail: 'Ordenando productos y frecuencia de uso.' },
    { at: 46000, phrase: 'Afinando el diagnostico', detail: 'Gemini esta cerrando prioridades y carrito.' },
    { at: 58000, phrase: 'Ya casi esta lista', detail: 'Si tarda un poco mas, seguimos esperando la respuesta.' }
  ];
  const diagnosisScoreConfig = [
    { key: 'sebum_balance', label: 'Control de brillo', hint: 'Zona T / sebo' },
    { key: 'hydration_barrier', label: 'Hidratación y barrera', hint: 'Confort / soporte' },
    { key: 'tone_evenness', label: 'Tono uniforme', hint: 'Manchas visibles' },
    { key: 'calmness', label: 'Calma', hint: 'Rojeces / brotes' },
    { key: 'texture_refinement', label: 'Textura', hint: 'Poros / suavidad' },
    { key: 'spf_priority', label: 'Prioridad SPF', hint: 'Protección diaria' }
  ];

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

    initCameraControls(form);
    initPhotoCaptureUx(form);

    const fileInput = form.querySelector('[data-bsc-skin-photo]');
    if (fileInput) {
      fileInput.addEventListener('change', () => {
        const file = fileInput.files && fileInput.files[0];
        handlePhotoFile(form, file, 'upload', fileInput);
        if (file) {
          trackQuizEvent('photo_selected', { source: 'upload' });
        }
      });
    }
  });

  results.addEventListener('click', async (event) => {
    const emailToggle = event.target.closest('[data-bsc-email-routine]');
    if (emailToggle) {
      const article = emailToggle.closest('[data-bundle-id]');
      const form = article && article.querySelector('[data-bsc-email-form]');
      if (form) {
        form.classList.toggle('is-hidden');
        const input = form.querySelector('input[type="email"]');
        const submit = form.querySelector('[data-bsc-email-submit]');
        if (input) {
          input.focus();
        }
        if (submit) {
          submit.setAttribute('data-share-token', emailToggle.getAttribute('data-share-token') || '');
        }
      }
      return;
    }

    const emailSubmit = event.target.closest('[data-bsc-email-submit]');
    if (emailSubmit) {
      event.preventDefault();
      await sendRoutineEmail(emailSubmit);
      return;
    }

    const shareLink = event.target.closest('[data-bsc-whatsapp-routine]');
    if (shareLink) {
      trackQuizEvent('routine_share', { channel: 'whatsapp', run_id: shareLink.getAttribute('data-run-id') || currentRunId });
      return;
    }

    const repeatButton = event.target.closest('[data-bsc-repeat-quiz]');
    if (repeatButton) {
      window.location.assign(`${window.location.origin}/skin-quiz/?mode=ai`);
      return;
    }

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
    payload.set('run_id', button.getAttribute('data-run-id') || currentRunId || '');

    try {
      const data = await postForm(payload);

      button.textContent = 'Agregada';
      setActiveStatus('Rutina agregada al carrito.');
      updateCartCounters(data.cart_count);
      trackQuizEvent('routine_add_to_cart', {
        mode: currentMode(),
        source: productButton ? 'dynamic_products' : 'bundle',
        run_id: button.getAttribute('data-run-id') || currentRunId
      });
      button.textContent = 'Redirigiendo...';
      window.location.assign(data.checkout_url || checkoutUrl);
    } catch (error) {
      button.disabled = false;
      button.textContent = previousText;
      setActiveStatus(error.message || 'No fue posible agregar la rutina.', true);
    }
  });

  results.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-bsc-email-form]');
    if (!form) {
      return;
    }

    event.preventDefault();
    const submit = form.querySelector('[data-bsc-email-submit]');
    if (submit) {
      await sendRoutineEmail(submit);
    }
  });

  initAccountDeletion();

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
        const frameWidth = `${frame.getBoundingClientRect().width}px`;
        afterImage.style.width = frameWidth;
        if (webglCanvas) {
          webglCanvas.style.width = frameWidth;
        }
      }
    };
    compareRange.addEventListener('input', updateCompare);
    window.addEventListener('resize', updateCompare);
    updateCompare();
    initAfterIntensityControls();
  }

  const initialMode = config.initialMode === 'ai' ? 'ai' : 'normal';
  setMode(initialMode);
  initSavedRoutine();
  initSharedRoutine();
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
    const submitButton = form.querySelector('.bsc-skin-quiz__submit');
    let analysisStarted = false;
    let loaderStarted = false;

    form.classList.add('is-submitting');
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.setAttribute('aria-busy', 'true');
    }

    setStatus(form, statusText);
    trackQuizEvent('quiz_submit', { mode });

    try {
      if (mode === 'ai') {
        await ensureAiPreview(form);
        const quality = getPhotoQuality(form);
        if (quality && !quality.ok) {
          setStatus(form, quality.message, true);
          trackQuizEvent('photo_quality_blocked', { reason: quality.reason || 'quality' });
          return;
        }

        startAiAnalysis();
        startAiLoader(form);
        analysisStarted = true;
        loaderStarted = true;
        setStatus(form, 'Skincare AI está procesando la foto y armando tu rutina...');
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

      const data = await postForm(payload);
      const firstBundle = Array.isArray(data.bundles) && data.bundles.length ? data.bundles[0] : null;
      currentRunId = Number((data.ai && data.ai.run_id) || (firstBundle && firstBundle.run_id) || 0);

      renderBundles(data.bundles || []);
      renderAiVisual(data.ai || null, mode);
      saveRoutine({
        mode,
        bundles: data.bundles || [],
        ai: data.ai || null,
        run_id: currentRunId,
        createdAt: new Date().toISOString()
      });
      trackQuizEvent('quiz_result', {
        mode,
        fallback: data.ai && data.ai.fallback ? '1' : '0',
        run_id: currentRunId
      });
      const fallbackLabel = data.ai && data.ai.fallback ? 'Rutina lista. Lectura AI pendiente.' : 'Rutina personalizada lista.';
      if (loaderStarted) {
        finishAiLoader(form, true);
      }
      setStatus(form, mode === 'ai' ? fallbackLabel : 'Rutina lista.');
      if (analysisStarted) {
        finishAiAnalysis(true);
      }
    } catch (error) {
      if (analysisStarted) {
        finishAiAnalysis(false);
      }
      if (mode === 'ai') {
        resetDiagnosis('No fue posible completar el análisis AI. Intenta de nuevo con la misma foto.');
      }
      if (loaderStarted) {
        finishAiLoader(form, false);
      }
      setStatus(form, error.message || 'No fue posible recomendar una rutina.', true);
    } finally {
      form.classList.remove('is-submitting');
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.removeAttribute('aria-busy');
      }
    }
  }

  function startAiLoader(form) {
    const loader = form.querySelector('[data-bsc-ai-loader]');

    if (!loader) {
      return;
    }

    window.clearInterval(aiLoaderTimer);
    window.clearTimeout(aiLoaderHideTimer);
    aiLoaderStartedAt = Date.now();
    loader.classList.remove('is-complete', 'is-error');
    loader.classList.add('is-visible');
    loader.setAttribute('aria-hidden', 'false');
    updateAiLoader(form, 0);

    if (reduceMotion) {
      updateAiLoader(form, 42);
      return;
    }

    aiLoaderTimer = window.setInterval(() => {
      updateAiLoader(form);
    }, 500);
  }

  function finishAiLoader(form, success) {
    const loader = form.querySelector('[data-bsc-ai-loader]');

    window.clearInterval(aiLoaderTimer);
    window.clearTimeout(aiLoaderHideTimer);
    aiLoaderTimer = 0;

    if (!loader) {
      return;
    }

    updateAiLoader(form, success ? 100 : Math.max(8, currentAiLoaderPercent(form)));
    loader.classList.toggle('is-complete', Boolean(success));
    loader.classList.toggle('is-error', !success);

    const phrase = loader.querySelector('[data-bsc-ai-loader-phrase]');
    const detail = loader.querySelector('[data-bsc-ai-loader-detail]');
    if (phrase) {
      phrase.textContent = success ? 'Rutina lista' : 'No se completo la lectura';
    }
    if (detail) {
      detail.textContent = success ? 'Ya puedes revisar diagnostico y productos.' : 'Puedes intentarlo de nuevo con la misma foto.';
    }

    aiLoaderHideTimer = window.setTimeout(() => {
      loader.classList.remove('is-visible', 'is-complete', 'is-error');
      loader.setAttribute('aria-hidden', 'true');
      aiLoaderHideTimer = 0;
    }, success && !reduceMotion ? 650 : 220);
  }

  function updateAiLoader(form, forcedPercent = null) {
    const loader = form.querySelector('[data-bsc-ai-loader]');

    if (!loader) {
      return;
    }

    const elapsed = Math.max(0, Date.now() - aiLoaderStartedAt);
    const percent = forcedPercent === null
      ? Math.min(96, Math.round((elapsed / aiLoaderDuration) * 96))
      : Math.max(0, Math.min(100, Math.round(forcedPercent)));
    const stage = aiLoaderPhrases.reduce((current, item) => (elapsed >= item.at ? item : current), aiLoaderPhrases[0]);
    const phrase = loader.querySelector('[data-bsc-ai-loader-phrase]');
    const detail = loader.querySelector('[data-bsc-ai-loader-detail]');
    const progress = loader.querySelector('[data-bsc-ai-loader-progress]');
    const progressBar = loader.querySelector('[data-bsc-ai-loader-progress-bar]');
    const time = loader.querySelector('[data-bsc-ai-loader-time]');
    const steps = Array.from(loader.querySelectorAll('[data-bsc-ai-loader-step]'));

    loader.style.setProperty('--bsc-ai-loader-progress', `${percent}%`);

    if (phrase && forcedPercent !== 100) {
      phrase.textContent = stage.phrase;
    }

    if (detail && forcedPercent !== 100) {
      detail.textContent = stage.detail;
    }

    if (progress) {
      progress.style.width = `${percent}%`;
    }

    if (progressBar) {
      progressBar.setAttribute('aria-valuenow', String(percent));
    }

    if (time) {
      const seconds = Math.min(99, Math.floor(elapsed / 1000));
      time.textContent = `0:${String(seconds).padStart(2, '0')}`;
      time.setAttribute('datetime', `PT${seconds}S`);
    }

    if (steps.length) {
      const activeCount = Math.max(1, Math.ceil((percent / 100) * steps.length));
      steps.forEach((step, index) => {
        step.classList.toggle('is-active', index < activeCount);
      });
    }
  }

  function currentAiLoaderPercent(form) {
    const loader = form.querySelector('[data-bsc-ai-loader]');
    const value = loader ? getComputedStyle(loader).getPropertyValue('--bsc-ai-loader-progress') : '';
    return Number.parseFloat(value) || 0;
  }

  function startAiAnalysis() {
    if (!analysisOverlay || !compare) {
      return;
    }

    window.clearInterval(aiAnalysisTimer);
    window.clearTimeout(aiAnalysisHideTimer);
    aiAnalysisProgress = 7;
    compare.classList.add('is-analyzing');
    compare.classList.remove('is-analysis-complete');
    analysisOverlay.setAttribute('aria-hidden', 'false');
    setSkinPreviewAnalysis(true, aiAnalysisProgress / 100);
    setAiAnalysisProgress(aiAnalysisProgress);

    if (diagnosis) {
      diagnosis.classList.add('is-loading');
      diagnosis.classList.remove('is-empty');
    }

    if (diagnosisSkinType) {
      diagnosisSkinType.textContent = 'Analizando a fondo';
    }

    if (diagnosisConfidence) {
      diagnosisConfidence.textContent = '';
      diagnosisConfidence.style.width = '18%';
    }

    if (diagnosisNeeds) {
      diagnosisNeeds.innerHTML = '';
    }

    setAiNotes('La lectura cosmética está cruzando foto, señales locales y catálogo BSC.');

    if (reduceMotion) {
      setAiAnalysisProgress(82);
      return;
    }

    const startedAt = Date.now();
    aiAnalysisTimer = window.setInterval(() => {
      const elapsed = Date.now() - startedAt;
      const softTarget = Math.min(92, 14 + (Math.log1p(elapsed / 520) * 17));
      const next = aiAnalysisProgress + ((softTarget - aiAnalysisProgress) * 0.22) + 0.16;
      setAiAnalysisProgress(Math.min(92, next));
    }, 260);
  }

  function setAiAnalysisProgress(value) {
    aiAnalysisProgress = Math.max(0, Math.min(100, Number(value) || 0));
    const percent = Math.round(aiAnalysisProgress);
    const stage = analysisStages.find((item) => aiAnalysisProgress <= item.max) || analysisStages[analysisStages.length - 1];

    if (analysisOverlay) {
      analysisOverlay.style.setProperty('--bsc-ai-progress', `${percent}%`);
    }

    setSkinPreviewAnalysis(aiAnalysisProgress > 0 && compare && compare.classList.contains('is-analyzing'), aiAnalysisProgress / 100);

    if (analysisProgress) {
      analysisProgress.style.width = `${percent}%`;
    }

    if (analysisProgressBar) {
      analysisProgressBar.setAttribute('aria-valuenow', String(percent));
    }

    if (analysisStage && stage) {
      analysisStage.textContent = stage.stage;
    }

    if (analysisText && stage) {
      analysisText.textContent = stage.text;
    }

    if (analysisSteps.length) {
      const activeCount = Math.ceil((percent / 100) * analysisSteps.length);
      analysisSteps.forEach((item, index) => {
        item.classList.toggle('is-active', index < activeCount);
      });
    }
  }

  function finishAiAnalysis(success) {
    window.clearInterval(aiAnalysisTimer);
    window.clearTimeout(aiAnalysisHideTimer);
    aiAnalysisTimer = 0;
    aiAnalysisHideTimer = 0;

    if (!analysisOverlay || !compare) {
      return;
    }

    if (success) {
      setAiAnalysisProgress(100);
      compare.classList.add('is-analysis-complete');
    }

    const hideDelay = success && !reduceMotion ? 520 : 0;

    aiAnalysisHideTimer = window.setTimeout(() => {
      compare.classList.remove('is-analyzing', 'is-analysis-complete');
      analysisOverlay.setAttribute('aria-hidden', 'true');
      setSkinPreviewAnalysis(false, success ? 1 : 0);

      if (!success) {
        setAiAnalysisProgress(0);
      }

      if (diagnosis) {
        diagnosis.classList.remove('is-loading');
      }
    }, hideDelay);
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

  function stripHtml(value) {
    const tmp = document.createElement('span');
    tmp.innerHTML = sanitizeHtml(String(value || ''));
    return tmp.textContent.trim();
  }

  function sanitizeHtml(value) {
    const template = document.createElement('template');
    template.innerHTML = String(value || '');
    template.content.querySelectorAll('script, style, iframe, object').forEach((node) => node.remove());
    template.content.querySelectorAll('*').forEach((node) => {
      Array.from(node.attributes).forEach((attribute) => {
        if (/^on/i.test(attribute.name)) {
          node.removeAttribute(attribute.name);
        }
      });
    });
    return template.innerHTML;
  }

  function setMode(mode) {
    if (mode !== 'ai') {
      finishAiAnalysis(false);
    }

    if (root) {
      root.classList.toggle('is-ai-mode', mode === 'ai');
      root.setAttribute('data-bsc-current-mode', mode);
      root.classList.add('is-switching-mode');
      window.clearTimeout(modeTransitionTimer);
      modeTransitionTimer = window.setTimeout(() => {
        root.classList.remove('is-switching-mode');
      }, reduceMotion ? 0 : 260);
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

    bundles.forEach((bundle, index) => {
      const card = createBundleCard(bundle);
      card.style.setProperty('--bsc-card-enter-delay', `${index * 64}ms`);
      card.classList.add('is-entering');
      results.appendChild(card);
      window.requestAnimationFrame(() => {
        card.classList.add('is-entered');
      });
    });
  }

  function createBundleCard(bundle) {
    const badgeText = String(bundle.badge || '');
    const isAiRoutine = currentMode() === 'ai' || badgeText.toLowerCase().includes('ai');
    const article = document.createElement('article');
    article.className = isAiRoutine ? 'bsc-skin-quiz__bundle bsc-skin-quiz__bundle--ai' : 'bsc-skin-quiz__bundle';
    article.setAttribute('data-bundle-id', bundle.id || '');
    if (bundle.run_id) {
      article.setAttribute('data-run-id', bundle.run_id);
    }

    const header = document.createElement('div');
    header.className = 'bsc-skin-quiz__bundle-header';

    if (bundle.badge || (isAiRoutine && bundle.product_count)) {
      const meta = document.createElement('div');
      meta.className = 'bsc-skin-quiz__bundle-meta';

      const badge = document.createElement('span');
      badge.className = 'bsc-skin-quiz__badge';
      badge.textContent = bundle.badge || 'Rutina BSC';
      meta.appendChild(badge);

      if (isAiRoutine && bundle.product_count) {
        const count = document.createElement('span');
        count.className = 'bsc-skin-quiz__bundle-count';
        count.textContent = `${bundle.product_count} productos`;
        meta.appendChild(count);
      }

      if (bundle.total_html) {
        const total = document.createElement('span');
        total.className = 'bsc-skin-quiz__bundle-total';
        total.innerHTML = sanitizeHtml(bundle.total_html);
        meta.appendChild(total);
      }

      header.appendChild(meta);
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
      const productsPanel = document.createElement('div');
      productsPanel.className = 'bsc-skin-quiz__products-panel';

      if (isAiRoutine) {
        const productsHeading = document.createElement('div');
        productsHeading.className = 'bsc-skin-quiz__products-heading';

        const productsTitle = document.createElement('strong');
        productsTitle.textContent = 'Productos recomendados';
        productsHeading.appendChild(productsTitle);

        const productsNote = document.createElement('span');
        productsNote.textContent = 'Listos para tu carrito';
        productsHeading.appendChild(productsNote);

        productsPanel.appendChild(productsHeading);
      }

      const list = document.createElement('ul');
      list.className = 'bsc-skin-quiz__products';

      bundle.products.forEach((product) => {
        list.appendChild(createProductItem(product));
      });

      productsPanel.appendChild(list);
      article.appendChild(productsPanel);
    }

    const actions = document.createElement('div');
    actions.className = 'bsc-skin-quiz__bundle-actions';

    const actionLabel = bundle.discount_label || (isAiRoutine ? 'Agrega la rutina completa en un clic' : '');
    if (actionLabel) {
      const label = document.createElement('span');
      label.textContent = actionLabel;
      actions.appendChild(label);
    }

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'bsc__button bsc-skin-quiz__bundle-button';
    button.disabled = !bundle.product_count;

    if (isAiRoutine) {
      const icon = document.createElement('i');
      icon.className = 'fas fa-cart-plus';
      icon.setAttribute('aria-hidden', 'true');
      button.appendChild(icon);
    }

    const buttonLabel = document.createElement('span');
    const hasTotal = Number(bundle.total_raw || 0) > 0;
    buttonLabel.textContent = hasTotal && bundle.total_html ? `Agregar rutina - ${stripHtml(bundle.total_html)}` : 'Agregar rutina';
    button.appendChild(buttonLabel);

    if (Array.isArray(bundle.product_ids) && bundle.product_ids.length) {
      button.setAttribute('data-bsc-add-products', bundle.product_ids.join(','));
    } else {
      button.setAttribute('data-bsc-add-bundle', bundle.id || '');
    }
    if (bundle.run_id) {
      button.setAttribute('data-run-id', bundle.run_id);
    }

    actions.appendChild(button);
    if (bundle.run_id) {
      actions.appendChild(createShareActions(bundle));
    }
    article.appendChild(actions);

    return article;
  }

  function createShareActions(bundle) {
    const wrap = document.createElement('div');
    wrap.className = 'bsc-skin-quiz__share-actions';

    const emailButton = document.createElement('button');
    emailButton.type = 'button';
    emailButton.className = 'bsc-skin-quiz__secondary-button';
    emailButton.setAttribute('data-bsc-email-routine', '1');
    emailButton.setAttribute('data-run-id', bundle.run_id || '');
    emailButton.setAttribute('data-share-token', bundle.share_token || '');
    emailButton.textContent = 'Enviarme rutina';
    wrap.appendChild(emailButton);

    if (bundle.whatsapp_url) {
      const whatsapp = document.createElement('a');
      whatsapp.className = 'bsc-skin-quiz__secondary-link';
      whatsapp.href = bundle.whatsapp_url;
      whatsapp.target = '_blank';
      whatsapp.rel = 'noreferrer';
      whatsapp.setAttribute('data-bsc-whatsapp-routine', '1');
      whatsapp.setAttribute('data-run-id', bundle.run_id || '');
      whatsapp.textContent = 'Compartir por WhatsApp';
      wrap.appendChild(whatsapp);
    }

    const repeat = document.createElement('button');
    repeat.type = 'button';
    repeat.className = 'bsc-skin-quiz__ghost-button';
    repeat.setAttribute('data-bsc-repeat-quiz', '1');
    repeat.textContent = 'Repetir quiz';
    wrap.appendChild(repeat);

    const form = document.createElement('form');
    form.className = 'bsc-skin-quiz__email-form is-hidden';
    form.setAttribute('data-bsc-email-form', '1');

    const input = document.createElement('input');
    input.type = 'email';
    input.name = 'email';
    input.required = true;
    input.placeholder = 'tu@email.com';
    input.value = currentEmail || '';
    form.appendChild(input);

    const send = document.createElement('button');
    send.type = 'submit';
    send.className = 'bsc__button bsc-skin-quiz__email-submit';
    send.setAttribute('data-bsc-email-submit', '1');
    send.setAttribute('data-run-id', bundle.run_id || '');
    send.setAttribute('data-share-token', bundle.share_token || '');
    send.textContent = 'Enviar';
    form.appendChild(send);
    wrap.appendChild(form);

    return wrap;
  }

  async function sendRoutineEmail(button) {
    const form = button.closest('[data-bsc-email-form]');
    const email = form && form.querySelector('input[type="email"]');
    const runId = button.getAttribute('data-run-id') || currentRunId;
    const shareToken = button.getAttribute('data-share-token') || '';

    if (!form || !email || !email.value) {
      return;
    }

    button.disabled = true;
    const previousText = button.textContent;
    button.textContent = 'Enviando...';

    try {
      const payload = new FormData();
      payload.set('action', 'bsc_skin_quiz_send_routine');
      payload.set('nonce', nonce);
      payload.set('run_id', runId);
      payload.set('share_token', shareToken);
      payload.set('email', email.value);
      const data = await postForm(payload);
      setActiveStatus(data.message || 'Rutina enviada a tu correo.');
      trackQuizEvent('routine_send', { channel: 'email', run_id: runId });
      form.classList.add('is-hidden');
    } catch (error) {
      setActiveStatus(error.message || 'No fue posible enviar la rutina.', true);
    } finally {
      button.disabled = false;
      button.textContent = previousText;
    }
  }

  function currentMode() {
    const activeForm = forms.find((form) => !form.classList.contains('is-hidden'));
    return activeForm ? activeForm.dataset.quizMode || 'normal' : 'normal';
  }

  function createSteps(steps) {
    const list = document.createElement('div');
    list.className = 'bsc-skin-quiz__steps';
    const labels = {
      manana: 'Mañana',
      noche: 'Noche',
      semanal: '2-3 veces por semana'
    };

    steps.forEach((step) => {
      const item = document.createElement('article');
      item.className = 'bsc-skin-quiz__step';
      const meta = document.createElement('span');
      const label = document.createElement('strong');
      const amount = document.createElement('em');
      const why = document.createElement('span');
      const warning = document.createElement('small');

      meta.textContent = labels[step.time_of_day] || step.frequency || 'Rutina';
      label.textContent = step.label || '';
      amount.textContent = [step.amount, step.frequency].filter(Boolean).join(' · ');
      why.textContent = step.why || '';
      warning.textContent = step.warning || '';
      item.append(meta, label);
      if (amount.textContent) {
        item.appendChild(amount);
      }
      if (why.textContent) {
        item.appendChild(why);
      }
      if (warning.textContent) {
        item.appendChild(warning);
      }
      list.appendChild(item);
    });

    return list;
  }

  function createProductItem(product) {
    const item = document.createElement('li');
    item.className = 'bsc-skin-quiz__product';
    if (product.stock_status === 'outofstock' || product.is_addable === false) {
      item.classList.add('is-unavailable');
    }
    const link = document.createElement('a');
    link.href = product.permalink || '#';

    if (product.image) {
      const image = document.createElement('img');
      image.src = product.image;
      image.alt = product.name || '';
      link.appendChild(image);
    } else {
      const placeholder = document.createElement('span');
      placeholder.className = 'bsc-skin-quiz__product-placeholder';
      placeholder.setAttribute('aria-hidden', 'true');
      const icon = document.createElement('i');
      icon.className = 'fas fa-spa';
      placeholder.appendChild(icon);
      link.appendChild(placeholder);
    }

    const name = document.createElement('span');
    name.textContent = product.name || '';
    link.appendChild(name);

    if (product.brand) {
      const brand = document.createElement('small');
      brand.textContent = product.brand;
      link.appendChild(brand);
    }

    if (product.replacement_label) {
      const replacement = document.createElement('small');
      replacement.className = 'bsc-skin-quiz__replacement-label';
      replacement.textContent = product.replacement_label;
      link.appendChild(replacement);
    }

    const meta = document.createElement('em');
    meta.className = 'bsc-skin-quiz__product-meta';
    meta.textContent = [stripHtml(product.price_html || product.price || ''), product.stock_label || ''].filter(Boolean).join(' · ');
    if (meta.textContent) {
      link.appendChild(meta);
    }

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
        renderSkinPreview(src, '', lastVisionSignals);
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

    label.textContent = file ? file.name : 'Arrastra aquí o elige una foto';
  }

  function handlePhotoFile(form, file, source = 'upload', fileInput = null, options = {}) {
    const state = cameraStates.get(form);
    const useFormFile = source !== 'upload';
    const previewKey = useFormFile ? form : fileInput;

    if (state) {
      state.capturedFile = useFormFile && file ? file : null;
    }

    if (!options.keepMirror) {
      resetCameraMirror(form);
    }

    clearPhotoQuality(form);
    updateUploadLabel(form, file);
    updateCaptureState(form, file, source);

    if (!file) {
      return;
    }

    const previewPromise = previewBeforeImage(file);
    const visionPromise = analyzeLocalPhoto(file).then((signals) => {
      setVisionSignals(form, signals);
      return signals;
    });

    if (previewKey) {
      previewPromises.set(previewKey, previewPromise);
      visionPromises.set(previewKey, visionPromise);
    }
  }

  function updateCaptureState(form, file, source = '') {
    const shell = form.querySelector('[data-bsc-camera-shell]');

    if (!shell) {
      return;
    }

    shell.classList.toggle('has-photo-ready', Boolean(file));
    shell.classList.remove('is-dragging');

    if (file) {
      shell.setAttribute('data-bsc-photo-source', source);
      return;
    }

    shell.removeAttribute('data-bsc-photo-source');
  }

  function initPhotoCaptureUx(form) {
    const shell = form.querySelector('[data-bsc-camera-shell]');
    const uploadDropzone = form.querySelector('[data-bsc-upload-dropzone]');
    const fileInput = form.querySelector('[data-bsc-skin-photo]');
    const uploadCanvas = form.querySelector('[data-bsc-upload-canvas]');

    if (uploadCanvas) {
      startUploadCanvas(uploadCanvas);
    }

    if (shell) {
      shell.addEventListener('pointermove', (event) => {
        const rect = shell.getBoundingClientRect();
        if (!rect.width || !rect.height) {
          return;
        }
        shell.style.setProperty('--bsc-capture-x', `${Math.round(((event.clientX - rect.left) / rect.width) * 100)}%`);
        shell.style.setProperty('--bsc-capture-y', `${Math.round(((event.clientY - rect.top) / rect.height) * 100)}%`);
      });
    }

    if (!uploadDropzone || !fileInput || !shell) {
      return;
    }

    ['dragenter', 'dragover'].forEach((eventName) => {
      uploadDropzone.addEventListener(eventName, (event) => {
        event.preventDefault();
        shell.classList.add('is-dragging');
      });
    });

    ['dragleave', 'dragend'].forEach((eventName) => {
      uploadDropzone.addEventListener(eventName, () => {
        shell.classList.remove('is-dragging');
      });
    });

    uploadDropzone.addEventListener('drop', (event) => {
      event.preventDefault();
      shell.classList.remove('is-dragging');

      const files = Array.from((event.dataTransfer && event.dataTransfer.files) || []);
      const file = files.find((item) => /^image\/(jpeg|png|webp)$/i.test(item.type));

      if (!file) {
        setStatus(form, 'Sube una imagen JPG, PNG o WebP.', true);
        return;
      }

      try {
        const transfer = new DataTransfer();
        transfer.items.add(file);
        fileInput.files = transfer.files;
      } catch (error) {
        // Some browsers do not allow programmatic file assignment. The file is still sent as captured data.
      }

      handlePhotoFile(form, file, 'drop', fileInput);
      trackQuizEvent('photo_selected', { source: 'drop' });
    });
  }

  function startUploadCanvas(canvas) {
    const context = canvas.getContext('2d');

    if (!context) {
      return;
    }

    const resize = () => {
      const rect = canvas.getBoundingClientRect();
      const ratio = Math.min(window.devicePixelRatio || 1, 2);
      const width = Math.max(1, Math.round(rect.width * ratio));
      const height = Math.max(1, Math.round(rect.height * ratio));

      if (canvas.width !== width || canvas.height !== height) {
        canvas.width = width;
        canvas.height = height;
      }

      context.setTransform(ratio, 0, 0, ratio, 0, 0);
    };

    const draw = (time = 0) => {
      const rect = canvas.getBoundingClientRect();
      const width = rect.width || 1;
      const height = rect.height || 1;
      const pulse = reduceMotion ? 0.5 : (Math.sin(time / 1200) + 1) / 2;
      const scan = reduceMotion ? 0.56 : ((time / 2600) % 1);

      resize();
      context.clearRect(0, 0, width, height);

      const gradient = context.createLinearGradient(0, 0, width, height);
      gradient.addColorStop(0, 'rgba(255, 255, 255, 0.95)');
      gradient.addColorStop(0.48, 'rgba(255, 244, 247, 0.9)');
      gradient.addColorStop(1, 'rgba(238, 241, 240, 0.88)');
      context.fillStyle = gradient;
      context.fillRect(0, 0, width, height);

      context.save();
      context.translate(width * 0.5, height * 0.5);
      const ringBase = Math.min(width, height) * 0.2;
      const ringGap = Math.max(10, Math.min(width, height) * 0.055);
      context.strokeStyle = `rgba(31, 31, 31, ${0.1 + (pulse * 0.05)})`;
      context.lineWidth = 1;
      for (let index = 0; index < 4; index += 1) {
        context.beginPath();
        context.arc(0, 0, ringBase + (index * ringGap), 0, Math.PI * 2);
        context.stroke();
      }

      context.strokeStyle = 'rgba(247, 192, 205, 0.64)';
      context.lineWidth = 2;
      context.beginPath();
      context.arc(0, 0, ringBase + (ringGap * 0.45), -0.7, Math.PI * 1.2);
      context.stroke();

      context.fillStyle = 'rgba(31, 31, 31, 0.26)';
      [
        [-0.18, -0.12],
        [0.18, -0.12],
        [0, 0.18]
      ].forEach(([x, y], index) => {
        context.beginPath();
        context.arc(ringBase * x, ringBase * y, index === 2 ? 2.2 : 2.7, 0, Math.PI * 2);
        context.fill();
      });
      context.restore();

      const scanX = width * scan;
      const scanGradient = context.createLinearGradient(scanX - 32, 0, scanX + 32, 0);
      scanGradient.addColorStop(0, 'rgba(247, 192, 205, 0)');
      scanGradient.addColorStop(0.5, 'rgba(247, 192, 205, 0.45)');
      scanGradient.addColorStop(1, 'rgba(247, 192, 205, 0)');
      context.fillStyle = scanGradient;
      context.fillRect(scanX - 32, 0, 64, height);

      context.fillStyle = 'rgba(31, 31, 31, 0.08)';
      for (let index = 0; index < 18; index += 1) {
        const x = ((index * 47) + (scan * 80)) % width;
        const y = ((index * 31) + (pulse * 14)) % height;
        context.beginPath();
        context.arc(x, y, index % 3 === 0 ? 2 : 1.2, 0, Math.PI * 2);
        context.fill();
      }

      if (!reduceMotion) {
        window.requestAnimationFrame(draw);
      }
    };

    if (window.ResizeObserver) {
      const observer = new ResizeObserver(resize);
      observer.observe(canvas);
    } else {
      window.addEventListener('resize', resize);
    }

    draw(0);
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
          throw new Error('camera_unavailable');
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
        setStatus(form, getCameraErrorMessage(error), true);
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
      updateCaptureState(form, file, 'camera');
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

  function getCameraErrorMessage(error) {
    if (!window.isSecureContext) {
      return 'La cámara requiere HTTPS local. Mientras tanto, sube una selfie desde tu galería.';
    }

    const name = error && error.name ? String(error.name) : '';

    if (name === 'NotAllowedError' || name === 'SecurityError') {
      return 'Permite el acceso a la cámara o sube una selfie desde tu galería.';
    }

    if (name === 'NotFoundError' || (error && error.message === 'camera_unavailable')) {
      return 'No encontré una cámara disponible. Puedes subir una selfie desde tu galería.';
    }

    return 'No fue posible abrir la cámara. Puedes subir una selfie desde tu galería.';
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
    updateCaptureState(form, null);
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
    lastVisionSignals = signals || {};
    updatePhotoQuality(form, signals || {});

    if (beforeImage && beforeImage.src) {
      renderSkinPreview(beforeImage.src, afterImage && !afterImage.classList.contains('is-fallback') ? afterImage.src : '', lastVisionSignals);
    }
  }

  function updatePhotoQuality(form, signals) {
    const quality = assessPhotoQuality(signals);
    form.dataset.bscPhotoQualityOk = quality.ok ? '1' : '0';
    form.dataset.bscPhotoQualityMessage = quality.message || '';
    form.dataset.bscPhotoQualityReason = quality.reason || '';
    updatePhotoSignals(signals || {}, quality);

    const warning = form.querySelector('[data-bsc-photo-quality]');
    const shell = form.querySelector('[data-bsc-camera-shell]');

    if (shell) {
      shell.classList.toggle('is-photo-quality-ok', quality.ok);
      shell.classList.toggle('is-photo-quality-warning', !quality.ok);
    }

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
    const shell = form.querySelector('[data-bsc-camera-shell]');

    if (shell) {
      shell.classList.remove('is-photo-quality-ok', 'is-photo-quality-warning');
    }

    if (warning) {
      warning.textContent = '';
      warning.classList.remove('is-visible');
    }

    updatePhotoSignals(null);
  }

  function updatePhotoSignals(signals, quality = null) {
    if (!photoSignalItems.length) {
      return;
    }

    const empty = !signals || !Object.keys(signals).length;
    const signalValue = (key, fallback = 0) => (Number.isFinite(Number(signals[key])) ? Number(signals[key]) : fallback);
    const signalMap = empty ? {} : {
      lighting: Math.max(
        signalValue('lighting_evenness_signal'),
        1 - signalValue('lighting_cast_signal', 1),
        signalValue('tone_sample_confidence')
      ),
      sharpness: Math.max(signalValue('sharpness'), signalValue('fine_texture_signal') * 0.8),
      framing: Math.max(
        signalValue('face_center_score'),
        signalValue('mask_skin_confidence'),
        signalValue('skin_pixel_ratio') * 3
      )
    };

    photoSignalItems.forEach((item) => {
      const key = item.getAttribute('data-bsc-photo-signal') || '';
      const labels = { lighting: 'Luz', sharpness: 'Enfoque', framing: 'Encuadre' };
      const label = labels[key] || key;
      const score = Math.max(0, Math.min(1, Number(signalMap[key] || 0)));
      const state = empty ? 'idle' : score >= 0.64 ? 'good' : score >= 0.38 ? 'warn' : 'low';
      const note = empty ? 'pendiente' : state === 'good' ? 'ok' : state === 'warn' ? 'mejorable' : 'bajo';

      item.classList.remove('is-good', 'is-warn', 'is-low', 'is-idle');
      item.classList.add(`is-${state}`);
      item.style.setProperty('--bsc-photo-signal-score', `${Math.round(score * 100)}%`);
      item.innerHTML = `<i aria-hidden="true"></i>${label}<em>${note}</em>`;
    });

    if (quality && !quality.ok) {
      const target = photoSignalItems.find((item) => {
        const key = item.getAttribute('data-bsc-photo-signal') || '';
        return (
          (['low_light', 'overexposed'].includes(quality.reason || '') || (quality.reason || '').includes('light')) && key === 'lighting'
        ) || (
          ['no_face', 'multiple_faces', 'face_too_small', 'off_center', 'skin_area_unclear'].includes(quality.reason || '') && key === 'framing'
        ) || (
          quality.reason === 'small_image' && key === 'sharpness'
        );
      });

      if (target) {
        target.classList.remove('is-good', 'is-warn', 'is-idle');
        target.classList.add('is-low');
        target.style.setProperty('--bsc-photo-signal-score', '0%');
        const note = target.querySelector('em');
        if (note) {
          note.textContent = 'bajo';
        }
      }
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
    const size = 128;
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
    let skinRed = 0;
    let skinGreen = 0;
    let skinBlue = 0;

    for (let index = 0; index < pixels.length; index += 4) {
      const pixelIndex = index / 4;
      const r = pixels[index] / 255;
      const g = pixels[index + 1] / 255;
      const b = pixels[index + 2] / 255;
      const max = Math.max(r, g, b);
      const min = Math.min(r, g, b);
      const sat = max === 0 ? 0 : (max - min) / max;
      const luma = (0.2126 * r) + (0.7152 * g) + (0.0722 * b);
      const skin = isLikelySkinPixel(r, g, b, luma, sat);
      const rednessIndex = Math.max(0, r - ((g + b) / 2));

      samples.push({
        r,
        g,
        b,
        luma,
        sat,
        skin,
        rednessIndex,
        x: (pixelIndex % size) / size,
        y: Math.floor(pixelIndex / size) / size
      });
      lumas.push(luma);
      if (skin) {
        skinCount += 1;
        brightness += luma;
        saturation += sat;
        skinRed += r;
        skinGreen += g;
        skinBlue += b;
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
      skinRed = samples.reduce((sum, sample) => sum + sample.r, 0);
      skinGreen = samples.reduce((sum, sample) => sum + sample.g, 0);
      skinBlue = samples.reduce((sum, sample) => sum + sample.b, 0);
    }

    const mean = brightness / analysisCount;
    const averageRed = skinRed / analysisCount;
    const averageGreen = skinGreen / analysisCount;
    const averageBlue = skinBlue / analysisCount;
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

    const faceSignals = await detectFaceSignals(canvas, size);
    const lightingSignals = readLightingSignals(samples, size, useSkinSample, faceSignals);
    const correctedTone = correctedSkinToneForLighting(averageRed, averageGreen, averageBlue, mean, lightingSignals);
    const textureSignal = texturePairs ? texture / texturePairs : 0;
    const undertoneProxy = inferUndertone(correctedTone.r, correctedTone.g, correctedTone.b, correctedTone.luma);
    const skinDepthProxy = inferSkinDepth(correctedTone.luma);
    const makeupHints = makeupHintsFor(undertoneProxy, skinDepthProxy, shine / analysisCount);
    const edgeSignals = readEdgeSignals(samples, size, useSkinSample);
    const clusterSignals = readClusterSignals(samples, size, mean, useSkinSample, faceSignals);
    const zoneSignals = readZoneSignals(samples, size, mean, useSkinSample, faceSignals, edgeSignals.edgeMap);
    const symmetrySignals = readSymmetrySignals(samples, mean, useSkinSample, faceSignals);
    const cosmeticSignals = buildCosmeticSignals({
      mean,
      variance,
      shineRatio: shine / analysisCount,
      rednessRatio: redness / analysisCount,
      darkSpotRatio: darkSpots / analysisCount,
      textureSignal,
      skinRatio,
      zoneSignals,
      edgeSignals,
      clusterSignals,
      faceSignals,
      lightingSignals,
      symmetrySignals
    });
    const makeupProfile = buildMakeupProfileV2({
      undertoneProxy,
      skinDepthProxy,
      makeupHints,
      cosmeticSignals,
      lightingSignals,
      zoneSignals,
      correctedTone
    });
    const progressSignals = buildProgressSignals(cosmeticSignals, zoneSignals, clusterSignals, lightingSignals, symmetrySignals);
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
    if (cosmeticSignals.mask_quality === 'limited') {
      qualityFlags.push('mask_limited');
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
      skin_tone_r: roundSignal(averageRed),
      skin_tone_g: roundSignal(averageGreen),
      skin_tone_b: roundSignal(averageBlue),
      skin_tone_luma: roundSignal(mean),
      corrected_skin_tone_r: roundSignal(correctedTone.r),
      corrected_skin_tone_g: roundSignal(correctedTone.g),
      corrected_skin_tone_b: roundSignal(correctedTone.b),
      corrected_skin_tone_luma: roundSignal(correctedTone.luma),
      undertone_proxy: undertoneProxy,
      skin_depth_proxy: skinDepthProxy,
      makeup_lip_hint: makeupHints.lip,
      makeup_blush_hint: makeupHints.blush,
      makeup_finish_hint: makeupHints.finish,
      makeup_foundation_note: makeupHints.foundation,
      ...zoneSignals,
      ...lightingSignals,
      ...symmetrySignals,
      ...edgeSignals.publicSignals,
      ...clusterSignals,
      ...cosmeticSignals,
      ...makeupProfile,
      ...progressSignals,
      quality_flags: qualityFlags,
      ...faceSignals
    };
  }

  function inferUndertone(red, green, blue, luma) {
    const warmth = red - blue;
    const olive = green - ((red + blue) / 2);

    if (olive > 0.025 && luma < 0.72) {
      return 'olive';
    }

    if (warmth > 0.055) {
      return 'warm';
    }

    if (warmth < -0.018) {
      return 'cool';
    }

    return 'neutral';
  }

  function inferSkinDepth(luma) {
    if (luma >= 0.72) {
      return 'fair';
    }

    if (luma >= 0.6) {
      return 'light';
    }

    if (luma >= 0.46) {
      return 'medium';
    }

    if (luma >= 0.32) {
      return 'tan';
    }

    return 'deep';
  }

  function makeupHintsFor(undertone, depth, shineRatio) {
    const warm = undertone === 'warm' || undertone === 'olive';
    const deep = depth === 'tan' || depth === 'deep';

    return {
      lip: warm ? (deep ? 'terracota suave o berry calido' : 'durazno rosado') : (deep ? 'rosa ciruela suave' : 'rosa frio transparente'),
      blush: warm ? 'coral suave aplicado ligero' : 'rosa malva aplicado ligero',
      finish: shineRatio > 0.08 ? 'satinado controlado en zona T' : 'glow hidratado sin exceso de brillo',
      foundation: `subtono ${undertone}, profundidad ${depth}; probar en mandibula con luz natural`
    };
  }

  function readLightingSignals(samples, size, useSkinSample, faceSignals) {
    const faceMask = faceMaskFor(faceSignals);
    const buckets = {
      left: emptyColorBucket(),
      right: emptyColorBucket(),
      top: emptyColorBucket(),
      bottom: emptyColorBucket(),
      face: emptyColorBucket()
    };

    samples.forEach((sample) => {
      if ((useSkinSample && !sample.skin) || !insideEllipse(sample.x, sample.y, faceMask)) {
        return;
      }

      addColorSample(buckets.face, sample);

      if (sample.x < faceMask.cx) {
        addColorSample(buckets.left, sample);
      } else {
        addColorSample(buckets.right, sample);
      }

      if (sample.y < faceMask.cy) {
        addColorSample(buckets.top, sample);
      } else {
        addColorSample(buckets.bottom, sample);
      }
    });

    Object.keys(buckets).forEach((key) => {
      buckets[key] = finalizeColorBucket(buckets[key]);
    });

    const sideDelta = buckets.left.count && buckets.right.count ? buckets.left.luma - buckets.right.luma : 0;
    const verticalDelta = buckets.top.count && buckets.bottom.count ? buckets.top.luma - buckets.bottom.luma : 0;
    const lumas = [buckets.left.luma, buckets.right.luma, buckets.top.luma, buckets.bottom.luma].filter((value) => Number.isFinite(value));
    const evenness = lumas.length ? 1 - Math.min(1, (Math.max(...lumas) - Math.min(...lumas)) * 2.8) : 0;
    const redBlueDelta = buckets.face.r - buckets.face.b;
    const greenTintDelta = buckets.face.g - ((buckets.face.r + buckets.face.b) / 2);
    const castStrength = Math.min(1, (Math.abs(redBlueDelta) + Math.abs(greenTintDelta)) * 2.6);
    const sampleConfidence = Math.min(1, buckets.face.count / (size * size * 0.16));

    return {
      lighting_temperature_proxy: redBlueDelta > 0.055 ? 'warm_cast' : redBlueDelta < -0.025 ? 'cool_cast' : 'neutral_light',
      lighting_tint_proxy: greenTintDelta > 0.025 ? 'green_cast' : greenTintDelta < -0.025 ? 'magenta_cast' : 'neutral_tint',
      lighting_shadow_bias: Math.abs(sideDelta) > Math.abs(verticalDelta) && Math.abs(sideDelta) > 0.045
        ? (sideDelta < 0 ? 'left_shadow' : 'right_shadow')
        : Math.abs(verticalDelta) > 0.045
          ? (verticalDelta < 0 ? 'top_shadow' : 'bottom_shadow')
          : 'balanced',
      lighting_warmth_signal: roundSignal(Math.max(0, redBlueDelta)),
      lighting_coolness_signal: roundSignal(Math.max(0, -redBlueDelta)),
      lighting_green_tint_signal: roundSignal(Math.max(0, greenTintDelta)),
      lighting_magenta_tint_signal: roundSignal(Math.max(0, -greenTintDelta)),
      lighting_evenness_signal: roundSignal(evenness),
      lighting_cast_signal: roundSignal(castStrength),
      lighting_side_delta_signal: roundSignal(Math.min(1, Math.abs(sideDelta) * 3)),
      lighting_vertical_delta_signal: roundSignal(Math.min(1, Math.abs(verticalDelta) * 3)),
      tone_sample_confidence: roundSignal(sampleConfidence)
    };
  }

  function correctedSkinToneForLighting(red, green, blue, luma, lightingSignals) {
    let correctedRed = red;
    let correctedGreen = green;
    let correctedBlue = blue;
    const warmth = Number(lightingSignals.lighting_warmth_signal || 0) - Number(lightingSignals.lighting_coolness_signal || 0);
    const tint = Number(lightingSignals.lighting_green_tint_signal || 0) - Number(lightingSignals.lighting_magenta_tint_signal || 0);
    const correctionStrength = Math.min(0.16, Number(lightingSignals.lighting_cast_signal || 0) * 0.08);

    correctedRed -= warmth * correctionStrength;
    correctedBlue += warmth * correctionStrength;
    correctedGreen -= tint * correctionStrength;

    return {
      r: clamp01(correctedRed),
      g: clamp01(correctedGreen),
      b: clamp01(correctedBlue),
      luma: clamp01((0.2126 * correctedRed) + (0.7152 * correctedGreen) + (0.0722 * correctedBlue) || luma)
    };
  }

  function emptyColorBucket() {
    return {
      count: 0,
      r: 0,
      g: 0,
      b: 0,
      luma: 0
    };
  }

  function addColorSample(bucket, sample) {
    bucket.count += 1;
    bucket.r += sample.r;
    bucket.g += sample.g;
    bucket.b += sample.b;
    bucket.luma += sample.luma;
  }

  function finalizeColorBucket(bucket) {
    if (!bucket.count) {
      return {
        count: 0,
        r: 0,
        g: 0,
        b: 0,
        luma: 0
      };
    }

    return {
      count: bucket.count,
      r: bucket.r / bucket.count,
      g: bucket.g / bucket.count,
      b: bucket.b / bucket.count,
      luma: bucket.luma / bucket.count
    };
  }

  function readEdgeSignals(samples, size, useSkinSample) {
    const edgeMap = new Float32Array(samples.length);
    let skinEdges = 0;
    let skinLaplacian = 0;
    let skinCount = 0;

    for (let row = 1; row < size - 1; row += 1) {
      for (let col = 1; col < size - 1; col += 1) {
        const index = (row * size) + col;
        const sample = samples[index];

        if (useSkinSample && !sample.skin) {
          continue;
        }

        const tl = samples[index - size - 1].luma;
        const tc = samples[index - size].luma;
        const tr = samples[index - size + 1].luma;
        const ml = samples[index - 1].luma;
        const mr = samples[index + 1].luma;
        const bl = samples[index + size - 1].luma;
        const bc = samples[index + size].luma;
        const br = samples[index + size + 1].luma;
        const gx = (-tl - (2 * ml) - bl) + (tr + (2 * mr) + br);
        const gy = (-tl - (2 * tc) - tr) + (bl + (2 * bc) + br);
        const edge = Math.min(1, Math.hypot(gx, gy));
        const laplacian = Math.abs((4 * sample.luma) - tc - ml - mr - bc);

        edgeMap[index] = edge;
        skinEdges += edge;
        skinLaplacian += laplacian;
        skinCount += 1;
      }
    }

    const averageEdge = skinCount ? skinEdges / skinCount : 0;
    const averageLaplacian = skinCount ? skinLaplacian / skinCount : 0;

    return {
      edgeMap,
      publicSignals: {
        fine_texture_signal: roundSignal(Math.min(1, averageEdge * 2.4)),
        pores_proxy_signal: roundSignal(Math.min(1, averageLaplacian * 2.8)),
        blur_proxy_signal: roundSignal(Math.max(0, 1 - (averageEdge * 9)))
      }
    };
  }

  function readClusterSignals(samples, size, mean, useSkinSample, faceSignals) {
    const faceMask = faceMaskFor(faceSignals);
    const spotMask = new Uint8Array(samples.length);
    const redMask = new Uint8Array(samples.length);
    const shadowMask = new Uint8Array(samples.length);

    samples.forEach((sample, index) => {
      if ((useSkinSample && !sample.skin) || !insideEllipse(sample.x, sample.y, faceMask)) {
        return;
      }

      if (sample.luma < mean - 0.14 && sample.sat > 0.12) {
        spotMask[index] = 1;
      }

      if (sample.rednessIndex > 0.085 && sample.sat > 0.16) {
        redMask[index] = 1;
      }

      if (sample.luma < mean - 0.18 && sample.sat < 0.32) {
        shadowMask[index] = 1;
      }
    });

    const spots = connectedMaskStats(spotMask, size);
    const reds = connectedMaskStats(redMask, size);
    const shadows = connectedMaskStats(shadowMask, size);

    return {
      spot_cluster_signal: roundSignal(Math.min(1, spots.clusters / 14)),
      spot_area_signal: roundSignal(Math.min(1, spots.areaRatio * 9)),
      spot_largest_signal: roundSignal(Math.min(1, spots.largestRatio * 24)),
      red_cluster_signal: roundSignal(Math.min(1, reds.areaRatio * 8)),
      shadow_cluster_signal: roundSignal(Math.min(1, shadows.areaRatio * 7))
    };
  }

  function connectedMaskStats(mask, size) {
    const visited = new Uint8Array(mask.length);
    let clusters = 0;
    let area = 0;
    let largest = 0;

    for (let index = 0; index < mask.length; index += 1) {
      if (!mask[index] || visited[index]) {
        continue;
      }

      const stack = [index];
      let currentSize = 0;
      visited[index] = 1;

      while (stack.length) {
        const current = stack.pop();
        const row = Math.floor(current / size);
        const col = current % size;
        currentSize += 1;

        const neighbors = [
          row > 0 ? current - size : -1,
          row < size - 1 ? current + size : -1,
          col > 0 ? current - 1 : -1,
          col < size - 1 ? current + 1 : -1
        ];

        neighbors.forEach((neighbor) => {
          if (neighbor >= 0 && mask[neighbor] && !visited[neighbor]) {
            visited[neighbor] = 1;
            stack.push(neighbor);
          }
        });
      }

      if (currentSize >= 2) {
        clusters += 1;
        area += currentSize;
        largest = Math.max(largest, currentSize);
      }
    }

    return {
      clusters,
      areaRatio: area / mask.length,
      largestRatio: largest / mask.length
    };
  }

  function readZoneSignals(samples, size, mean, useSkinSample, faceSignals, edgeMap) {
    const box = faceBoxFor(faceSignals);
    const zones = {
      forehead: ellipseZone(box, 0.5, 0.22, 0.24, 0.11),
      nose: ellipseZone(box, 0.5, 0.48, 0.11, 0.22),
      chin: ellipseZone(box, 0.5, 0.78, 0.2, 0.1),
      leftCheek: ellipseZone(box, 0.31, 0.54, 0.17, 0.14),
      rightCheek: ellipseZone(box, 0.69, 0.54, 0.17, 0.14),
      underEyeLeft: ellipseZone(box, 0.35, 0.38, 0.15, 0.055),
      underEyeRight: ellipseZone(box, 0.65, 0.38, 0.15, 0.055),
      mouth: ellipseZone(box, 0.5, 0.72, 0.15, 0.055),
      face: faceMaskFor(faceSignals)
    };

    const metrics = {};
    Object.entries(zones).forEach(([name, zone]) => {
      metrics[name] = zoneMetrics(samples, edgeMap, zone, mean, useSkinSample, name === 'mouth');
    });

    const cheekRedness = average(metrics.leftCheek.redness, metrics.rightCheek.redness);
    const underEyeShadow = average(metrics.underEyeLeft.shadow, metrics.underEyeRight.shadow);
    const tShine = average(metrics.forehead.shine, metrics.nose.shine, metrics.chin.shine);

    return {
      zone_t_shine: roundSignal(tShine),
      zone_cheek_redness: roundSignal(cheekRedness),
      zone_spot_concentration: roundSignal(metrics.face.spots),
      zone_forehead_shine: roundSignal(metrics.forehead.shine),
      zone_nose_shine: roundSignal(metrics.nose.shine),
      zone_chin_texture: roundSignal(metrics.chin.texture),
      zone_left_cheek_redness: roundSignal(metrics.leftCheek.redness),
      zone_right_cheek_redness: roundSignal(metrics.rightCheek.redness),
      zone_under_eye_shadow: roundSignal(underEyeShadow),
      zone_mouth_tint: roundSignal(metrics.mouth.saturation)
    };
  }

  function zoneMetrics(samples, edgeMap, zone, mean, useSkinSample, allowNonSkin = false) {
    let count = 0;
    let shine = 0;
    let redness = 0;
    let spots = 0;
    let shadow = 0;
    let saturation = 0;
    let texture = 0;

    samples.forEach((sample, index) => {
      if (!insideEllipse(sample.x, sample.y, zone)) {
        return;
      }

      if (!allowNonSkin && useSkinSample && !sample.skin) {
        return;
      }

      count += 1;
      saturation += sample.sat;
      texture += edgeMap[index] || 0;

      if (sample.luma > Math.min(0.9, mean + 0.2) && sample.sat < 0.34) {
        shine += 1;
      }

      if (sample.rednessIndex > 0.075 && sample.sat > 0.16) {
        redness += 1;
      }

      if (sample.luma < mean - 0.15 && sample.sat > 0.12) {
        spots += 1;
      }

      if (sample.luma < mean - 0.16 && sample.sat < 0.35) {
        shadow += 1;
      }
    });

    return {
      shine: count ? shine / count : 0,
      redness: count ? redness / count : 0,
      spots: count ? spots / count : 0,
      shadow: count ? shadow / count : 0,
      saturation: count ? saturation / count : 0,
      texture: count ? texture / count : 0
    };
  }

  function readSymmetrySignals(samples, mean, useSkinSample, faceSignals) {
    const mask = faceMaskFor(faceSignals);
    const left = emptySymmetryBucket();
    const right = emptySymmetryBucket();

    samples.forEach((sample) => {
      if ((useSkinSample && !sample.skin) || !insideEllipse(sample.x, sample.y, mask)) {
        return;
      }

      const bucket = sample.x < mask.cx ? left : right;
      bucket.count += 1;
      bucket.luma += sample.luma;
      bucket.redness += sample.rednessIndex > 0.075 && sample.sat > 0.16 ? 1 : 0;
      bucket.spots += sample.luma < mean - 0.15 && sample.sat > 0.12 ? 1 : 0;
      bucket.shadow += sample.luma < mean - 0.16 && sample.sat < 0.35 ? 1 : 0;
    });

    const leftMetrics = finalizeSymmetryBucket(left);
    const rightMetrics = finalizeSymmetryBucket(right);
    const lumaDelta = Math.abs(leftMetrics.luma - rightMetrics.luma);
    const rednessDelta = Math.abs(leftMetrics.redness - rightMetrics.redness);
    const spotDelta = Math.abs(leftMetrics.spots - rightMetrics.spots);
    const shadowDelta = Math.abs(leftMetrics.shadow - rightMetrics.shadow);
    const asymmetry = clamp01((lumaDelta * 1.8) + (rednessDelta * 1.2) + (spotDelta * 1.3) + (shadowDelta * 1.4));

    return {
      symmetry_balance_signal: roundSignal(1 - asymmetry),
      asymmetric_shadow_signal: roundSignal(Math.min(1, shadowDelta * 3.5)),
      asymmetric_redness_signal: roundSignal(Math.min(1, rednessDelta * 3)),
      asymmetric_spot_signal: roundSignal(Math.min(1, spotDelta * 3)),
      left_right_luma_delta: roundSignal(Math.min(1, lumaDelta * 3)),
      left_right_redness_delta: roundSignal(Math.min(1, rednessDelta * 3)),
      left_right_spot_delta: roundSignal(Math.min(1, spotDelta * 3)),
      asymmetry_bias: lumaDelta > 0.05 ? (leftMetrics.luma < rightMetrics.luma ? 'left_darker' : 'right_darker') : 'balanced'
    };
  }

  function emptySymmetryBucket() {
    return {
      count: 0,
      luma: 0,
      redness: 0,
      spots: 0,
      shadow: 0
    };
  }

  function finalizeSymmetryBucket(bucket) {
    if (!bucket.count) {
      return {
        luma: 0,
        redness: 0,
        spots: 0,
        shadow: 0
      };
    }

    return {
      luma: bucket.luma / bucket.count,
      redness: bucket.redness / bucket.count,
      spots: bucket.spots / bucket.count,
      shadow: bucket.shadow / bucket.count
    };
  }

  function buildCosmeticSignals(context) {
    const contrastSignal = Math.min(1, Math.sqrt(context.variance) * 2.5);
    const fineTexture = Number(context.edgeSignals.publicSignals.fine_texture_signal || 0);
    const poresProxy = Number(context.edgeSignals.publicSignals.pores_proxy_signal || 0);
    const oilControl = Math.max(context.shineRatio, context.zoneSignals.zone_t_shine || 0);
    const toneUnevenness = clamp01((contrastSignal * 0.36) + (context.darkSpotRatio * 0.34) + (context.clusterSignals.spot_area_signal * 0.3));
    const dryness = clamp01((fineTexture * 0.34) + ((1 - oilControl) * 0.22) + (context.mean < 0.48 ? 0.12 : 0) + (poresProxy * 0.18));
    const barrierStress = clamp01((context.rednessRatio * 0.36) + ((context.zoneSignals.zone_cheek_redness || 0) * 0.28) + (dryness * 0.24) + (fineTexture * 0.12));
    const breakoutProne = clamp01((context.clusterSignals.red_cluster_signal * 0.38) + (context.rednessRatio * 0.24) + (context.textureSignal * 0.18) + (context.clusterSignals.spot_cluster_signal * 0.2));
    const hydrationNeed = clamp01((dryness * 0.56) + (barrierStress * 0.26) + (context.mean < 0.5 ? 0.08 : 0));
    const spfPriority = clamp01((toneUnevenness * 0.44) + (context.darkSpotRatio * 0.34) + (context.clusterSignals.spot_area_signal * 0.22));
    const underEyeShadow = Math.max(context.zoneSignals.zone_under_eye_shadow || 0, context.clusterSignals.shadow_cluster_signal * 0.45);
    const shadowLikelihood = clamp01(
      (context.clusterSignals.shadow_cluster_signal * 0.36)
      + ((1 - Number(context.lightingSignals.lighting_evenness_signal || 0)) * 0.28)
      + (Number(context.symmetrySignals.asymmetric_shadow_signal || 0) * 0.22)
      + (Number(context.lightingSignals.lighting_side_delta_signal || 0) * 0.14)
    );
    const pigmentLikelihood = clamp01(
      (context.clusterSignals.spot_area_signal * 0.34)
      + (context.clusterSignals.spot_cluster_signal * 0.24)
      + (toneUnevenness * 0.26)
      + ((1 - shadowLikelihood) * 0.16)
    );
    const lightingReliability = clamp01(
      (Number(context.lightingSignals.lighting_evenness_signal || 0) * 0.5)
      + (Number(context.lightingSignals.tone_sample_confidence || 0) * 0.3)
      + ((1 - Number(context.lightingSignals.lighting_cast_signal || 0)) * 0.2)
    );
    const supportSignal = clamp01(
      (toneUnevenness * 0.24)
      + (dryness * 0.18)
      + (barrierStress * 0.18)
      + (spfPriority * 0.18)
      + (fineTexture * 0.14)
      + (underEyeShadow * 0.08)
    );
    const skinTypeProxy = skinTypeProxyFor({
      oilControl,
      dryness,
      barrierStress,
      breakoutProne,
      cheekRedness: context.zoneSignals.zone_cheek_redness || 0
    });
    const maskSkinConfidence = clamp01(context.skinRatio >= 0.18 ? 0.72 + (context.skinRatio * 0.28) : context.skinRatio * 3.5);
    const faceUseful = context.faceSignals.face_detection !== 'supported' || Number(context.faceSignals.face_count || 0) === 1;
    const maskQuality = maskSkinConfidence > 0.64 && faceUseful ? 'strong' : maskSkinConfidence > 0.34 ? 'usable' : 'limited';
    const priorityFlags = cosmeticPriorityFlags({
      oilControl,
      toneUnevenness,
      dryness,
      barrierStress,
      breakoutProne,
      hydrationNeed,
      spfPriority,
      underEyeShadow,
      fineTexture
    });

    return {
      image_processing_version: 'bsc_cv_2',
      mask_quality: maskQuality,
      mask_skin_confidence: roundSignal(maskSkinConfidence),
      skin_type_proxy: skinTypeProxy,
      skin_support_signal: roundSignal(supportSignal),
      oil_control_signal: roundSignal(oilControl),
      tone_unevenness_signal: roundSignal(toneUnevenness),
      pigment_spot_proxy: roundSignal(pigmentLikelihood),
      lighting_shadow_proxy: roundSignal(shadowLikelihood),
      spot_shadow_confidence: roundSignal(lightingReliability),
      dryness_signal: roundSignal(dryness),
      barrier_stress_signal: roundSignal(barrierStress),
      breakout_prone_signal: roundSignal(breakoutProne),
      hydration_need_signal: roundSignal(hydrationNeed),
      spf_priority_signal: roundSignal(spfPriority),
      under_eye_shadow_signal: roundSignal(underEyeShadow),
      cosmetic_priority_flags: priorityFlags,
      routine_focus_hint: routineFocusHint(priorityFlags)
    };
  }

  function buildMakeupProfileV2(context) {
    const depth = context.skinDepthProxy;
    const undertone = context.undertoneProxy;
    const tone = Number(context.cosmeticSignals.tone_unevenness_signal || 0);
    const oil = Number(context.cosmeticSignals.oil_control_signal || 0);
    const texture = Number(context.cosmeticSignals.skin_support_signal || 0);
    const lightingConfidence = Number(context.lightingSignals.tone_sample_confidence || 0) * (1 - (Number(context.lightingSignals.lighting_cast_signal || 0) * 0.42));
    const colorFamily = undertone === 'cool'
      ? 'rose_mauve_berry'
      : undertone === 'olive'
        ? 'neutral_peach_terracotta'
        : undertone === 'warm'
          ? 'peach_coral_terracotta'
          : 'soft_rose_neutral';
    const coverage = tone > 0.46
      ? 'light_to_medium_evening'
      : texture > 0.52
        ? 'skin_tint_hydrating'
        : 'sheer_skinlike';
    const finish = oil > 0.18
      ? 'soft_matte_tzone_satin_cheeks'
      : Number(context.cosmeticSignals.dryness_signal || 0) > 0.42
        ? 'hydrating_glow'
        : context.makeupHints.finish.replace(/\s+/g, '_').toLowerCase();

    return {
      makeup_profile_version: 'makeup_profile_v2',
      makeup_base_finish: finish,
      makeup_coverage_hint: coverage,
      makeup_color_family: colorFamily,
      makeup_blush_v2: context.makeupHints.blush,
      makeup_lip_v2: context.makeupHints.lip,
      makeup_eye_brightness_hint: Number(context.cosmeticSignals.under_eye_shadow_signal || 0) > 0.16 ? 'soft_peach_or_rosy_brightening' : 'minimal_brightening',
      makeup_texture_strategy: texture > 0.54 ? 'avoid_heavy_powder_keep_skinlike' : 'thin_layers_blended',
      makeup_match_confidence: roundSignal(clamp01(lightingConfidence)),
      makeup_foundation_depth_hint: depth,
      makeup_foundation_undertone_hint: undertone
    };
  }

  function buildProgressSignals(cosmeticSignals, zoneSignals, clusterSignals, lightingSignals, symmetrySignals) {
    const confidence = clamp01(
      (Number(cosmeticSignals.mask_skin_confidence || 0) * 0.35)
      + (Number(lightingSignals.lighting_evenness_signal || 0) * 0.25)
      + (Number(lightingSignals.tone_sample_confidence || 0) * 0.2)
      + (Number(symmetrySignals.symmetry_balance_signal || 0) * 0.2)
    );
    const signatureParts = [
      bucketSignal(cosmeticSignals.oil_control_signal),
      bucketSignal(cosmeticSignals.tone_unevenness_signal),
      bucketSignal(cosmeticSignals.dryness_signal),
      bucketSignal(cosmeticSignals.barrier_stress_signal),
      bucketSignal(cosmeticSignals.pigment_spot_proxy),
      bucketSignal(zoneSignals.zone_under_eye_shadow),
      bucketSignal(clusterSignals.red_cluster_signal)
    ];

    return {
      progress_tracking_ready: confidence > 0.58 ? 'yes' : 'limited',
      progress_tracking_signal: roundSignal(confidence),
      progress_signature: `bsc2-${signatureParts.join('')}`,
      progress_baseline_hint: confidence > 0.58 ? 'compare_future_photo_same_light_angle' : 'retake_for_better_baseline'
    };
  }

  function bucketSignal(value) {
    return Math.max(0, Math.min(9, Math.round((Number(value) || 0) * 9))).toString(10);
  }

  function cosmeticPriorityFlags(signals) {
    const flags = [];

    if (signals.oilControl > 0.08) {
      flags.push('tzone_shine');
    }
    if (signals.toneUnevenness > 0.28) {
      flags.push('tone_evening');
    }
    if (signals.dryness > 0.45) {
      flags.push('hydration_barrier');
    }
    if (signals.barrierStress > 0.22) {
      flags.push('calming');
    }
    if (signals.breakoutProne > 0.18) {
      flags.push('breakout_support');
    }
    if (signals.spfPriority > 0.22) {
      flags.push('spf_priority');
    }
    if (signals.underEyeShadow > 0.16) {
      flags.push('fresh_finish');
    }
    if (signals.fineTexture > 0.18) {
      flags.push('texture_refinement');
    }

    return flags.slice(0, 8);
  }

  function skinTypeProxyFor(signals) {
    if (signals.barrierStress > 0.32 || signals.cheekRedness > 0.2) {
      return 'sensible';
    }

    if (signals.oilControl > 0.18 && signals.dryness > 0.34) {
      return 'mixta-deshidratada';
    }

    if (signals.oilControl > 0.2 || signals.breakoutProne > 0.26) {
      return 'grasa';
    }

    if (signals.dryness > 0.48) {
      return 'seca';
    }

    if (signals.oilControl > 0.08 && signals.dryness > 0.22) {
      return 'mixta';
    }

    return 'normal';
  }

  function routineFocusHint(flags) {
    if (flags.includes('calming') || flags.includes('hydration_barrier')) {
      return 'barrier_first_calm_hydrate';
    }

    if (flags.includes('tone_evening') || flags.includes('spf_priority')) {
      return 'tone_evening_antioxidant_spf';
    }

    if (flags.includes('tzone_shine') || flags.includes('breakout_support')) {
      return 'balance_tzone_gentle_clear';
    }

    if (flags.includes('texture_refinement')) {
      return 'smooth_texture_glow';
    }

    return 'maintain_hydrated_glow';
  }

  function faceBoxFor(faceSignals = {}) {
    if (faceSignals.face_detection === 'supported' && Number(faceSignals.face_count || 0) === 1) {
      return {
        x: Number(faceSignals.face_box_x || 0.18),
        y: Number(faceSignals.face_box_y || 0.08),
        width: Number(faceSignals.face_box_w || 0.64),
        height: Number(faceSignals.face_box_h || 0.82)
      };
    }

    return {
      x: 0.18,
      y: 0.08,
      width: 0.64,
      height: 0.82
    };
  }

  function faceMaskFor(faceSignals = {}) {
    const box = faceBoxFor(faceSignals);
    return {
      cx: box.x + (box.width * 0.5),
      cy: box.y + (box.height * 0.52),
      rx: box.width * 0.48,
      ry: box.height * 0.54
    };
  }

  function ellipseZone(box, centerX, centerY, radiusX, radiusY) {
    return {
      cx: box.x + (box.width * centerX),
      cy: box.y + (box.height * centerY),
      rx: box.width * radiusX,
      ry: box.height * radiusY
    };
  }

  function insideEllipse(x, y, zone) {
    const dx = (x - zone.cx) / Math.max(0.001, zone.rx);
    const dy = (y - zone.cy) / Math.max(0.001, zone.ry);
    return (dx * dx) + (dy * dy) <= 1;
  }

  function average(...values) {
    const usable = values.map(Number).filter((value) => Number.isFinite(value));
    return usable.length ? usable.reduce((sum, value) => sum + value, 0) / usable.length : 0;
  }

  function clamp01(value) {
    return Math.max(0, Math.min(1, Number(value) || 0));
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
        face_center_score: roundSignal(centerScore),
        face_box_x: roundSignal(firstFace.x / size),
        face_box_y: roundSignal(firstFace.y / size),
        face_box_w: roundSignal(firstFace.width / size),
        face_box_h: roundSignal(firstFace.height / size)
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

  function renderSkinPreview(beforeSrc, afterSrc = '', signals = {}) {
    if (!webglCanvas || !compare || !beforeSrc) {
      return;
    }

    if (!skinPreviewRenderer) {
      try {
        skinPreviewRenderer = new BscSkinPreviewRenderer(webglCanvas);
      } catch (error) {
        compare.classList.remove('has-webgl');
        return;
      }
    }

    const ticket = webglRenderTicket + 1;
    webglRenderTicket = ticket;
    skinPreviewRenderer.setIntensityMode(afterIntensityMode);
    updateCompare();

    skinPreviewRenderer.render(beforeSrc, afterSrc, signals, { animate: !reduceMotion })
      .then((didRender) => {
        if (ticket !== webglRenderTicket || !didRender) {
          return;
        }

        compare.classList.add('has-webgl');
        if (afterWrap) {
          afterWrap.classList.remove('is-fallback');
        }
        window.requestAnimationFrame(updateCompare);
      })
      .catch(() => {
        compare.classList.remove('has-webgl');
      });
  }

  function initAfterIntensityControls() {
    if (!compare || compare.querySelector('[data-bsc-after-intensity]')) {
      return;
    }

    const controls = document.createElement('div');
    controls.className = 'bsc-skin-quiz__after-controls';
    controls.setAttribute('data-bsc-after-intensity', '1');
    [
      ['natural', 'Natural'],
      ['glow', 'Glow'],
      ['visible', 'Más visible']
    ].forEach(([value, label]) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = value === afterIntensityMode ? 'is-active' : '';
      button.dataset.intensity = value;
      button.textContent = label;
      controls.appendChild(button);
    });

    controls.addEventListener('click', (event) => {
      const button = event.target.closest('button[data-intensity]');
      if (!button) {
        return;
      }
      afterIntensityMode = button.dataset.intensity || 'natural';
      controls.querySelectorAll('button').forEach((item) => {
        item.classList.toggle('is-active', item === button);
      });
      if (skinPreviewRenderer) {
        skinPreviewRenderer.setIntensityMode(afterIntensityMode);
      }
    });

    compare.appendChild(controls);
  }

  function setSkinPreviewAnalysis(active, progress) {
    if (!skinPreviewRenderer) {
      return;
    }

    skinPreviewRenderer.setAnalysis(active, progress);
  }

  class BscSkinPreviewRenderer {
    constructor(canvas) {
      this.canvas = canvas;
      this.gl = canvas.getContext('webgl', {
        alpha: true,
        antialias: false,
        depth: false,
        preserveDrawingBuffer: false
      }) || canvas.getContext('experimental-webgl');

      if (!this.gl) {
        throw new Error('WebGL no disponible');
      }

      this.program = this.createProgram();
      this.buffer = this.gl.createBuffer();
      this.beforeTexture = null;
      this.afterTexture = null;
      this.beforeSize = { width: 1, height: 1 };
      this.afterSize = { width: 1, height: 1 };
      this.beforeSrc = '';
      this.afterSrc = '';
      this.animationFrame = 0;
      this.startedAt = 0;
      this.analysisActive = false;
      this.analysisProgress = 0;
      this.intensityMode = 'natural';
      this.uniforms = {
        before: this.gl.getUniformLocation(this.program, 'u_before'),
        after: this.gl.getUniformLocation(this.program, 'u_after'),
        hasAfter: this.gl.getUniformLocation(this.program, 'u_has_after'),
        time: this.gl.getUniformLocation(this.program, 'u_time'),
        intensity: this.gl.getUniformLocation(this.program, 'u_intensity'),
        shine: this.gl.getUniformLocation(this.program, 'u_shine'),
        redness: this.gl.getUniformLocation(this.program, 'u_redness'),
        spots: this.gl.getUniformLocation(this.program, 'u_spots'),
        texture: this.gl.getUniformLocation(this.program, 'u_texture'),
        warmth: this.gl.getUniformLocation(this.program, 'u_warmth'),
        makeup: this.gl.getUniformLocation(this.program, 'u_makeup'),
        analysis: this.gl.getUniformLocation(this.program, 'u_analysis'),
        analysisProgress: this.gl.getUniformLocation(this.program, 'u_analysis_progress'),
        beforeCover: this.gl.getUniformLocation(this.program, 'u_before_cover'),
        afterCover: this.gl.getUniformLocation(this.program, 'u_after_cover')
      };

      this.gl.bindBuffer(this.gl.ARRAY_BUFFER, this.buffer);
      this.gl.bufferData(
        this.gl.ARRAY_BUFFER,
        new Float32Array([-1, -1, 1, -1, -1, 1, -1, 1, 1, -1, 1, 1]),
        this.gl.STATIC_DRAW
      );
    }

    async render(beforeSrc, afterSrc, signals, options = {}) {
      if (!beforeSrc) {
        return false;
      }

      const [beforeTexture, afterTexture] = await Promise.all([
        this.loadTexture(beforeSrc, 'before'),
        afterSrc ? this.loadTexture(afterSrc, 'after') : Promise.resolve(null)
      ]);

      if (!beforeTexture) {
        return false;
      }

      this.beforeTexture = beforeTexture;
      this.afterTexture = afterTexture || beforeTexture;
      this.signals = signals || {};
      this.hasAfter = Boolean(afterTexture);
      this.afterSize = afterTexture ? this.afterSize : this.beforeSize;
      this.startedAt = window.performance ? window.performance.now() : Date.now();
      this.stop();
      this.draw(0);

      if (options.animate) {
        const tick = (time) => {
          this.draw((time - this.startedAt) / 1000);
          this.animationFrame = window.requestAnimationFrame(tick);
        };
        this.animationFrame = window.requestAnimationFrame(tick);
      }

      return true;
    }

    setAnalysis(active, progress = 0) {
      this.analysisActive = Boolean(active);
      this.analysisProgress = Math.max(0, Math.min(1, Number(progress) || 0));

      if (!this.animationFrame && this.beforeTexture && !reduceMotion) {
        this.startedAt = window.performance ? window.performance.now() : Date.now();
        const tick = (time) => {
          this.draw((time - this.startedAt) / 1000);
          if (this.analysisActive) {
            this.animationFrame = window.requestAnimationFrame(tick);
          } else {
            this.animationFrame = 0;
          }
        };
        this.animationFrame = window.requestAnimationFrame(tick);
      } else if (this.beforeTexture) {
        const now = window.performance ? window.performance.now() : Date.now();
        this.draw((now - this.startedAt) / 1000);
      }
    }

    setIntensityMode(mode) {
      this.intensityMode = ['natural', 'glow', 'visible'].includes(mode) ? mode : 'natural';
      if (this.beforeTexture) {
        const now = window.performance ? window.performance.now() : Date.now();
        this.draw((now - this.startedAt) / 1000);
      }
    }

    stop() {
      if (this.animationFrame) {
        window.cancelAnimationFrame(this.animationFrame);
        this.animationFrame = 0;
      }
    }

    loadTexture(src, slot) {
      if (!src) {
        return Promise.resolve(null);
      }

      if (slot === 'before' && src === this.beforeSrc && this.beforeTexture) {
        return Promise.resolve(this.beforeTexture);
      }

      if (slot === 'after' && src === this.afterSrc && this.afterTexture) {
        return Promise.resolve(this.afterTexture);
      }

      return new Promise((resolve) => {
        const image = new Image();
        image.decoding = 'async';
        image.addEventListener('load', () => {
          const imageSize = {
            width: image.naturalWidth || image.width || 1,
            height: image.naturalHeight || image.height || 1
          };
          const texture = this.gl.createTexture();
          this.gl.bindTexture(this.gl.TEXTURE_2D, texture);
          this.gl.pixelStorei(this.gl.UNPACK_FLIP_Y_WEBGL, true);
          this.gl.texParameteri(this.gl.TEXTURE_2D, this.gl.TEXTURE_WRAP_S, this.gl.CLAMP_TO_EDGE);
          this.gl.texParameteri(this.gl.TEXTURE_2D, this.gl.TEXTURE_WRAP_T, this.gl.CLAMP_TO_EDGE);
          this.gl.texParameteri(this.gl.TEXTURE_2D, this.gl.TEXTURE_MIN_FILTER, this.gl.LINEAR);
          this.gl.texParameteri(this.gl.TEXTURE_2D, this.gl.TEXTURE_MAG_FILTER, this.gl.LINEAR);
          this.gl.texImage2D(this.gl.TEXTURE_2D, 0, this.gl.RGBA, this.gl.RGBA, this.gl.UNSIGNED_BYTE, image);

          if (slot === 'before') {
            this.beforeSrc = src;
            this.beforeSize = imageSize;
          } else {
            this.afterSrc = src;
            this.afterSize = imageSize;
          }

          resolve(texture);
        }, { once: true });
        image.addEventListener('error', () => resolve(null), { once: true });
        image.src = src;
      });
    }

    draw(time) {
      const gl = this.gl;
      this.resize();
      gl.useProgram(this.program);
      gl.bindBuffer(gl.ARRAY_BUFFER, this.buffer);

      const position = gl.getAttribLocation(this.program, 'a_position');
      gl.enableVertexAttribArray(position);
      gl.vertexAttribPointer(position, 2, gl.FLOAT, false, 0, 0);

      gl.activeTexture(gl.TEXTURE0);
      gl.bindTexture(gl.TEXTURE_2D, this.beforeTexture);
      gl.uniform1i(this.uniforms.before, 0);

      gl.activeTexture(gl.TEXTURE1);
      gl.bindTexture(gl.TEXTURE_2D, this.afterTexture || this.beforeTexture);
      gl.uniform1i(this.uniforms.after, 1);

      const redness = Math.max(Number(this.signals.redness_signal || 0), Number(this.signals.zone_cheek_redness || 0), Number(this.signals.barrier_stress_signal || 0) * 0.55);
      const spots = Math.max(Number(this.signals.dark_spot_signal || 0), Number(this.signals.zone_spot_concentration || 0), Number(this.signals.tone_unevenness_signal || 0) * 0.5);
      const shine = Math.max(Number(this.signals.shine_signal || 0), Number(this.signals.zone_t_shine || 0), Number(this.signals.oil_control_signal || 0) * 0.82);
      const texture = Math.max(Number(this.signals.texture_signal || 0), Number(this.signals.fine_texture_signal || 0), Number(this.signals.pores_proxy_signal || 0) * 0.65);
      const undertone = String(this.signals.undertone_proxy || 'neutral');
      const warmth = undertone === 'warm' ? 1 : undertone === 'olive' ? 0.55 : undertone === 'cool' ? -0.55 : 0;
      const beforeCover = this.coverScaleFor(this.beforeSize);
      const afterCover = this.coverScaleFor(this.afterSize);
      const intensityScale = this.intensityMode === 'visible' ? 1.08 : this.intensityMode === 'glow' ? 0.88 : 0.62;
      const makeupScale = this.intensityMode === 'visible' ? 0.76 : this.intensityMode === 'glow' ? 0.58 : 0.34;

      gl.uniform1f(this.uniforms.hasAfter, this.hasAfter ? 1 : 0);
      gl.uniform1f(this.uniforms.time, time || 0);
      gl.uniform1f(this.uniforms.intensity, Math.min(1, (0.42 + (redness * 0.16) + (spots * 0.12) + (texture * 0.1)) * intensityScale));
      gl.uniform1f(this.uniforms.shine, Math.min(1, shine));
      gl.uniform1f(this.uniforms.redness, Math.min(1, redness));
      gl.uniform1f(this.uniforms.spots, Math.min(1, spots));
      gl.uniform1f(this.uniforms.texture, Math.min(1, texture));
      gl.uniform1f(this.uniforms.warmth, warmth);
      gl.uniform1f(this.uniforms.makeup, this.hasAfter ? makeupScale : 0.3 * intensityScale);
      gl.uniform1f(this.uniforms.analysis, this.analysisActive ? 1 : 0);
      gl.uniform1f(this.uniforms.analysisProgress, this.analysisProgress);
      gl.uniform2f(this.uniforms.beforeCover, beforeCover[0], beforeCover[1]);
      gl.uniform2f(this.uniforms.afterCover, afterCover[0], afterCover[1]);

      gl.drawArrays(gl.TRIANGLES, 0, 6);
    }

    coverScaleFor(imageSize) {
      const canvasAspect = Math.max(this.canvas.width / Math.max(this.canvas.height, 1), 0.001);
      const imageAspect = Math.max(Number(imageSize.width || 1) / Math.max(Number(imageSize.height || 1), 1), 0.001);

      if (imageAspect > canvasAspect) {
        return [canvasAspect / imageAspect, 1];
      }

      return [1, imageAspect / canvasAspect];
    }

    resize() {
      const rect = this.canvas.getBoundingClientRect();
      const ratio = Math.min(window.devicePixelRatio || 1, 2);
      const width = Math.max(1, Math.round(rect.width * ratio));
      const height = Math.max(1, Math.round(rect.height * ratio));

      if (this.canvas.width !== width || this.canvas.height !== height) {
        this.canvas.width = width;
        this.canvas.height = height;
      }

      this.gl.viewport(0, 0, width, height);
    }

    createProgram() {
      const vertexShader = this.compileShader(this.gl.VERTEX_SHADER, `
        attribute vec2 a_position;
        varying vec2 v_uv;
        void main() {
          v_uv = a_position * 0.5 + 0.5;
          gl_Position = vec4(a_position, 0.0, 1.0);
        }
      `);
      const fragmentShader = this.compileShader(this.gl.FRAGMENT_SHADER, `
        precision mediump float;
        uniform sampler2D u_before;
        uniform sampler2D u_after;
        uniform float u_has_after;
        uniform float u_time;
        uniform float u_intensity;
        uniform float u_shine;
        uniform float u_redness;
        uniform float u_spots;
        uniform float u_texture;
        uniform float u_warmth;
        uniform float u_makeup;
        uniform float u_analysis;
        uniform float u_analysis_progress;
        uniform vec2 u_before_cover;
        uniform vec2 u_after_cover;
        varying vec2 v_uv;

        float luma(vec3 color) {
          return dot(color, vec3(0.2126, 0.7152, 0.0722));
        }

        float ellipse(vec2 uv, vec2 center, vec2 radius) {
          vec2 delta = (uv - center) / radius;
          return 1.0 - smoothstep(0.72, 1.0, dot(delta, delta));
        }

        float ellipseDistance(vec2 uv, vec2 center, vec2 radius) {
          vec2 delta = (uv - center) / radius;
          return dot(delta, delta);
        }

        float circlePulse(vec2 uv, vec2 center, float radius, float timeOffset) {
          float distanceToPoint = distance(uv, center);
          float core = 1.0 - smoothstep(radius * 0.42, radius, distanceToPoint);
          float ringRadius = radius + (0.024 * (0.5 + 0.5 * sin(u_time * 2.4 + timeOffset)));
          float ring = 1.0 - smoothstep(0.0, 0.018, abs(distanceToPoint - ringRadius));
          return max(core, ring * 0.72);
        }

        vec2 coverUv(vec2 uv, vec2 coverScale) {
          return ((uv - 0.5) * coverScale) + 0.5;
        }

        void main() {
          vec2 beforeUv = coverUv(v_uv, u_before_cover);
          vec2 afterUv = coverUv(v_uv, u_after_cover);
          vec4 beforeColor = texture2D(u_before, beforeUv);
          vec3 aiColor = texture2D(u_after, afterUv).rgb;
          float hasAfterImage = step(0.5, u_has_after);
          vec3 color = mix(beforeColor.rgb, aiColor, hasAfterImage);
          vec2 faceCenter = vec2(0.5, 0.48);
          vec2 faceRadius = vec2(0.37, 0.48);
          float faceDistance = ellipseDistance(v_uv, faceCenter, faceRadius);
          float skinMask = 1.0 - smoothstep(0.72, 1.0, faceDistance);
          float tone = luma(color);
          vec3 warmTint = vec3(1.0 + (u_warmth * 0.035), 0.992 + abs(u_warmth) * 0.012, 1.0 - (u_warmth * 0.025));
          color = mix(color, color * warmTint, skinMask * 0.34);

          float redExcess = max(color.r - max(color.g, color.b), 0.0);
          color.g += redExcess * (0.12 + u_redness * 0.22) * skinMask;
          color.b += redExcess * (0.06 + u_redness * 0.12) * skinMask;

          float shadow = smoothstep(0.34, 0.05, tone) * skinMask;
          color = mix(color, color + vec3(0.035, 0.026, 0.018), shadow * (0.28 + u_spots * 0.4));

          float faceGlow = ellipse(v_uv, vec2(0.5, 0.48), vec2(0.28, 0.36));
          float microGlow = sin((v_uv.x + v_uv.y + u_time * 0.08) * 34.0) * 0.004;
          color += vec3(0.018, 0.013, 0.01) * faceGlow * u_intensity;
          color += vec3(microGlow) * faceGlow * (1.0 - u_shine);

          float cheekLeft = ellipse(v_uv, vec2(0.35, 0.55), vec2(0.13, 0.09));
          float cheekRight = ellipse(v_uv, vec2(0.65, 0.55), vec2(0.13, 0.09));
          float lips = ellipse(v_uv, vec2(0.5, 0.73), vec2(0.13, 0.045));
          vec3 blush = u_warmth >= 0.0 ? vec3(1.0, 0.55, 0.42) : vec3(0.86, 0.46, 0.58);
          vec3 lipTint = u_warmth >= 0.0 ? vec3(0.78, 0.28, 0.2) : vec3(0.68, 0.22, 0.35);
          color = mix(color, mix(color, blush, 0.22), (cheekLeft + cheekRight) * 0.18 * u_makeup);
          color = mix(color, mix(color, lipTint, 0.34), lips * 0.28 * u_makeup);

          float textureSoftener = clamp((u_texture * 0.24) + 0.06, 0.06, 0.28) * skinMask;
          vec3 lifted = color + vec3(0.014, 0.01, 0.008);
          color = mix(color, lifted, textureSoftener);

          if (u_analysis > 0.01) {
            float scanX = mix(-0.12, 1.12, clamp(u_analysis_progress, 0.0, 1.0));
            float liveScanX = scanX + sin(u_time * 1.6) * 0.025;
            float scanBand = 1.0 - smoothstep(0.0, 0.13, abs(v_uv.x - liveScanX));
            float scanCore = 1.0 - smoothstep(0.0, 0.018, abs(v_uv.x - liveScanX));
            float faceEdge = 1.0 - smoothstep(0.0, 0.045, abs(faceDistance - 0.78));
            float retouchGlow = smoothstep(0.72, 1.0, 0.5 + (0.5 * sin((v_uv.x * 8.0) + (v_uv.y * 5.0) - (u_time * 1.2))));
            float contour = (0.55 + (0.45 * sin((faceDistance * 26.0) - (u_time * 2.3)))) * skinMask;
            float pointA = circlePulse(v_uv, vec2(0.43, 0.35), 0.035, 0.0);
            float pointB = circlePulse(v_uv, vec2(0.34, 0.55), 0.032, 1.6);
            float pointC = circlePulse(v_uv, vec2(0.61, 0.57), 0.032, 3.2);
            float pointMask = max(pointA, max(pointB, pointC));
            float processed = step(v_uv.x, clamp(u_analysis_progress + 0.08, 0.0, 1.0));
            vec3 analysisPink = vec3(1.0, 0.72, 0.8);
            vec3 analysisCream = vec3(1.0, 0.96, 0.9);
            float meshX = 1.0 - smoothstep(0.0, 0.012, abs(fract((v_uv.x * 8.0) + (sin(u_time * 0.8) * 0.04)) - 0.5));
            float meshY = 1.0 - smoothstep(0.0, 0.012, abs(fract((v_uv.y * 10.0) - (u_time * 0.05)) - 0.5));
            float mesh = max(meshX * 0.38, meshY * 0.28) * skinMask * processed;
            float sparkle = pow(max(0.0, sin((v_uv.x * 44.0) + (v_uv.y * 31.0) + (u_time * 2.2))), 18.0) * skinMask * processed;

            color = mix(color, color + analysisCream * 0.055, skinMask * processed * 0.48 * u_analysis);
            color = mix(color, analysisPink, faceEdge * 0.18 * u_analysis);
            color += analysisPink * scanBand * (0.08 + (0.1 * skinMask)) * u_analysis;
            color += vec3(1.0) * scanCore * 0.13 * u_analysis;
            color += analysisCream * retouchGlow * contour * processed * 0.012 * u_analysis;
            color += analysisPink * mesh * 0.028 * u_analysis;
            color += vec3(1.0, 0.92, 0.86) * sparkle * 0.09 * u_analysis;
            color = mix(color, analysisPink, pointMask * 0.36 * u_analysis);
          }

          color = clamp(color, 0.0, 1.0);
          gl_FragColor = vec4(color, beforeColor.a);
        }
      `);
      const program = this.gl.createProgram();

      this.gl.attachShader(program, vertexShader);
      this.gl.attachShader(program, fragmentShader);
      this.gl.linkProgram(program);

      if (!this.gl.getProgramParameter(program, this.gl.LINK_STATUS)) {
        throw new Error(this.gl.getProgramInfoLog(program) || 'No fue posible crear el preview WebGL');
      }

      return program;
    }

    compileShader(type, source) {
      const shader = this.gl.createShader(type);
      this.gl.shaderSource(shader, source);
      this.gl.compileShader(shader);

      if (!this.gl.getShaderParameter(shader, this.gl.COMPILE_STATUS)) {
        throw new Error(this.gl.getShaderInfoLog(shader) || 'No fue posible compilar el shader');
      }

      return shader;
    }
  }

  function renderAiVisual(ai, mode) {
    if (mode !== 'ai' || !compare) {
      return;
    }

    compare.classList.add('is-visible');
    const beforeSrc = beforeImage && beforeImage.src ? beforeImage.src : '';
    const afterSrc = ai && ai.after_image_data_uri ? ai.after_image_data_uri : '';

    if (afterSrc && afterImage) {
      afterImage.src = afterSrc;
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

    renderSkinPreview(beforeSrc, afterSrc, lastVisionSignals);
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
    status.classList.toggle('is-visible', Boolean(message));
    status.classList.toggle('is-success', Boolean(message) && !isError && /lista|agregada/i.test(message));
  }

  function setActiveStatus(message, isError = false) {
    const activeForm = forms.find((form) => !form.classList.contains('is-hidden')) || forms[0];
    setStatus(activeForm, message, isError);
  }

  function setAiNotes(message) {
    if (!aiNotes) {
      return;
    }

    const blocks = (Array.isArray(message) ? message : [message])
      .map((item) => String(item || '').trim())
      .filter(Boolean);

    aiNotes.innerHTML = '';

    if (!blocks.length) {
      return;
    }

    if (blocks.length === 1) {
      aiNotes.textContent = blocks[0];
      return;
    }

    blocks.forEach((text) => {
      const paragraph = document.createElement('p');
      paragraph.textContent = text;
      aiNotes.appendChild(paragraph);
    });
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

    if (diagnosisBars) {
      diagnosisBars.innerHTML = '';
    }

    updateDiagnosisSummary([], {}, 0, []);

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
    const assessment = ai && ai.assessment && typeof ai.assessment === 'object' ? ai.assessment : {};
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

    const diagnosticScores = normalizeDiagnosticScores(ai && ai.diagnostic_scores, profile, lastVisionSignals);
    renderDiagnosticBars(diagnosticScores);
    updateDiagnosisSummary(diagnosticScores, profile, confidence, needs);

    if (assessment.headline || assessment.summary) {
      notes.push([assessment.headline, assessment.summary].filter(Boolean).join('. '));
    }

    if (ai && Array.isArray(ai.notes) && ai.notes.length) {
      notes.push(ai.notes.join(' '));
    }

    if (ai && ai.after_description) {
      notes.push(`Simulación visual: ${ai.after_description}`);
    }

    if (!notes.length && needs.length) {
      notes.push('Cruzamos la foto, tus respuestas y el catálogo BSC para armar esta rutina.');
    }

    setAiNotes(notes);
  }

  function renderDiagnosticBars(scores) {
    if (!diagnosisBars) {
      return;
    }

    diagnosisBars.innerHTML = '';

    scores.forEach((item, index) => {
      const percent = Math.max(0, Math.min(100, Math.round(item.score * 100)));
      const row = document.createElement('div');
      row.className = 'bsc-skin-quiz__diagnosis-bar';
      row.setAttribute('data-score-key', item.key);
      row.setAttribute('data-score-state', percent >= 70 ? 'high' : percent >= 42 ? 'medium' : 'low');
      row.style.setProperty('--bsc-diagnosis-delay', `${index * 48}ms`);

      const header = document.createElement('div');
      header.className = 'bsc-skin-quiz__diagnosis-bar-header';

      const label = document.createElement('span');
      label.textContent = item.label;

      const value = document.createElement('strong');
      value.textContent = `${percent}%`;

      const hint = document.createElement('em');
      hint.textContent = item.hint;

      const track = document.createElement('div');
      track.className = 'bsc-skin-quiz__diagnosis-bar-track';

      const fill = document.createElement('span');
      fill.style.setProperty('--bsc-diagnosis-score', '0%');

      header.append(label, value);
      track.appendChild(fill);
      row.append(header, track, hint);
      diagnosisBars.appendChild(row);

      window.requestAnimationFrame(() => {
        fill.style.setProperty('--bsc-diagnosis-score', `${percent}%`);
      });
    });
  }

  function updateDiagnosisSummary(scores, profile, confidence, needs) {
    if (!diagnosisSummary) {
      return;
    }

    const sorted = Array.isArray(scores) ? [...scores].sort((left, right) => right.score - left.score) : [];
    const top = sorted[0] || null;
    const topThree = sorted.slice(0, 3);
    const fallbackFit = topThree.length
      ? topThree.reduce((sum, item) => sum + Number(item.score || 0), 0) / topThree.length
      : 0;
    const fit = Number(confidence || 0) > 0 ? Number(confidence) : fallbackFit;
    const hasSignals = Object.keys(lastVisionSignals || {}).length > 4;
    const skinType = profile && profile.skin_type ? labelFor(profile.skin_type) : '';
    const primary = top ? top.label : (Array.isArray(needs) && needs[0] ? labelFor(needs[0]) : 'Rutina');

    if (diagnosisFitScore) {
      diagnosisFitScore.textContent = fit > 0 ? `${Math.round(Math.max(0, Math.min(1, fit)) * 100)}%` : '--';
    }

    if (diagnosisPrimaryFocus) {
      diagnosisPrimaryFocus.textContent = primary;
    }

    if (diagnosisBaseNote) {
      diagnosisBaseNote.textContent = hasSignals && skinType ? `Foto + ${skinType}` : hasSignals ? 'Foto + quiz' : 'Quiz';
    }
  }

  function normalizeDiagnosticScores(scores, profile, signals) {
    const rawScores = scores && typeof scores === 'object' ? scores : {};
    const derivedScores = deriveClientDiagnosticScores(profile || {}, signals || {});

    return diagnosisScoreConfig.map((configItem) => ({
      ...configItem,
      score: scoreValue(rawScores[configItem.key], derivedScores[configItem.key] || 0)
    }));
  }

  function deriveClientDiagnosticScores(profile, signals) {
    const needs = Array.isArray(profile.needs) ? profile.needs : [];
    const skinType = String(profile.skin_type || '');
    const needBoost = (need, value = 0.72) => (needs.includes(need) ? value : 0);
    const typeBoost = (types, value = 0.58) => (types.includes(skinType) ? value : 0);

    return {
      sebum_balance: Math.max(maxSignal(signals, ['oil_control_signal', 'shine_signal', 'zone_t_shine', 'zone_forehead_shine', 'zone_nose_shine']), typeBoost(['grasa', 'mixta']), needBoost('acne', 0.52)),
      hydration_barrier: Math.max(maxSignal(signals, ['hydration_need_signal', 'dryness_signal', 'barrier_stress_signal']), typeBoost(['seca', 'sensible']), needBoost('hidratacion'), needBoost('barrera')),
      tone_evenness: Math.max(maxSignal(signals, ['tone_unevenness_signal', 'dark_spot_signal', 'pigment_spot_proxy', 'spot_cluster_signal']), needBoost('manchas'), needBoost('glow', 0.46)),
      calmness: Math.max(maxSignal(signals, ['redness_signal', 'zone_cheek_redness', 'red_cluster_signal', 'barrier_stress_signal']), typeBoost(['sensible']), needBoost('acne', 0.45)),
      texture_refinement: Math.max(maxSignal(signals, ['texture_signal', 'fine_texture_signal', 'pores_proxy_signal', 'zone_chin_texture']), needBoost('glow', 0.42)),
      spf_priority: Math.max(maxSignal(signals, ['spf_priority_signal', 'tone_unevenness_signal', 'pigment_spot_proxy']), needBoost('protector-solar'), needBoost('manchas', 0.6))
    };
  }

  function maxSignal(signals, keys) {
    return keys.reduce((max, key) => Math.max(max, Number(signals[key] || 0)), 0);
  }

  function scoreValue(value, fallback) {
    const raw = value && typeof value === 'object' ? value.score || value.value : value;
    const number = Number(raw);

    if (!Number.isFinite(number)) {
      return Math.max(0, Math.min(1, Number(fallback) || 0));
    }

    return Math.max(0, Math.min(1, number));
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

  function initSharedRoutine() {
    const routine = config.sharedRoutine;
    if (!routine || !Array.isArray(routine.bundles) || !routine.bundles.length) {
      return;
    }

    currentRunId = Number(routine.run_id || 0);
    setMode(routine.mode === 'ai' ? 'ai' : 'normal');
    renderBundles(routine.bundles);
    renderAiVisual(routine.ai || null, routine.mode === 'ai' ? 'ai' : 'normal');
    setActiveStatus('Rutina compartida cargada.');
  }

  function initAccountDeletion() {
    document.addEventListener('click', async (event) => {
      const deleteButton = event.target.closest('[data-bsc-delete-run]');
      if (!deleteButton) {
        return;
      }

      const runId = deleteButton.getAttribute('data-bsc-delete-run') || '';
      deleteButton.disabled = true;
      const previousText = deleteButton.textContent;
      deleteButton.textContent = 'Eliminando...';

      try {
        const payload = new FormData();
        payload.set('action', 'bsc_skin_quiz_delete_run');
        payload.set('nonce', nonce);
        payload.set('run_id', runId);
        await postForm(payload);
        const item = deleteButton.closest('[data-bsc-run-id]');
        if (item) {
          item.remove();
        }
      } catch (error) {
        deleteButton.disabled = false;
        deleteButton.textContent = previousText;
        const status = document.querySelector('[data-bsc-skin-quiz-status]');
        if (status) {
          status.textContent = error.message || 'No fue posible eliminar este historial.';
          status.classList.add('is-error');
        }
      }
    });
  }

  function initQuizVariant() {
    if (!config.abTesting || !config.abTesting.enabled) {
      document.cookie = 'bsc_skin_quiz_variant=control; path=/; max-age=2592000; SameSite=Lax';
      return 'control';
    }

    try {
      const stored = window.localStorage ? window.localStorage.getItem('bscSkinQuizVariant') : '';
      if (stored) {
        document.cookie = `bsc_skin_quiz_variant=${encodeURIComponent(stored)}; path=/; max-age=2592000; SameSite=Lax`;
        return stored;
      }
      const variants = ['control', 'after-off', 'cta-buy', 'routine-short'];
      const selected = variants[Math.floor(Math.random() * variants.length)];
      if (window.localStorage) {
        window.localStorage.setItem('bscSkinQuizVariant', selected);
      }
      document.cookie = `bsc_skin_quiz_variant=${encodeURIComponent(selected)}; path=/; max-age=2592000; SameSite=Lax`;
      return selected;
    } catch (error) {
      return 'control';
    }
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

    const storedAi = routine.ai && typeof routine.ai === 'object' ? { ...routine.ai } : null;
    if (storedAi && storedAi.after_image_data_uri) {
      delete storedAi.after_image_data_uri;
    }

    const stored = {
      mode: routine.mode || 'normal',
      bundles: routine.bundles,
      ai: storedAi,
      run_id: routine.run_id || currentRunId,
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
    payload.set('meta', JSON.stringify({
      ...meta,
      variant: quizVariant,
      run_id: meta.run_id || currentRunId || ''
    }));

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
