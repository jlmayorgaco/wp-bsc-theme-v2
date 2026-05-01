const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { openWpAdminPage, openWpAdminPopup } = require('../helpers/ui');

async function openAdminOrdersPage(page, testInfo) {
  const adminFixture =
    fixture?.adminVariants?.[testInfo.project.name] || fixture?.admin || null;

  test.skip(
    !adminFixture?.username || !adminFixture?.password,
    'The admin fixture is required for admin smoke coverage.'
  );

  await openWpAdminPage(
    page,
    adminFixture,
    '/wp-admin/admin.php?page=bsc-orders',
    async (currentPage) => {
      await expect(currentPage.locator('.wrap.bsc-admin-orders h1').first()).toContainText(
        'Pedidos BSC'
      );
    }
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
    test.skip(
      testInfo.project.name === 'mobile',
      'Popup-based WP admin order actions are covered on tablet/desktop only.'
    );

    await openAdminOrdersPage(page, testInfo);

    const firstCheckbox = page.locator('input[name="order_ids[]"]').first();
    await expect(firstCheckbox).toBeVisible();
    await firstCheckbox.check();
    await page.locator('#bsc-orders-form').evaluate((form) => form.setAttribute('target', '_blank'));

    const popup = await openWpAdminPopup(
      page,
      '#bsc-packing-btn',
      async (currentPopup) => {
        await expect(currentPopup.locator('body.bsc-packing-view').first()).toBeVisible();
        await expect(currentPopup.locator('.bsc-packing-view__order-card').first()).toBeVisible();
      }
    );

    await popup.close();
    await page.locator('#bsc-orders-form').evaluate((form) => form.removeAttribute('target'));
  });

  test('order labels view opens in a new tab for a selected order', async ({ page }, testInfo) => {
    test.skip(
      testInfo.project.name === 'mobile',
      'Popup-based WP admin order actions are covered on tablet/desktop only.'
    );

    await openAdminOrdersPage(page, testInfo);

    const firstCheckbox = page.locator('input[name="order_ids[]"]').first();
    await expect(firstCheckbox).toBeVisible();
    await firstCheckbox.check();
    await page.locator('#bsc-orders-form').evaluate((form) => form.setAttribute('target', '_blank'));

    const popup = await openWpAdminPopup(
      page,
      '#bsc-labels-btn',
      async (currentPopup) => {
        await expect(currentPopup.locator('body.bsc-order-label-print').first()).toBeVisible();
        await expect(currentPopup.locator('.bsc-labels-toolbar').first()).toBeVisible();
        await expect(currentPopup.locator('.bsc-print-label').first()).toBeVisible();
        await expect(
          currentPopup.locator('[data-bsc-label-action=\"download-pdf\"]').first()
        ).toBeVisible();
      }
    );

    await popup.close();
    await page.locator('#bsc-orders-form').evaluate((form) => form.removeAttribute('target'));
  });
});
