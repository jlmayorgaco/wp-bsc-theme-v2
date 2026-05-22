const { expect } = require('@playwright/test');

const TRANSIENT_ERROR_MARKERS = [
  '502 Bad Gateway',
  '504 Gateway Timeout',
  'Error establishing a database connection',
  'There has been a critical error on this website.',
];

async function disableMotion(page) {
  await page.addStyleTag({
    content: `
      *,
      *::before,
      *::after {
        animation-duration: 0s !important;
        animation-delay: 0s !important;
        transition-duration: 0s !important;
        transition-delay: 0s !important;
        scroll-behavior: auto !important;
      }
    `,
  });
}

async function waitForImages(page) {
  await page.evaluate(async () => {
    const images = Array.from(document.images || []);
    await Promise.all(
      images.map((img) => {
        const rect = img.getBoundingClientRect();
        const isNearViewport =
          rect.bottom >= -300 && rect.top <= window.innerHeight + 300;

        if (img.loading === 'lazy' && !isNearViewport) {
          return Promise.resolve();
        }

        if (img.complete) {
          return Promise.resolve();
        }

        return new Promise((resolve) => {
          const done = () => resolve();
          img.addEventListener('load', done, { once: true });
          img.addEventListener('error', done, { once: true });
        });
      })
    );
  });
}

async function waitForStableDocumentHeight(page, samples = 3, intervalMs = 120) {
  let previousHeight = -1;
  let stableSamples = 0;

  while (stableSamples < samples) {
    const currentHeight = await page.evaluate(() =>
      Math.max(
        document.body?.scrollHeight || 0,
        document.documentElement?.scrollHeight || 0
      )
    );

    if (currentHeight === previousHeight) {
      stableSamples += 1;
    } else {
      stableSamples = 0;
      previousHeight = currentHeight;
    }

    await page.waitForTimeout(intervalMs);
  }
}

async function primeFullPage(page) {
  const viewport = page.viewportSize();
  const viewportHeight = viewport?.height || 900;
  const documentHeight = await page.evaluate(() =>
    Math.max(
      document.body?.scrollHeight || 0,
      document.documentElement?.scrollHeight || 0
    )
  );

  for (let top = 0; top < documentHeight; top += Math.max(200, Math.floor(viewportHeight * 0.8))) {
    await page.evaluate((scrollTop) => window.scrollTo(0, scrollTop), top);
    await page.waitForTimeout(75);
    await waitForImages(page);
  }

  await page.evaluate(() => window.scrollTo(0, 0));
  await page.waitForTimeout(75);
  await waitForImages(page);
}

async function waitForSettledLoad(page) {
  await page.waitForLoadState('load');

  try {
    await page.waitForLoadState('networkidle', { timeout: 7_500 });
  } catch (error) {
    // Some WordPress pages keep background activity; best-effort only.
  }
}

async function isTransientGatewayPage(page) {
  const body = page.locator('body').first();

  if (!(await body.count())) {
    return false;
  }

  const bodyId = await body.getAttribute('id').catch(() => '');
  if (bodyId === 'error-page') {
    return true;
  }

  const errorPage = page.locator('#error-page').first();
  if ((await errorPage.count()) > 0) {
    return true;
  }

  const bodyText = await body.innerText().catch(() => '');

  return TRANSIENT_ERROR_MARKERS.some((marker) => bodyText.includes(marker));
}

async function gotoAndStabilize(page, path, options = {}) {
  const maxAttempts = Math.max(1, options.maxAttempts || 3);
  const shouldPrimePage = options.primePage !== false;
  const shouldWaitForImages = options.waitForImages !== false;
  let lastError = null;

  for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
    try {
      await page.goto(path, { waitUntil: 'domcontentloaded' });
      await waitForSettledLoad(page);

      if (await isTransientGatewayPage(page)) {
        throw new Error(`Transient gateway page detected at ${path}`);
      }

      await disableMotion(page);
      if (shouldWaitForImages) {
        await waitForImages(page);
      }

      if (shouldPrimePage) {
        await primeFullPage(page);
        await page.evaluate(() => window.scrollTo(0, 0));
      }

      return;
    } catch (error) {
      lastError = error;

      if (attempt >= maxAttempts) {
        break;
      }

      await page.waitForTimeout(750 * attempt);
    }
  }

  throw lastError || new Error(`Navigation failed for ${path}`);
}

async function hasVisibleLoginForm(page) {
  const usernameField = page.locator('#user_login').first();
  const passwordField = page.locator('#user_pass').first();
  const submitButton = page.locator('#wp-submit').first();

  return (
    (await usernameField.count()) > 0 &&
    (await passwordField.count()) > 0 &&
    (await submitButton.count()) > 0
  );
}

async function submitLoginForm(page, usernameOrEmail, password, redirectTarget = '') {
  const usernameField = page.locator('#user_login').first();
  const passwordField = page.locator('#user_pass').first();
  const loginButton = page.locator('#wp-submit').first();
  const redirectField = page.locator('input[name="redirect_to"]').first();

  await usernameField.fill(usernameOrEmail);
  await passwordField.fill(password);

  if (redirectTarget && (await redirectField.count())) {
    await redirectField.evaluate((node, value) => {
      node.value = value;
      node.setAttribute('value', value);
    }, redirectTarget);
  }

  await Promise.all([
    page
      .waitForURL(
        (url) =>
          !url.pathname.startsWith('/wp-login.php') &&
          !url.pathname.endsWith('/login/'),
        { timeout: 15_000 }
      )
      .catch(() => null),
    loginButton.click(),
  ]);

  await waitForSettledLoad(page);
}

async function isAuthenticatedAccountView(page) {
  if (page.url().includes('/wp-login.php') || page.url().includes('/login/')) {
    return false;
  }

  if ((await isTransientGatewayPage(page))) {
    return false;
  }

  const accountContent = page.locator('.woocommerce-MyAccount-content').first();
  const logoutLink = page.locator('a[href*="customer-logout"], a[href*="logout"]').first();

  if ((await accountContent.count()) > 0) {
    await expect(accountContent).toBeVisible();
    return true;
  }

  return (await logoutLink.count()) > 0;
}

async function gotoProductGridCategory(page, categoryPaths) {
  const candidates = Array.isArray(categoryPaths)
    ? categoryPaths
    : [categoryPaths];

  const uniqueCandidates = candidates.filter(
    (candidate, index) => candidate && candidates.indexOf(candidate) === index
  );

  for (const candidate of uniqueCandidates) {
    await gotoAndStabilize(page, candidate);

    if (await page.locator('.bsc__product-card').count()) {
      await page.waitForFunction(() => {
        const cards = Array.from(document.querySelectorAll('.bsc__product-card'));
        if (!cards.length) {
          return false;
        }

        const relevantImages = cards
          .slice(0, Math.min(cards.length, 8))
          .flatMap((card) => Array.from(card.querySelectorAll('img')));

        return relevantImages.every((img) => img.complete && img.naturalHeight > 0);
      });
      await waitForStableDocumentHeight(page);
      return candidate;
    }
  }

  throw new Error('No product-grid category route rendered BSC product cards.');
}

async function openFirstProductFromCategory(page, categoryPath) {
  await gotoProductGridCategory(page, categoryPath);

  const productLink = page
    .locator('.bsc__product-card .card__title a, .bsc__product-card .card__images')
    .first();

  await productLink.waitFor({ state: 'visible' });
  await productLink.click();
  await page.waitForLoadState('domcontentloaded');
  await page.waitForLoadState('load');

  try {
    await page.waitForLoadState('networkidle', { timeout: 7_500 });
  } catch (error) {
    // Best-effort only.
  }

  await disableMotion(page);
  await waitForImages(page);
  await primeFullPage(page);
  await page.evaluate(() => window.scrollTo(0, 0));
}

async function interactWithPrimaryCardAddToCart(page, projectName) {
  const productCard = page.locator('.bsc__product-card').first();
  const addButton = productCard.locator('.bsc__button-add-to-cart').first();

  await expect(addButton).toBeVisible();
  await addButton.scrollIntoViewIfNeeded();

  if (projectName === 'mobile' || projectName === 'tablet') {
    await addButton.tap();
  } else {
    await addButton.click();
  }

  const controls = productCard.locator('.bsc__quantity-controls').first();
  await expect(controls).toBeVisible({ timeout: 15000 });
  await expect(controls.locator('.bsc__qty-value').first()).toHaveText('1');

  return {
    productCard,
    addButton,
    controls,
  };
}

async function selectFirstNonEmptyOption(page, selector) {
  const field = page.locator(selector).first();
  await expect(field).toBeVisible();

  const currentValue = await field.inputValue().catch(() => '');
  if (currentValue) {
    return currentValue;
  }

  const optionValue = await field.evaluate((node) => {
    const option = Array.from(node.options || []).find((candidate) => candidate.value && !candidate.disabled);
    return option ? option.value : '';
  });

  if (!optionValue) {
    throw new Error(`No selectable option found for ${selector}`);
  }

  await field.selectOption(optionValue);
  return optionValue;
}

async function selectCheckoutBillingDestination(page) {
  await selectFirstNonEmptyOption(page, '#billing_state');

  await page.waitForFunction(() => {
    const city = document.querySelector('#billing_city');
    return Boolean(
      city &&
      !city.disabled &&
      Array.from(city.options || []).some((option) => option.value && !option.disabled)
    );
  });

  await selectFirstNonEmptyOption(page, '#billing_city');

  const postcode = page.locator('#billing_postcode').first();
  if ((await postcode.count()) > 0) {
    await postcode.fill('110111');
  }

  const address = page.locator('#billing_address_1').first();
  if ((await address.count()) > 0) {
    await address.fill('Calle 123 #45-67');
  }

  await page.evaluate(() => new Promise((resolve) => {
    const $ = window.jQuery;

    if (!$ || !$('form[name="checkout"]').length) {
      window.setTimeout(resolve, 250);
      return;
    }

    let settled = false;
    const finish = () => {
      if (settled) {
        return;
      }

      settled = true;
      window.setTimeout(resolve, 250);
    };

    $(document.body).one('updated_checkout', finish);
    $(document.body).trigger('update_checkout');
    window.setTimeout(finish, 4000);
  }));
}

async function applyCheckoutCoupon(page, couponCode) {
  const couponInput = page.locator('#bsc__coupon-input').first();
  const applyButton = page.locator('#apply_coupon').first();

  await expect(couponInput).toBeVisible();
  await expect(applyButton).toBeVisible();

  await couponInput.fill(couponCode);
  const applyResponse = page.waitForResponse(async (response) => {
    if (!response.url().includes('/wp-admin/admin-ajax.php')) {
      return false;
    }

    const postData = response.request().postData() || '';
    if (!postData.includes('action=apply_coupon')) {
      return false;
    }

    try {
      const payload = await response.json();
      return Boolean(payload?.success);
    } catch (error) {
      return response.ok();
    }
  });

  const couponsResponse = page.waitForResponse(async (response) => {
    if (!response.url().includes('/wp-admin/admin-ajax.php')) {
      return false;
    }

    const postData = response.request().postData() || '';
    if (!postData.includes('action=get_applied_coupons')) {
      return false;
    }

    try {
      const payload = await response.json();
      return Boolean(payload?.success);
    } catch (error) {
      return response.ok();
    }
  });

  await applyButton.click();
  await applyResponse;
  await couponsResponse;
}

async function mutateCheckoutCouponDirect(page, action, couponCode) {
  const response = await page.evaluate(async ({ action, couponCode }) => {
    const params = new URLSearchParams();
    params.set('action', action);
    params.set('nonce', window.bsc_ajax?.nonce || '');
    params.set('coupon_code', couponCode);

    const result = await fetch(window.bsc_ajax.ajax_url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      },
      credentials: 'same-origin',
      body: params.toString(),
    });

    return result.json();
  }, { action, couponCode });

  if (!response?.success) {
    throw new Error(`Coupon mutation failed for ${action}`);
  }

  await page.evaluate(() => new Promise((resolve) => {
    const $ = window.jQuery;

    if (!$) {
      if (typeof window.refreshReviewSummary === 'function') {
        window.refreshReviewSummary();
      }
      window.setTimeout(resolve, 400);
      return;
    }

    const finish = () => {
      if (typeof window.refreshReviewSummary === 'function') {
        const request = window.refreshReviewSummary();
        if (request && typeof request.always === 'function') {
          request.always(() => resolve());
          return;
        }
      }

      window.setTimeout(resolve, 400);
    };

    if ($('form[name=\"checkout\"]').length) {
      $(document.body).one('updated_checkout', finish);
      $(document.body).trigger('update_checkout');
      window.setTimeout(finish, 4000);
      return;
    }

    finish();
  }));

  return response;
}

async function applyCheckoutCouponDirect(page, couponCode) {
  return mutateCheckoutCouponDirect(page, 'apply_coupon', couponCode);
}

async function removeCheckoutCoupon(page, couponCode) {
  const couponItem = page.locator('.applied-coupon-item').filter({
    hasText: new RegExp(couponCode, 'i'),
  }).first();

  await expect(couponItem).toBeVisible();

  const removeResponse = page.waitForResponse(async (response) => {
    if (!response.url().includes('/wp-admin/admin-ajax.php')) {
      return false;
    }

    const postData = response.request().postData() || '';
    if (!postData.includes('action=remove_coupon')) {
      return false;
    }

    try {
      const payload = await response.json();
      return Boolean(payload?.success);
    } catch (error) {
      return response.ok();
    }
  });

  const couponsResponse = page.waitForResponse(async (response) => {
    if (!response.url().includes('/wp-admin/admin-ajax.php')) {
      return false;
    }

    const postData = response.request().postData() || '';
    if (!postData.includes('action=get_applied_coupons')) {
      return false;
    }

    try {
      const payload = await response.json();
      return Boolean(payload?.success);
    } catch (error) {
      return response.ok();
    }
  });

  await couponItem.locator('.remove-coupon').click();
  await removeResponse;
  await couponsResponse;
}

async function removeCheckoutCouponDirect(page, couponCode) {
  return mutateCheckoutCouponDirect(page, 'remove_coupon', couponCode);
}

async function ensureCheckoutReadyFromCategory(page, categoryPath, projectName) {
  await gotoProductGridCategory(page, categoryPath);
  await interactWithPrimaryCardAddToCart(page, projectName);
}

async function loginFromAccount(page, loginPath, accountPath, usernameOrEmail, password) {
  const redirectTarget = accountPath || loginPath || '/';
  const wpLoginUrl =
    '/wp-login.php?redirect_to=' + encodeURIComponent(redirectTarget);

  for (let attempt = 0; attempt < 3; attempt += 1) {
    await gotoAndStabilize(page, wpLoginUrl, {
      primePage: false,
      waitForImages: false,
    });

    if (!(await hasVisibleLoginForm(page))) {
      return false;
    }

    await submitLoginForm(page, usernameOrEmail, password, redirectTarget);

    if (accountPath) {
      await gotoAndStabilize(page, accountPath);

      if (await isAuthenticatedAccountView(page)) {
        await waitForImages(page);
        return true;
      }
    } else {
      await waitForImages(page);
      return true;
    }

    if (loginPath) {
      await gotoAndStabilize(page, loginPath, {
        primePage: false,
        waitForImages: false,
      });

      if (await hasVisibleLoginForm(page)) {
        await submitLoginForm(page, usernameOrEmail, password, redirectTarget);

        if (accountPath) {
          await gotoAndStabilize(page, accountPath);

          if (await isAuthenticatedAccountView(page)) {
            await waitForImages(page);
            return true;
          }
        } else {
          await waitForImages(page);
          return true;
        }
      }
    }

    await page.waitForTimeout(500 * (attempt + 1));
  }

  throw new Error('Account login did not complete successfully.');
}

async function loginToWpAdmin(page, username, password) {
  for (let attempt = 0; attempt < 3; attempt += 1) {
    await gotoAndStabilize(page, '/wp-login.php', {
      primePage: false,
      waitForImages: false,
    });

    if (page.url().includes('/wp-admin') && (await page.locator('#wpadminbar').count())) {
      return true;
    }

    if (!(await hasVisibleLoginForm(page))) {
      continue;
    }

    await submitLoginForm(page, username, password, '/wp-admin/');

    if (page.url().includes('/wp-admin') && (await page.locator('#wpadminbar').count())) {
      return true;
    }
  }

  return false;
}

async function openWpAdminPage(page, adminFixture, path, verifyReady, options = {}) {
  const maxAttempts = Math.max(1, options.maxAttempts || 4);
  let lastError = null;

  for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
    try {
      const loggedIn = await loginToWpAdmin(
        page,
        adminFixture.username,
        adminFixture.password
      );

      expect(loggedIn).toBeTruthy();

      await gotoAndStabilize(page, path, {
        maxAttempts: 7,
        primePage: false,
        waitForImages: false,
      });

      if (page.url().includes('/wp-login.php')) {
        throw new Error(`Admin page redirected to login: ${path}`);
      }

      await verifyReady(page);
      return;
    } catch (error) {
      lastError = error;

      if (attempt >= maxAttempts) {
        break;
      }

      await page.goto('about:blank').catch(() => null);
      await page.waitForTimeout(500 * attempt);
    }
  }

  throw lastError || new Error(`Admin page did not stabilize: ${path}`);
}

async function openWpAdminPopup(page, triggerSelector, verifyReady, options = {}) {
  const maxAttempts = Math.max(1, options.maxAttempts || 3);
  let lastError = null;

  for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
    let popup = null;

    try {
      const popupPromise = page.waitForEvent('popup');
      await page.locator(triggerSelector).click();
      popup = await popupPromise;

      await popup.waitForURL((url) => url.toString() !== 'about:blank', {
        timeout: 10_000,
      });
      await waitForSettledLoad(popup);

      if (popup.url().includes('/wp-login.php')) {
        throw new Error(`Admin popup redirected to login: ${popup.url()}`);
      }

      if (await isTransientGatewayPage(popup)) {
        throw new Error(`Transient admin popup detected: ${popup.url()}`);
      }

      await disableMotion(popup);

      await verifyReady(popup);
      return popup;
    } catch (error) {
      lastError = error;

      if (popup) {
        await popup.close().catch(() => null);
      }

      if (attempt >= maxAttempts) {
        break;
      }

      await page.waitForTimeout(500 * attempt);
    }
  }

  throw lastError || new Error(`Admin popup did not stabilize for ${triggerSelector}`);
}

module.exports = {
  applyCheckoutCoupon,
  applyCheckoutCouponDirect,
  ensureCheckoutReadyFromCategory,
  gotoAndStabilize,
  gotoProductGridCategory,
  interactWithPrimaryCardAddToCart,
  loginFromAccount,
  loginToWpAdmin,
  openWpAdminPage,
  openWpAdminPopup,
  openFirstProductFromCategory,
  primeFullPage,
  removeCheckoutCoupon,
  removeCheckoutCouponDirect,
  selectCheckoutBillingDestination,
};
