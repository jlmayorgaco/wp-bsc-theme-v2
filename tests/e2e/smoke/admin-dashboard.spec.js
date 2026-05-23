const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { openWpAdminPage } = require('../helpers/ui');

async function openDashboardAdmin(page, testInfo, query = '') {
  test.skip(
    testInfo.project.name !== 'desktop',
    'Dashboard admin smoke is desktop-only because the WP admin dashboard is the target surface.'
  );

  const adminFixture =
    fixture?.adminVariants?.[testInfo.project.name] || fixture?.admin || null;

  test.skip(
    !adminFixture?.username || !adminFixture?.password,
    'The admin fixture is required for Dashboard admin smoke coverage.'
  );

  await openWpAdminPage(
    page,
    adminFixture,
    `/wp-admin/admin.php?page=bsc-dashboard${query}`,
    async (currentPage) => {
      await expect(currentPage.locator('.wrap.bsc-admin-dashboard h1').first()).toContainText(
        'BSC Dashboard'
      );
    }
  );
}

test.describe('BSC dashboard admin smoke', () => {
  test('dashboard loads period filters, KPIs and quick links', async ({ page }, testInfo) => {
    await openDashboardAdmin(page, testInfo, '&dashboard_range=7d');

    await expect(page.locator('.bsc-admin-dashboard__filters').first()).toBeVisible();
    await expect(page.locator('select[name="dashboard_range"]').first()).toHaveValue('7d');
    await expect(page.locator('.bsc-admin-dashboard__card').first()).toBeVisible();
    await expect(page.locator('.bsc-admin-dashboard__quick-links').first()).toBeVisible();
  });
});
