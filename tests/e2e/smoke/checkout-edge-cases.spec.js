const { expect, test } = require('@playwright/test');
const { expectsStorefront, routes } = require('../helpers/env');
const {
  ensureCheckoutReadyFromCategory,
  gotoAndStabilize,
} = require('../helpers/ui');

test.describe('BSC checkout edge cases', () => {
  test('empty checkout shows the empty-cart route instead of a broken form', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for checkout edge coverage');

    await gotoAndStabilize(page, routes.checkout);

    await expect(page).toHaveURL(/\/checkout\/?/);
    expect(page.url()).not.toContain('/cart');
    await expect(page.locator('.bsc__page--empty, .container__empty').first()).toBeVisible();
    await expect(page.locator('form.checkout, form[name="checkout"]')).toHaveCount(0);
  });

  test('cart page redirects to checkout', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for checkout edge coverage');

    await gotoAndStabilize(page, routes.cart);

    await expect(page).toHaveURL(/\/checkout\/?/);
    expect(page.url()).not.toContain('/cart');
  });

  test('removing the last checkout item returns to empty cart state', async ({ page }, testInfo) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for checkout edge coverage');

    await ensureCheckoutReadyFromCategory(page, [
      routes.categoryGrid,
      routes.category,
      routes.groupCategory,
    ], testInfo.project.name);
    await gotoAndStabilize(page, routes.checkout);

    const item = page.locator('.checkout-cart__item').first();
    await expect(item).toBeVisible();

    await item.locator('.delete-btn').click();
    await expect(page.locator('.footer__cart-count').first()).toHaveText('0');
    await expect(page).toHaveURL(/\/checkout\/?/, { timeout: 15_000 });
    expect(page.url()).not.toContain('/cart');
    await expect(page.locator('.bsc__page--empty, .container__empty').first()).toBeVisible({
      timeout: 15_000,
    });
  });

  test('billing city reload ignores stale ajax responses', async ({ page }, testInfo) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for checkout edge coverage');

    let cityRequestCount = 0;
    await page.route('**/wp-admin/admin-ajax.php*', async (route) => {
      const request = route.request();
      const postData = request.postData() || '';

      if (request.method() !== 'POST' || !postData.includes('action=bsc_reload_city_fields')) {
        await route.continue();
        return;
      }

      cityRequestCount += 1;
      const label = cityRequestCount === 1 ? 'Ciudad vieja QA' : 'Ciudad nueva QA';
      if (cityRequestCount === 1) {
        await new Promise((resolve) => setTimeout(resolve, 500));
      }

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: {
            html:
              '<p id="billing_city_field" class="form-row bsc__field">' +
              '<label for="billing_city">Ciudad</label>' +
              '<select id="billing_city" name="billing_city">' +
              '<option value="">Selecciona una ciudad</option>' +
              `<option value="${label}">${label}</option>` +
              '</select>' +
              '</p>',
          },
        }),
      });
    });

    await ensureCheckoutReadyFromCategory(page, [
      routes.categoryGrid,
      routes.category,
      routes.groupCategory,
    ], testInfo.project.name);
    await gotoAndStabilize(page, routes.checkout);

    const states = await page.locator('#billing_state option').evaluateAll((options) =>
      options
        .map((option) => option.value)
        .filter(Boolean)
        .slice(0, 2)
    );

    test.skip(states.length < 2, 'Checkout fixture needs at least two billing states.');

    await page.locator('#billing_state').selectOption(states[0]);
    await page.locator('#billing_state').selectOption(states[1]);

    await expect(page.locator('#billing_city option').last()).toHaveText('Ciudad nueva QA');
    await expect(page.locator('#billing_city')).not.toContainText('Ciudad vieja QA');
  });
});
