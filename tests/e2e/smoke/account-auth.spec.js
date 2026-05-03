const { expect, test } = require('@playwright/test');
const {
  auth,
  expectsStorefront,
  fixture,
  hasAccountAuth,
  hasViewOrderRoute,
  routes,
} = require('../helpers/env');
const { gotoAndStabilize, loginFromAccount } = require('../helpers/ui');

test.describe('BSC smoke - auth and account', () => {
  test('login page validates empty required fields', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for auth smoke');

    await gotoAndStabilize(page, routes.login);
    await page.locator('#wp-submit').click();

    await expect(page.locator('#error_user_login')).toContainText('Por favor ingresa tu correo o usuario.');
    await expect(page.locator('#error_user_pass')).toContainText('Por favor ingresa tu contrase');
  });

  test('register page validates empty required fields', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for auth smoke');

    await gotoAndStabilize(page, routes.register);
    await page.locator('#register-submit').click();

    await expect(page.locator('#error_nombres')).toContainText('Por favor ingresa tu nombre completo.');
    await expect(page.locator('#error_email')).toContainText('Por favor ingresa un correo');
    await expect(page.locator('#error_password')).toContainText('Por favor ingresa una contrase');
  });

  test('edit account keeps hidden Woo fields in sync', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for account smoke');
    test.skip(!hasAccountAuth(), 'The auth fixture or PW_ACCOUNT_* credentials are required.');

    await loginFromAccount(page, routes.login, routes.account, auth.username || auth.email, auth.password);
    await gotoAndStabilize(page, routes.accountEdit);

    const fullName = page.locator('#account_full_name').first();
    await fullName.fill('Maria Luisa Perez');

    await expect(page.locator('#account_first_name')).toHaveValue('Maria');
    await expect(page.locator('#account_last_name')).toHaveValue('Luisa Perez');
    await expect(page.locator('#account_display_name')).toHaveValue('Maria Luisa Perez');
  });

  test('account orders route renders table or no-orders state', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for account smoke');
    test.skip(!hasAccountAuth(), 'The auth fixture or PW_ACCOUNT_* credentials are required.');

    await loginFromAccount(page, routes.login, routes.account, auth.username || auth.email, auth.password);
    await gotoAndStabilize(page, routes.accountOrders);

    await expect(page.locator('.menu__link', { hasText: '¡ Mis pedidos !' }).first()).toBeVisible();
    await expect(page.locator('.menu__link', { hasText: '¡ Bubble points !' }).first()).toBeVisible();

    const emptyTitle = page.locator('#bsc-orders-empty-title').first();
    if (await emptyTitle.count()) {
      await expect(emptyTitle).toContainText('Upss... aún no tienes pedidos :(');
      await expect(page.locator('.orders__subtitle')).toContainText(
        '¡Tenemos todo para armar tu rutina coreana perfecta!'
      );
      await expect(page.locator('.orders__button')).toContainText('¡ Ir a la tienda !');
      await expect(page.locator('.orders__empty-image')).toHaveCount(0);
      return;
    }

    await expect(page.locator('.bsc__orders-row, .bsc__orders-card').first()).toBeAttached();
  });

  test('view order route renders authenticated order details', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for account smoke');
    test.skip(!hasAccountAuth(), 'The auth fixture or PW_ACCOUNT_* credentials are required.');
    test.skip(!hasViewOrderRoute(), 'The auth fixture or PW_ROUTE_VIEW_ORDER is required.');

    await loginFromAccount(page, routes.login, routes.account, auth.username || auth.email, auth.password);
    await gotoAndStabilize(page, routes.viewOrder);

    await expect(page.locator('#bsc-order-items')).toBeVisible();

    if (fixture?.order?.number) {
      await expect(page.locator('body')).toContainText(`#${fixture.order.number}`);
    }
  });
});
