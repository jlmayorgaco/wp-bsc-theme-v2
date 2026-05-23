const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { openWpAdminPage } = require('../helpers/ui');

async function openCreatorsAdmin(page, testInfo) {
  test.skip(
    testInfo.project.name !== 'desktop',
    'Creators admin smoke is desktop-only because the WP admin table is the target surface.'
  );

  const adminFixture =
    fixture?.adminVariants?.[testInfo.project.name] || fixture?.admin || null;

  test.skip(
    !adminFixture?.username || !adminFixture?.password,
    'The admin fixture is required for Creators admin smoke coverage.'
  );

  await openWpAdminPage(
    page,
    adminFixture,
    '/wp-admin/admin.php?page=bsc-creators',
    async (currentPage) => {
      await expect(currentPage.locator('.wrap.bsc-admin-creators h1').first()).toContainText(
        'Bubble Creators'
      );
    }
  );
}

test.describe('BSC Creators admin smoke', () => {
  test('creators workflow page loads with filters and export', async ({ page }, testInfo) => {
    await openCreatorsAdmin(page, testInfo);

    await expect(page.locator('.bsc-admin-creators__filters').first()).toBeVisible();
    await expect(page.locator('select[name="creator_status"]').first()).toBeVisible();
    await expect(page.getByRole('link', { name: 'Exportar CSV' })).toBeVisible();
    await expect(page.locator('.bsc-admin-creators__table').first()).toBeVisible();
    await expect(page.locator('.bsc-admin-creators__table thead').first()).toContainText('Origen');
    await expect(page.locator('.bsc-admin-creators__table thead').first()).toContainText('Notas');
  });
});
