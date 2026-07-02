const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { openWpAdminPage } = require('../helpers/ui');

async function openContactAdmin(page, testInfo) {
  test.skip(
    testInfo.project.name !== 'desktop',
    'Contact admin smoke is desktop-only because the WP admin table is the target surface.'
  );

  const adminFixture =
    fixture?.adminVariants?.[testInfo.project.name] || fixture?.admin || null;

  test.skip(
    !adminFixture?.username || !adminFixture?.password,
    'The admin fixture is required for Contact admin smoke coverage.'
  );

  await openWpAdminPage(
    page,
    adminFixture,
    '/wp-admin/admin.php?page=bsc-contact',
    async (currentPage) => {
      await expect(currentPage.locator('.wrap.bsc-admin-contact h1').first()).toContainText(
        'Contacto'
      );
    }
  );
}

test.describe('BSC Contact admin smoke', () => {
  test('contact workflow page loads with filters and export', async ({ page }, testInfo) => {
    await openContactAdmin(page, testInfo);

    await expect(page.locator('.bsc-admin-contact__filters').first()).toBeVisible();
    await expect(page.locator('select[name="message_status"]').first()).toBeVisible();
    await expect(page.locator('input[name="message_query"]').first()).toBeVisible();
    await expect(page.getByRole('link', { name: 'Exportar CSV' })).toBeVisible();
    await expect(page.locator('.bsc-admin-contact__table').first()).toBeVisible();
    await expect(page.locator('.bsc-admin-contact__table thead').first()).toContainText(
      'Mensaje'
    );
    await expect(page.locator('.bsc-admin-contact__table thead').first()).toContainText(
      'Notificacion'
    );
  });
});
