const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { openWpAdminPage } = require('../helpers/ui');

async function openEmailsAdmin(page, testInfo) {
  test.skip(
    testInfo.project.name !== 'desktop',
    'Emails admin smoke is desktop-only because the WP admin table is the target surface.'
  );

  const adminFixture =
    fixture?.adminVariants?.[testInfo.project.name] || fixture?.admin || null;

  test.skip(
    !adminFixture?.username || !adminFixture?.password,
    'The admin fixture is required for Emails admin smoke coverage.'
  );

  await openWpAdminPage(
    page,
    adminFixture,
    '/wp-admin/admin.php?page=bsc-followup-emails',
    async (currentPage) => {
      await expect(currentPage.locator('.wrap h1').first()).toContainText('Emails BSC');
    }
  );
}

test.describe('BSC Emails admin smoke', () => {
  test('emails page loads settings, operational log and previews', async ({ page }, testInfo) => {
    await openEmailsAdmin(page, testInfo);

    await expect(page.getByRole('heading', { name: /Log operativo de correos de pedidos/i })).toBeVisible();
    await expect(page.getByRole('heading', { name: /Templates editables/i })).toBeVisible();
    await expect(page.getByRole('button', { name: /Guardar/i })).toBeVisible();
    await expect(page.getByLabel(/Servidor SMTP/i)).toBeVisible();
    await expect(page.getByLabel(/Usuario SMTP/i)).toBeVisible();
    await expect(page.getByLabel(/Password SMTP/i)).toBeVisible();
    await expect(page.getByRole('link', { name: /Preview/i }).first()).toBeVisible();
    await expect(page.getByRole('button', { name: /Enviar prueba/i }).first()).toBeVisible();
    await expect(page.locator('input[name="bsc_preview_target_email"]').first()).toBeVisible();
  });
});
