const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { openWpAdminPage } = require('../helpers/ui');

async function openBubblePointsAdmin(page, testInfo) {
  test.skip(
    testInfo.project.name !== 'desktop',
    'Bubble Points admin smoke is desktop-only because WP_List_Table hides action controls on narrow viewports.'
  );

  const adminFixture =
    fixture?.adminVariants?.[testInfo.project.name] || fixture?.admin || null;

  test.skip(
    !adminFixture?.username || !adminFixture?.password,
    'The admin fixture is required for Bubble Points admin smoke coverage.'
  );

  await openWpAdminPage(
    page,
    adminFixture,
    '/wp-admin/admin.php?page=bsc-bubble-points',
    async (currentPage) => {
      await expect(currentPage.locator('.wrap.bsc-bp-admin h1').first()).toContainText(
        'Bubble Points'
      );
    }
  );
}

test.describe('Bubble Points admin smoke', () => {
  test('users list loads and inline adjust controls are visible', async ({ page }, testInfo) => {
    await openBubblePointsAdmin(page, testInfo);

    await expect(page.locator('.wp-list-table').first()).toBeVisible();
    await expect(page.locator('.bsc-bp-inline-form').first()).toBeVisible();
    await expect(page.locator('.bsc-bp-inline-form__amount').first()).toBeVisible();
  });

  test('history screen loads for the first listed user', async ({ page }, testInfo) => {
    await openBubblePointsAdmin(page, testInfo);

    const historyLink = page.getByRole('link', { name: /View history/i }).first();
    await expect(historyLink).toBeVisible();
    await historyLink.click();

    await expect(page.locator('.bsc-bp-admin__back-link').first()).toBeVisible();
    await expect(page.locator('.bsc-bp-history-form').first()).toBeVisible();
    await expect(page.locator('.widefat.striped').first()).toBeVisible();
  });
});
