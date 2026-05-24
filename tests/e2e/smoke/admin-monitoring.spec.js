const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { openWpAdminPage } = require('../helpers/ui');

async function openMonitoringAdmin(page, testInfo) {
  test.skip(
    testInfo.project.name !== 'desktop',
    'Monitoring admin smoke is desktop-only because the WP admin surface is the target.'
  );

  const adminFixture =
    fixture?.adminVariants?.[testInfo.project.name] || fixture?.admin || null;

  test.skip(
    !adminFixture?.username || !adminFixture?.password,
    'The admin fixture is required for Monitoring admin smoke coverage.'
  );

  await openWpAdminPage(
    page,
    adminFixture,
    '/wp-admin/admin.php?page=bsc-monitoring',
    async (currentPage) => {
      await expect(currentPage.locator('.wrap.bsc-admin-monitoring h1').first()).toContainText(
        'Monitoreo post-launch'
      );
    }
  );
}

test.describe('BSC Monitoring admin smoke', () => {
  test('monitoring page loads owner, signals and incident procedure', async ({ page }, testInfo) => {
    await openMonitoringAdmin(page, testInfo);

    await expect(page.getByRole('heading', { name: /Owner de incidentes/i })).toBeVisible();
    await expect(page.getByRole('heading', { name: /Senales operativas/i })).toBeVisible();
    await expect(page.getByRole('heading', { name: /Checklist diario/i })).toBeVisible();
    await expect(page.getByRole('heading', { name: /Procedimiento de incidente/i })).toBeVisible();
    await expect(page.locator('.bsc-admin-monitoring__card')).toHaveCount(6);
    await expect(page.getByRole('button', { name: /Guardar owner/i })).toBeVisible();
  });
});
