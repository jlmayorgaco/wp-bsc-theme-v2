const { expect, test } = require('@playwright/test');
const { expectsStorefront, routes } = require('../helpers/env');
const {
  gotoAndStabilize,
  openFirstProductFromCategory,
} = require('../helpers/ui');

test.describe('BSC smoke', () => {
  test('home loads approved shell', async ({ page }) => {
    await gotoAndStabilize(page, routes.home);

    await expect(page.locator('body')).toBeVisible();

    if (expectsStorefront()) {
      await expect(
        page.locator('header, .bsc__header, .site-header').first()
      ).toBeVisible();
      return;
    }

    await expect(page.locator('.coming-soon-container').first()).toBeVisible();
  });

  test('category page renders product cards', async ({ page }) => {
    await gotoAndStabilize(page, routes.category);

    if (expectsStorefront()) {
      await expect(page.locator('.bsc__product-card').first()).toBeVisible();
      return;
    }

    await expect(page.locator('.coming-soon-container').first()).toBeVisible();
  });

  test('first PDP opens from category', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for PDP smoke');

    await openFirstProductFromCategory(page, routes.category);

    await expect(
      page.locator(
        '.woocommerce-product-gallery, .product, .bsc__product-gallery'
      ).first()
    ).toBeVisible();
  });

  test('checkout page renders', async ({ page }) => {
    await gotoAndStabilize(page, routes.checkout);

    if (expectsStorefront()) {
      await expect(
        page.locator('form.checkout, .bsc__checkout, .woocommerce-checkout').first()
      ).toBeVisible();
      return;
    }

    await expect(page.locator('.coming-soon-container').first()).toBeVisible();
  });

  test('account route responds', async ({ page }) => {
    await gotoAndStabilize(page, routes.account);

    await expect(page.locator('body')).toBeVisible();
    await expect(page).toHaveURL(/mi-cuenta|my-account|login/i);
  });
});
