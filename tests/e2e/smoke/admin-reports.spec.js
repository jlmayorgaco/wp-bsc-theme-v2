const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { openWpAdminPage } = require('../helpers/ui');

async function openAdminReportsPage(page, testInfo, tab = 'ventas') {
  const adminFixture =
    fixture?.adminVariants?.[testInfo.project.name] || fixture?.admin || null;

  test.skip(
    !adminFixture?.username || !adminFixture?.password,
    'The admin fixture is required for reports admin smoke coverage.'
  );

  await openWpAdminPage(
    page,
    adminFixture,
    `/wp-admin/admin.php?page=bsc-reports&tab=${tab}`,
    async (currentPage) => {
      await expect(currentPage.locator('.wrap.bsc-admin-reports h1').first()).toContainText(
        'Informes BSC'
      );
    }
  );
}

test.describe('BSC admin reports smoke', () => {
  test('sales report loads filters and export controls', async ({ page }, testInfo) => {
    await openAdminReportsPage(page, testInfo, 'ventas');

    await expect(page.locator('.bsc-admin-reports__sales-filters').first()).toBeVisible();
    await expect(page.getByRole('link', { name: /Exportar CSV/i }).first()).toBeVisible();
    await expect(page.locator('.bsc-kpi-grid .bsc-kpi-card').first()).toBeVisible();
  });

  test('stock report loads search and updates result count', async ({ page }, testInfo) => {
    await openAdminReportsPage(page, testInfo, 'stock');

    const searchInput = page.locator('#bsc-stock-search').first();
    await expect(searchInput).toBeVisible();
    await expect(page.locator('#bsc-stock-per-page').first()).toBeVisible();
    await expect(page.locator('#bsc-stock-count').first()).toContainText(/resultado\(s\)/);

    const stockTable = page.locator('#bsc-stock-table').first();
    if (await stockTable.isVisible()) {
      await searchInput.fill('zzzzzzzzzz');
      await page.getByRole('button', { name: 'Buscar' }).first().click();

      await expect(page.locator('#bsc-stock-count').first()).toContainText('0 resultado');
      await expect(page.locator('.bsc-admin-reports__empty-state--stock').first()).toBeVisible();
      return;
    }

    await expect(page.locator('.bsc-admin-reports__empty-state--stock').first()).toBeVisible();
  });
});
