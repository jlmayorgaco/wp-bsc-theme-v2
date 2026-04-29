const { expect } = require('@playwright/test');

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

async function gotoAndStabilize(page, path) {
  await page.goto(path, { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('load');

  try {
    await page.waitForLoadState('networkidle', { timeout: 7_500 });
  } catch (error) {
    // Some WordPress pages keep background activity; best-effort only.
  }

  await disableMotion(page);
  await waitForImages(page);
  await primeFullPage(page);
  await page.evaluate(() => window.scrollTo(0, 0));
}

async function openFirstProductFromCategory(page, categoryPath) {
  await gotoAndStabilize(page, categoryPath);

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
  await gotoAndStabilize(page, categoryPath);
  await interactWithPrimaryCardAddToCart(page, projectName);
}

async function loginFromAccount(page, loginPath, accountPath, usernameOrEmail, password) {
  const redirectTarget = accountPath || loginPath || '/';
  const wpLoginUrl =
    '/wp-login.php?redirect_to=' + encodeURIComponent(redirectTarget);

  await page.goto(wpLoginUrl, { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('load');

  const usernameField = page.locator('#user_login').first();
  const passwordField = page.locator('#user_pass').first();
  const loginButton = page.locator('#wp-submit').first();

  if (!(await usernameField.count()) || !(await passwordField.count()) || !(await loginButton.count())) {
    return false;
  }

  await usernameField.fill(usernameOrEmail);
  await passwordField.fill(password);
  await Promise.all([
    page
      .waitForURL(
        (url) => !url.pathname.startsWith('/wp-login.php'),
        { timeout: 15_000 }
      )
      .catch(() => null),
    loginButton.click(),
  ]);

  try {
    await page.waitForLoadState('networkidle', { timeout: 10_000 });
  } catch (error) {
    // Best-effort only.
  }

  if (accountPath) {
    for (let attempt = 0; attempt < 2; attempt += 1) {
      await gotoAndStabilize(page, accountPath);

      if (!page.url().includes('/login/') && !page.url().includes('/wp-login.php')) {
        const accountContent = page.locator('.woocommerce-MyAccount-content').first();

        if (await accountContent.count()) {
          await expect(accountContent).toBeVisible();
        }

        await waitForImages(page);
        return true;
      }

      await page.waitForTimeout(500);
    }

    throw new Error('Account login did not complete successfully.');
  }

  await waitForImages(page);
  return true;
}

async function loginToWpAdmin(page, username, password) {
  for (let attempt = 0; attempt < 2; attempt += 1) {
    await page.goto('/wp-login.php', { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('load');

    if (page.url().includes('/wp-admin') && (await page.locator('#wpadminbar').count())) {
      return true;
    }

    const usernameField = page.locator('#user_login').first();
    const passwordField = page.locator('#user_pass').first();
    const submitButton = page.locator('#wp-submit').first();

    if (!(await usernameField.count()) || !(await passwordField.count())) {
      continue;
    }

    await usernameField.fill(username);
    await passwordField.fill(password);
    await submitButton.click();
    await page.waitForLoadState('load');

    try {
      await page.waitForLoadState('networkidle', { timeout: 10_000 });
    } catch (error) {
      // Best-effort only.
    }

    if (page.url().includes('/wp-admin') && (await page.locator('#wpadminbar').count())) {
      return true;
    }
  }

  return false;
}

module.exports = {
  ensureCheckoutReadyFromCategory,
  gotoAndStabilize,
  interactWithPrimaryCardAddToCart,
  loginFromAccount,
  loginToWpAdmin,
  openFirstProductFromCategory,
  primeFullPage,
};
