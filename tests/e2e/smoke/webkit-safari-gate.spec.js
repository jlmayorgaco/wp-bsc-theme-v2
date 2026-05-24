const { expect, test } = require('@playwright/test');
const {
  auth,
  hasAccountAuth,
  routes,
} = require('../helpers/env');
const {
  gotoAndStabilize,
  loginFromAccount,
  openFirstProductFromCategory,
  watchConsoleErrors,
} = require('../helpers/ui');

test.describe('Safari/WebKit release gate @webkit', () => {
  test('account edit controls keep usable Safari dimensions', async ({ page }) => {
    test.skip(!hasAccountAuth(), 'Account fixture is required for Safari account gate.');

    const consoleWatcher = watchConsoleErrors(page);
    await loginFromAccount(page, routes.login, routes.accountEdit, auth.username || auth.email, auth.password);
    await gotoAndStabilize(page, routes.accountEdit, { primePage: false });

    const form = page.locator('.bsc__account-form').first();
    await expect(form).toBeVisible();

    for (const selector of ['#account_birthday', '#account_skin_type', '#account_sensitivity']) {
      const field = page.locator(selector).first();
      await expect(field).toBeVisible();

      const metrics = await field.evaluate((node) => {
        const style = window.getComputedStyle(node);
        const rect = node.getBoundingClientRect();

        return {
          height: rect.height,
          lineHeight: parseFloat(style.lineHeight) || 0,
          fontSize: parseFloat(style.fontSize) || 0,
        };
      });

      expect(metrics.height).toBeGreaterThanOrEqual(40);
      expect(metrics.lineHeight).toBeGreaterThanOrEqual(18);
      expect(metrics.fontSize).toBeGreaterThanOrEqual(14);
    }

    await expect(page.locator('.bsc__account-grid').first()).toBeVisible();
    consoleWatcher.assertNoErrors();
  });

  test('product, cart and checkout critical flow loads in WebKit', async ({ page }) => {
    const consoleWatcher = watchConsoleErrors(page, {
      allowed: ['Prefetch request denied: URL must be secure (HTTPS)'],
    });

    await openFirstProductFromCategory(page, [routes.categoryGrid, routes.category]);
    await expect(page.locator('.bsc__product-layout').first()).toBeVisible();
    await expect(page.locator('.bsc__button-add-to-cart').first()).toBeVisible();

    await gotoAndStabilize(page, '/cart/', { primePage: false });
    await expect(
      page.locator('.woocommerce-cart-form, .bsc__cart-empty, .bsc__product-recommendations').first()
    ).toBeVisible();

    await gotoAndStabilize(page, routes.checkout, { primePage: false });

    await expect(
      page.locator('.bsc__checkout-main, .bsc__cart-empty, form.checkout').first()
    ).toBeVisible();
    consoleWatcher.assertNoErrors();
  });
});
