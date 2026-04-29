const { expect, test } = require('@playwright/test');
const {
  auth,
  expectsStorefront,
  fixture,
  hasAccountAuth,
  hasThankYouRoute,
  hasViewOrderRoute,
  routes,
} = require('../helpers/env');
const { gotoAndStabilize, loginFromAccount } = require('../helpers/ui');

test.describe('BSC visual baseline - gated pages', () => {
  test.describe.configure({ mode: 'serial' });

  test('account page', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Authenticated visual baselines require PW_PUBLIC_MODE=storefront.');
    test.skip(!hasAccountAuth(), 'The auth fixture or PW_ACCOUNT_* credentials are required.');

    await loginFromAccount(page, routes.login, routes.account, auth.username || auth.email, auth.password);

    await expect(page).toHaveScreenshot('account.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('bubble points page', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Authenticated visual baselines require PW_PUBLIC_MODE=storefront.');
    test.skip(!hasAccountAuth(), 'The auth fixture or PW_ACCOUNT_* credentials are required.');

    await loginFromAccount(page, routes.login, routes.account, auth.username || auth.email, auth.password);
    await gotoAndStabilize(page, routes.bubblePoints);

    await expect(page).toHaveScreenshot('bubble-points.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('account addresses page', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Authenticated visual baselines require PW_PUBLIC_MODE=storefront.');
    test.skip(!hasAccountAuth(), 'The auth fixture or PW_ACCOUNT_* credentials are required.');

    await loginFromAccount(page, routes.login, routes.account, auth.username || auth.email, auth.password);
    await gotoAndStabilize(page, routes.accountAddresses);

    await expect(page).toHaveScreenshot('account-addresses.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('view order page', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Authenticated visual baselines require PW_PUBLIC_MODE=storefront.');
    test.skip(!hasAccountAuth(), 'The auth fixture or PW_ACCOUNT_* credentials are required.');
    test.skip(!hasViewOrderRoute(), 'The auth fixture or PW_ROUTE_VIEW_ORDER is required.');

    await loginFromAccount(page, routes.login, routes.account, auth.username || auth.email, auth.password);
    await gotoAndStabilize(page, routes.viewOrder);

    if (fixture?.order?.number) {
      await expect(page.locator('body')).toContainText(`#${fixture.order.number}`);
    }

    await expect(page).toHaveScreenshot('view-order.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('thank you page', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Authenticated visual baselines require PW_PUBLIC_MODE=storefront.');
    test.skip(!hasThankYouRoute(), 'The auth fixture or PW_ROUTE_THANK_YOU is required.');

    await gotoAndStabilize(page, routes.thankYou);

    if (fixture?.order?.number) {
      await expect(page.locator('body')).toContainText(`#${fixture.order.number}`);
    }

    await expect(page).toHaveScreenshot('thank-you.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });
});
