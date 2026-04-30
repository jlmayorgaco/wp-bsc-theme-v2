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
};
