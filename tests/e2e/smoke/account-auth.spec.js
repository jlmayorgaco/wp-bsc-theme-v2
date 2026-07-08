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

function accountBillingAddressRoute() {
  const base = routes.accountAddresses || '/mi-cuenta/edit-address/';

  if (/\/billing\/?$/i.test(base)) {
    return base;
  }

  return `${base.replace(/\/+$/, '')}/billing/`;
}

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

  test('register success redirects to welcome screen', async ({ page }, testInfo) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for auth smoke');

    const uniqueEmail = `bsc-e2e-${testInfo.project.name}-${Date.now()}-${Math.floor(Math.random() * 100000)}@example.com`;

    await gotoAndStabilize(page, routes.register);
    await page.locator('#nombres').fill('Maria Prueba');
    await page.locator('#email').fill(uniqueEmail);
    await page.locator('#password').fill('BubbleTest123!');
    await page.locator('#register-submit').click();

    await expect(page).toHaveURL(/\/registro-familia-bubbles\/?$/);
    await expect(page.locator('#bsc-register-welcome-title')).toContainText('Bienvenido Bubble lover');
    await expect(page.locator('.bsc-register-welcome__button')).toBeVisible();
  });

  test('edit account keeps hidden Woo fields in sync', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for account smoke');
    test.skip(!hasAccountAuth(), 'The auth fixture or PW_ACCOUNT_* credentials are required.');

    await loginFromAccount(page, routes.login, routes.account, auth.username || auth.email, auth.password);
    await gotoAndStabilize(page, routes.accountEdit);

    const fullName = page.locator('#account_full_name').first();
    const originalFullName = await fullName.inputValue();
    await fullName.fill('Maria Luisa Perez');

    await expect(page.locator('#account_first_name')).toHaveValue('Maria');
    await expect(page.locator('#account_last_name')).toHaveValue('Luisa Perez');
    await expect(page.locator('#account_display_name')).toHaveValue('Maria Luisa Perez');
    await expect(page.locator('#account_password')).toHaveCount(0);
    await expect(page.locator('#account_email')).toHaveJSProperty('readOnly', true);
    await expect(page.locator('#bsc_account_password')).toBeEditable();
    await expect(page.locator('#bsc_account_password_confirm')).toBeEditable();
    await expect(page.locator('#account_skin_type option')).toHaveCount(5);

    const skinValues = await page
      .locator('#account_skin_type option')
      .evaluateAll((options) => options.map((option) => option.value));
    expect(skinValues).toEqual(['', 'Grasa', 'Mixta', 'Seca', 'Normal']);

    await fullName.fill(originalFullName || 'Maria Luisa Perez');
    await page.locator('.bsc__account-submit button').click();

    const successNotice = page.locator('.bsc__account-notice--success').first();
    await expect(successNotice).toContainText('Tus datos se guardaron correctamente');
    await expect(page.locator('.woocommerce-message')).toHaveCount(0);

    const noticeBeforeButton = await page.evaluate(() => {
      const notice = document.querySelector('.bsc__account-notices');
      const button = document.querySelector('.bsc__account-submit button');

      if (!notice || !button) {
        return false;
      }

      return Boolean(notice.compareDocumentPosition(button) & Node.DOCUMENT_POSITION_FOLLOWING);
    });
    expect(noticeBeforeButton).toBe(true);

    for (const selector of ['#account_skin_type', '#account_sensitivity']) {
      const select = page.locator(selector).first();
      await expect(select).toBeVisible();

      const box = await select.boundingBox();
      expect(box?.height).toBeGreaterThanOrEqual(38);
    }
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

  test('billing address form uses checkout-style fields', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Storefront mode is required for account smoke');
    test.skip(!hasAccountAuth(), 'The auth fixture or PW_ACCOUNT_* credentials are required.');

    await loginFromAccount(page, routes.login, routes.account, auth.username || auth.email, auth.password);
    await gotoAndStabilize(page, accountBillingAddressRoute());

    const form = page.locator('.bsc__shipping-address').first();
    await expect(form).toBeVisible();
    await expect(page.locator('.woocommerce-address-fields__field-wrapper').first()).toHaveCSS('display', 'grid');
    await expect(page.locator('#billing_first_name_field label').first()).toHaveCSS('display', 'none');

    for (const selector of ['#billing_first_name', '#billing_last_name', '#billing_address_1', '#billing_phone']) {
      const field = page.locator(selector).first();
      await expect(field).toBeVisible();

      const metrics = await field.evaluate((node) => {
        const style = window.getComputedStyle(node);
        const rect = node.getBoundingClientRect();

        return {
          borderRadius: parseFloat(style.borderTopLeftRadius) || 0,
          borderStyle: style.borderTopStyle,
          fontSize: parseFloat(style.fontSize) || 0,
          height: rect.height,
        };
      });

      expect(metrics.borderRadius).toBeGreaterThanOrEqual(4);
      expect(metrics.borderStyle).toBe('solid');
      expect(metrics.fontSize).toBeGreaterThanOrEqual(14);
      expect(metrics.height).toBeGreaterThanOrEqual(40);
    }
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
