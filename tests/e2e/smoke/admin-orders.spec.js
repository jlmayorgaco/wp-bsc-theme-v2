const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { gotoAndStabilize, loginToWpAdmin } = require('../helpers/ui');

async function openAdminOrdersPage(page, testInfo) {
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

  await gotoAndStabilize(page, '/wp-admin/admin.php?page=bsc-orders', {
    maxAttempts: 5,
    primePage: false,
    waitForImages: false,
  });

  await expect(page.locator('.wrap.bsc-admin-orders h1').first()).toContainText(
    'Pedidos BSC'
  );
}

test.describe('BSC admin orders smoke', () => {
  test('orders page loads and bulk actions require a selection', async ({ page }, testInfo) => {
    await openAdminOrdersPage(page, testInfo);

    const bulkWarning = page.locator('#bsc-bulk-msg').first();

    await expect(bulkWarning).toBeHidden();

    await page.locator('#bsc-packing-btn').click();
    await expect(bulkWarning).toBeVisible();

    await bulkWarning.waitFor({ state: 'hidden', timeout: 5000 });
  });

  test('packing view opens in a new tab for a selected order', async ({ page }, testInfo) => {
    await openAdminOrdersPage(page, testInfo);

    const firstCheckbox = page.locator('input[name="order_ids[]"]').first();
    await expect(firstCheckbox).toBeVisible();
    await firstCheckbox.check();

    const popupPromise = page.waitForEvent('popup');
    await page.locator('#bsc-packing-btn').click();
    const popup = await popupPromise;

    await popup.waitForLoadState('domcontentloaded');
    await popup.waitForLoadState('load');

    await expect(popup.locator('body.bsc-packing-view').first()).toBeVisible();
    await expect(popup.locator('.bsc-packing-view__order-card').first()).toBeVisible();

    await popup.close();
  });

  test('order labels view opens in a new tab for a selected order', async ({ page }, testInfo) => {
    await openAdminOrdersPage(page, testInfo);

    const firstCheckbox = page.locator('input[name="order_ids[]"]').first();
    await expect(firstCheckbox).toBeVisible();
    await firstCheckbox.check();

    const popupPromise = page.waitForEvent('popup');
    await page.locator('#bsc-labels-btn').click();
    const popup = await popupPromise;

    await popup.waitForLoadState('domcontentloaded');
    await popup.waitForLoadState('load');

    await expect(popup.locator('body.bsc-order-label-print').first()).toBeVisible();
    await expect(popup.locator('.bsc-labels-toolbar').first()).toBeVisible();
    await expect(popup.locator('.bsc-print-label').first()).toBeVisible();
    await expect(popup.locator('[data-bsc-label-action=\"download-pdf\"]').first()).toBeVisible();

    await popup.close();
  });
});
