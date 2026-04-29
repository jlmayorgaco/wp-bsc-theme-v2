const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { loginToWpAdmin } = require('../helpers/ui');

test.describe('BSC admin orders smoke', () => {
  test('orders page loads and bulk actions require a selection', async ({ page }, testInfo) => {
    const adminFixture =
      fixture?.adminVariants?.[testInfo.project.name] || fixture?.admin || null;

    test.skip(
      !adminFixture?.username || !adminFixture?.password,
      'The admin fixture is required for admin smoke coverage.'
    );

    const loggedIn = await loginToWpAdmin(
      page,
      adminFixture.username,
      adminFixture.password
    );

    expect(loggedIn).toBeTruthy();

    await page.goto('/wp-admin/admin.php?page=bsc-orders', {
      waitUntil: 'domcontentloaded',
    });
    await page.waitForLoadState('load');

    await expect(page.locator('.wrap.bsc-admin-orders h1').first()).toContainText(
      'Pedidos BSC'
    );

    const bulkWarning = page.locator('#bsc-bulk-msg').first();

    await expect(bulkWarning).toBeHidden();

    await page.locator('#bsc-packing-btn').click();
    await expect(bulkWarning).toBeVisible();

    await bulkWarning.waitFor({ state: 'hidden', timeout: 5000 });
  });
});
