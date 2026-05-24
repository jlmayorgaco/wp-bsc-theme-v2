const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { openWpAdminPage } = require('../helpers/ui');

async function openNewsletterAdmin(page, testInfo) {
  test.skip(
    testInfo.project.name !== 'desktop',
    'Newsletter admin smoke is desktop-only because the WP admin table is the target surface.'
  );

  const adminFixture =
    fixture?.adminVariants?.[testInfo.project.name] || fixture?.admin || null;

  test.skip(
    !adminFixture?.username || !adminFixture?.password,
    'The admin fixture is required for Newsletter admin smoke coverage.'
  );

  await openWpAdminPage(
    page,
    adminFixture,
    '/wp-admin/admin.php?page=bsc-newsletter',
    async (currentPage) => {
      await expect(currentPage.locator('.wrap.bsc-admin-newsletter h1').first()).toContainText(
        'Newsletter'
      );
    }
  );
}

test.describe('BSC Newsletter admin smoke', () => {
  test('newsletter workflow page loads with filters and export', async ({ page }, testInfo) => {
    await openNewsletterAdmin(page, testInfo);

    await expect(page.locator('.bsc-admin-newsletter__filters').first()).toBeVisible();
    await expect(page.locator('select[name="subscriber_status"]').first()).toBeVisible();
    await expect(page.locator('input[name="subscriber_query"]').first()).toBeVisible();
    await expect(page.getByRole('link', { name: 'Exportar CSV' })).toBeVisible();
    await expect(page.locator('.bsc-admin-newsletter__table').first()).toBeVisible();
  });
});
