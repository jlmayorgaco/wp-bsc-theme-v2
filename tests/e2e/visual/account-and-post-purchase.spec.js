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

async function ensureAuthenticatedRoute(page, path, verify) {
  for (let attempt = 1; attempt <= 3; attempt += 1) {
    await loginFromAccount(page, routes.login, routes.account, auth.username || auth.email, auth.password);
    await gotoAndStabilize(page, path, {
      maxAttempts: 5,
    });

    if (await verify()) {
      return;
    }

    await page.waitForTimeout(400 * attempt);
  }

  throw new Error(`Authenticated route did not stabilize for ${path}`);
}

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

    await ensureAuthenticatedRoute(page, routes.bubblePoints, async () => {
      return (await page.locator('.profile-points, .profile-points__container, .bsc-coupon-wallet').count()) > 0;
    });

    await expect(page).toHaveScreenshot('bubble-points.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('account addresses page', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Authenticated visual baselines require PW_PUBLIC_MODE=storefront.');
    test.skip(!hasAccountAuth(), 'The auth fixture or PW_ACCOUNT_* credentials are required.');

    await ensureAuthenticatedRoute(page, routes.accountAddresses, async () => {
      const currentUrl = page.url();
      if (!/edit-address/i.test(currentUrl)) {
        return false;
      }

      return (await page.locator('.bsc__address-wrapper, .bsc__address-card, .bsc__address-content').count()) > 0;
    });

    await expect(page).toHaveScreenshot('account-addresses.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('view order page', async ({ page }) => {
    test.skip(!expectsStorefront(), 'Authenticated visual baselines require PW_PUBLIC_MODE=storefront.');
    test.skip(!hasAccountAuth(), 'The auth fixture or PW_ACCOUNT_* credentials are required.');
    test.skip(!hasViewOrderRoute(), 'The auth fixture or PW_ROUTE_VIEW_ORDER is required.');

    await ensureAuthenticatedRoute(page, routes.viewOrder, async () => {
      if (!(await page.locator('#bsc-order-items').count())) {
        return false;
      }

      if (fixture?.order?.number) {
        const bodyText = await page.locator('body').first().innerText().catch(() => '');
        return bodyText.includes(`#${fixture.order.number}`);
      }

      return true;
    });

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
