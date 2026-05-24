const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { openWpAdminPage } = require('../helpers/ui');

test.describe('BSC importer admin smoke', () => {
  test('theme-bundled importer owns the legacy admin URL', async ({ page }, testInfo) => {
    test.skip(
      testInfo.project.name !== 'desktop',
      'Importer admin smoke is desktop-only because the WP admin dashboard is the target surface.'
    );

    const adminFixture =
      fixture?.adminVariants?.[testInfo.project.name] || fixture?.admin || null;

    test.skip(
      !adminFixture?.username || !adminFixture?.password,
      'The admin fixture is required for importer admin smoke coverage.'
    );

    await openWpAdminPage(
      page,
      adminFixture,
      '/wp-admin/admin.php?page=bsc-plugin',
      async (currentPage) => {
        await expect(currentPage.locator('.wrap.bsc-importer-admin h1').first()).toContainText(
          'Importador BSC'
        );
      }
    );

    await expect(page.locator('.wrap.bsc-plugin-admin')).toHaveCount(0);
    await expect(page.locator('#toplevel_page_bsc-plugin')).toHaveCount(0);
    await expect(page.locator('.bsc-importer-task h3')).toContainText([
      'Categorias',
      'Productos',
      'Fotos por SKU',
    ]);
    await expect(page.locator('.bsc-importer-admin__summary-item')).toHaveCount(4);
    await expect(page.locator('input[name="bsc_theme_importer_confirm"]')).toHaveCount(3);
    await expect(page.locator('.bsc-category-overview')).not.toHaveAttribute('open', '');
  });
});
