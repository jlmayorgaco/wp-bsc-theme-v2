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

module.exports = {
  gotoAndStabilize,
  loginFromAccount,
  openFirstProductFromCategory,
};
