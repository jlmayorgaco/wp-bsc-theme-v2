const { expect, test } = require('@playwright/test');
const { expectsStorefront, routes } = require('../helpers/env');
const {
  ensureCheckoutReadyFromCategory,
  gotoAndStabilize,
  openFirstProductFromCategory,
} = require('../helpers/ui');

test.describe('BSC visual baseline - public pages', () => {
  test('home', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for public visual baselines');

    await gotoAndStabilize(page, routes.home);

    await expect(page).toHaveScreenshot('home.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('category', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for public visual baselines');

    await gotoAndStabilize(page, routes.category);

    await expect(page).toHaveScreenshot('category.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('product detail', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for public visual baselines');

    if (routes.product) {
      await gotoAndStabilize(page, routes.product);
    } else {
      await openFirstProductFromCategory(page, routes.category);
    }

    await page.addStyleTag({
      content: `
        .bsc__product-recommendations {
          display: none !important;
        }
      `,
    });

    await expect(page).toHaveScreenshot('product-detail.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('checkout', async ({ page }, testInfo) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for public visual baselines');

    await ensureCheckoutReadyFromCategory(page, routes.category, testInfo.project.name);
    await gotoAndStabilize(page, routes.checkout);

    await expect(page).toHaveScreenshot('checkout.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('contact us', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for public visual baselines');

    await gotoAndStabilize(page, routes.contact);

    await expect(page).toHaveScreenshot('contact-us.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('login', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for public visual baselines');

    await gotoAndStabilize(page, routes.login);

    await expect(page).toHaveScreenshot('login.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('register', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for public visual baselines');

    await gotoAndStabilize(page, routes.register);

    await expect(page).toHaveScreenshot('register.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('bubble creators', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for public visual baselines');

    await gotoAndStabilize(page, routes.bubbleCreators);

    await expect(page).toHaveScreenshot('bubble-creators.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });
});
