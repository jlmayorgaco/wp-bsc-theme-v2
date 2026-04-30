const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { gotoAndStabilize, loginToWpAdmin } = require('../helpers/ui');

async function openAdminShowroomPage(page, testInfo) {
  const adminFixture =
    fixture?.adminVariants?.[testInfo.project.name] || fixture?.admin || null;

  test.skip(
    !adminFixture?.username || !adminFixture?.password,
    'The admin fixture is required for showroom admin smoke coverage.'
  );

  const loggedIn = await loginToWpAdmin(
    page,
    adminFixture.username,
    adminFixture.password
  );

  expect(loggedIn).toBeTruthy();

  await gotoAndStabilize(page, '/wp-admin/admin.php?page=bsc-showroom', {
    maxAttempts: 5,
    primePage: false,
    waitForImages: false,
  });

  await expect(page.locator('.wrap.bsc-showroom h1').first()).toContainText(
    'Venta Presencial'
  );
}

test.describe('BSC admin showroom smoke', () => {
  test('showroom page loads core controls', async ({ page }, testInfo) => {
    await openAdminShowroomPage(page, testInfo);

    await expect(page.locator('#bsc-product-search').first()).toBeVisible();
    await expect(page.locator('#bsc-product-qty').first()).toBeVisible();
    await expect(page.locator('#bsc-register-sale').first()).toBeVisible();
    await expect(page.locator('.bsc-showroom__payment-option').first()).toBeVisible();
  });

  test('showroom search and add-to-cart flow works with mocked search results', async ({ page }, testInfo) => {
    await page.route('**/wp-admin/admin-ajax.php', async (route, request) => {
      const postData = request.postData() || '';

      if (!postData.includes('action=bsc_showroom_search')) {
        await route.continue();
        return;
      }

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: [
            {
              id: 99901,
              name: 'QA Showroom Product',
              sku: 'QA-001',
              price: 45000,
              stock_tienda: 7,
            },
          ],
        }),
      });
    });

    await openAdminShowroomPage(page, testInfo);

    await page.locator('#bsc-product-search').fill('qa');
    const result = page.locator('.bsc-showroom__search-result').first();
    await expect(result).toBeVisible();
    await result.click();

    await page.locator('#bsc-add-product').click();

    await expect(page.locator('#bsc-cart-table').first()).toBeVisible();
    await expect(page.locator('#bsc-cart-empty').first()).toBeHidden();
    await expect
      .poll(async () => {
        const totalText = await page.locator('#bsc-cart-total').first().textContent();
        return (totalText || '').replace(/[^\d]/g, '');
      })
      .toBe('45000');
    await expect(page.locator('#bsc-cart-body tr').first()).toContainText('QA Showroom Product');
  });
});
