const { expect, test } = require('@playwright/test');
const { expectsStorefront, routes } = require('../helpers/env');
const {
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

    await openFirstProductFromCategory(page, routes.category);
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

  test('checkout', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for public visual baselines');

    await gotoAndStabilize(page, routes.checkout);

    await expect(page).toHaveScreenshot('checkout.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });
});
