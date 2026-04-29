const { expect, test } = require('@playwright/test');
const {
  auth,
  hasAccountAuth,
  hasThankYouRoute,
  routes,
} = require('../helpers/env');
const { gotoAndStabilize, loginFromAccount } = require('../helpers/ui');

test.describe('BSC visual baseline - gated pages', () => {
  test('account page', async ({ page }) => {
    if (hasAccountAuth()) {
      await loginFromAccount(page, routes.account, auth.email, auth.password);
    } else {
      await gotoAndStabilize(page, routes.account);
    }

    await expect(page).toHaveScreenshot('account.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('bubble points page', async ({ page }) => {
    test.skip(!hasAccountAuth(), 'PW_ACCOUNT_EMAIL and PW_ACCOUNT_PASSWORD are required');

    await loginFromAccount(page, routes.account, auth.email, auth.password);
    await gotoAndStabilize(page, routes.bubblePoints);

    await expect(page).toHaveScreenshot('bubble-points.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });

  test('thank you page', async ({ page }) => {
    test.skip(!hasThankYouRoute(), 'PW_ROUTE_THANK_YOU is required');

    await gotoAndStabilize(page, routes.thankYou);

    await expect(page).toHaveScreenshot('thank-you.png', {
      animations: 'disabled',
      fullPage: true,
    });
  });
});
