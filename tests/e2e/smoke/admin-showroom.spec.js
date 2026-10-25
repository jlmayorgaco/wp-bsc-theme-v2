const { expect, test } = require('@playwright/test');
const { fixture } = require('../helpers/env');
const { openWpAdminPage } = require('../helpers/ui');

async function openAdminShowroomPage(page, testInfo) {
  const adminFixture =
    fixture?.adminVariants?.[testInfo.project.name] || fixture?.admin || null;

  test.skip(
    !adminFixture?.username || !adminFixture?.password,
    'The admin fixture is required for showroom admin smoke coverage.'
  );

  await openWpAdminPage(
    page,
    adminFixture,
    '/wp-admin/admin.php?page=bsc-showroom',
    async (currentPage) => {
      await expect(currentPage.locator('.wrap.bsc-showroom h1').first()).toContainText(
        'Venta Presencial'
      );
    }
  );
}

test.describe('BSC admin showroom smoke', () => {
  test('showroom page loads core controls', async ({ page }, testInfo) => {
    await openAdminShowroomPage(page, testInfo);

    await expect(page.locator('#bsc-product-search').first()).toBeVisible();
    await expect(page.locator('#bsc-product-qty').first()).toBeVisible();
    await expect(page.locator('#bsc-register-sale').first()).toBeVisible();
    await expect(page.locator('#bsc-coupon-code')).toBeVisible();
    await expect(page.locator('#bsc-apply-coupon')).toBeVisible();
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
    await expect(page.locator('#bsc-cart-body tr').first().locator('td').nth(1)).toHaveText('Stock');
  });

  test('out-of-stock products remain selectable and coupon changes update the total', async ({ page }, testInfo) => {
    let submitted;
    await page.route('**/wp-admin/admin-ajax.php', async (route, request) => {
      const data = new URLSearchParams(request.postData() || '');
      const action = data.get('action');
      if (action === 'bsc_showroom_search') {
        await route.fulfill({ json: { success: true, data: [
          { id: 99902, name: 'QA Producto Encargo', price: 100000, stock_tienda: 0 },
        ] } });
      } else if (action === 'bsc_showroom_coupon') {
        expect(data.get('coupon_code')).toBe('QA10');
        expect(JSON.parse(data.get('items'))[0].id).toBe(99902);
        await route.fulfill({ json: { success: true, data: { code: 'qa10', discount: 10000, total: 90000 } } });
      } else if (action === 'bsc_register_showroom_sale') {
        submitted = data;
        await route.fulfill({ json: { success: false, data: { message: 'QA: venta interceptada' } } });
      } else {
        await route.continue();
      }
    });
    await openAdminShowroomPage(page, testInfo);
    await page.locator('#bsc-product-search').fill('qa');
    await page.locator('.bsc-showroom__search-result').first().click();
    await page.locator('#bsc-add-product').click();
    await expect(page.locator('#bsc-cart-body td').nth(1)).toHaveText('Encargo');
    await expect(page.locator('#bsc-cart-table th').nth(1)).toHaveText('Stock');
    await expect(page.locator('#bsc-cart-table th').nth(2)).toHaveText('Qty');
    await page.locator('#bsc-customer-email').fill('qa-showroom@example.invalid');
    await page.locator('#bsc-coupon-code').fill('QA10');
    await page.locator('#bsc-apply-coupon').click();
    await expect(page.locator('#bsc-coupon-notice')).toContainText('aplicado');
    await expect(page.locator('#bsc-cart-total')).toHaveText('$90.000');
    await page.locator('.cart-qty').fill('2');
    await page.locator('.cart-qty').blur();
    await expect(page.locator('#bsc-cart-total')).toHaveText('$200.000');
    await expect(page.locator('#bsc-remove-coupon')).toBeHidden();
    await page.locator('.cart-qty').fill('1');
    await page.locator('.cart-qty').blur();
    await page.locator('#bsc-apply-coupon').click();
    await expect(page.locator('#bsc-cart-total')).toHaveText('$90.000');
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    expect(await page.locator('#bsc-customer-email').evaluate((input) => input.getBoundingClientRect().width)).toBeGreaterThan(250);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBeTruthy();
    await page.screenshot({ path: require('path').join(require('os').tmpdir(), 'bsc-showroom-' + testInfo.project.name + '.png'), fullPage: true });
    await page.locator('#bsc-register-sale').click();
    await expect(page.locator('#bsc-showroom-notice')).toContainText('QA: venta interceptada');
    expect(submitted.get('coupon_code')).toBe('qa10');
    expect(submitted.get('customer_email')).toBe('qa-showroom@example.invalid');
    expect(errors).toEqual([]);
  });
});
