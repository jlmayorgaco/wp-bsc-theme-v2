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

async function loginFromAccount(page, accountPath, email, password) {
  await gotoAndStabilize(page, accountPath);

  const usernameField = page.locator('#username, input[name="username"]').first();
  const passwordField = page.locator('#password, input[name="password"]').first();
  const loginButton = page
    .locator('button[name="login"], button[type="submit"], input[name="login"]')
    .first();

  if (!(await usernameField.count()) || !(await passwordField.count())) {
    return false;
  }

  await usernameField.fill(email);
  await passwordField.fill(password);
  await loginButton.click();

  try {
    await page.waitForLoadState('networkidle', { timeout: 10_000 });
  } catch (error) {
    // Best-effort only.
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
  gotoAndStabilize,
  loginFromAccount,
  loginToWpAdmin,
  openFirstProductFromCategory,
  primeFullPage,
};
