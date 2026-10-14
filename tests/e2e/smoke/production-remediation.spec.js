const { test, expect } = require('@playwright/test');
const { execFileSync } = require('child_process');
const { fixture, routes } = require('../helpers/env');
const { resolvePhpBinary, resolvePhpIni } = require('../helpers/wp-fixture');
const { loginFromAccount, loginToWpAdmin, selectCheckoutBillingDestination, gotoAndStabilize } = require('../helpers/ui');

test('home tabs support keyboard navigation and reduced motion without loading the unused icon font', async ({ page }) => {
  const unusedIconFontRequests = [];
  page.on('request', request => {
    if (/\/fonts\/dlicon\.(?:woff2?|ttf)(?:\?|$)/i.test(request.url())) unusedIconFontRequests.push(request.url());
  });
  await gotoAndStabilize(page, '/');
  await expect(page.locator('html')).toHaveAttribute('lang', /^es/);
  await expect(page.locator('.footer__shopping-cart .bsc-icon--bag')).toBeVisible();
  await expect(page.locator('.header-mobile__menu-btn .bsc-icon--menu')).toHaveCount(1);
  expect(unusedIconFontRequests).toEqual([]);
  const tabs = page.getByRole('tab');
  expect(await tabs.count()).toBeGreaterThan(1);
  await tabs.first().focus();
  await tabs.first().press('ArrowRight');
  await expect(tabs.nth(1)).toBeFocused();
  await expect(tabs.nth(1)).toHaveAttribute('aria-selected', 'true');
  await expect(page.getByRole('tabpanel')).toHaveCount(1);
  await tabs.nth(1).press('Home');
  await expect(tabs.first()).toHaveAttribute('aria-selected', 'true');
  await expect(page.getByRole('tabpanel')).toHaveCount(1);
  await page.screenshot({ path: test.info().outputPath('tabs-keyboard.png'), fullPage: false });
});

test('checkout and account responses cannot be publicly cached', async ({ request }) => {
  for (const path of ['/checkout/', routes.account]) {
    const response = await request.get(path);
    expect(response.status()).toBeLessThan(400);
    expect(response.headers()['cache-control']).toMatch(/no-cache|no-store|private/);
    expect(response.headers()['cache-control']).not.toMatch(/\bpublic\b/);
  }
});

test('coupon confirmation opens and closes from the keyboard', async ({ page }) => {
  test.skip(!fixture?.auth);
  test.skip(process.env.BSC_LOCAL_INTEGRATION !== '1', 'Local fixture mutation must be explicitly enabled.');
  const run = (mode, value) => execFileSync(resolvePhpBinary(), ['-c', resolvePhpIni(), 'tests/production-checkout-fixture.php', mode, String(value ?? '')], { encoding: 'utf8', env: process.env, windowsHide: true });
  const output = run('points-prepare');
  const original = JSON.parse(output.slice(output.lastIndexOf('{'))).balance;
  try {
  await loginFromAccount(page, routes.login, routes.account, fixture.auth.username, fixture.auth.password);
  await gotoAndStabilize(page, routes.bubblePoints);
  const button = page.locator('.js-bsc-coupon.is-normal button').first();
  await expect(button).toBeVisible();
  await button.focus();
  await button.press('Enter');
  await expect(page.getByRole('dialog')).toBeVisible();
  await expect(page.locator('#bscModalCancel')).toBeFocused();
  await page.locator('#bscModalCancel').press('Shift+Tab');
  await expect(page.locator('#bscModalConfirm')).toBeFocused();
  await page.locator('#bscModalConfirm').press('Escape');
  await expect(page.getByRole('dialog')).toHaveCount(0);
  await expect(button).toBeFocused();
  } finally { run('points-restore', original); }
});

test('operator cannot bypass product permissions through AJAX', async ({ page }) => {
  test.skip(!fixture?.adminRoles?.operator);
  const user = fixture.adminRoles.operator;
  expect(await loginToWpAdmin(page, user.username, user.password)).toBeTruthy();
  const response = await page.request.post('/wp-admin/admin-ajax.php', { form: { action: 'bsc_update_product_stock', product_id: '1', stock: '999' } });
  expect(response.status()).toBe(403);
  expect((await response.json()).success).toBe(false);
});

test('local checkout submits an order and deducts inventory once', async ({ page }) => {
  test.skip(process.env.BSC_LOCAL_CHECKOUT !== '1', 'Requires the temporary local gateway and external-network guard.');
  const php = resolvePhpBinary();
  const run = (mode, id) => execFileSync(php, ['-c', resolvePhpIni(), 'tests/production-checkout-fixture.php', mode, ...(id ? [String(id)] : [])], { encoding: 'utf8', env: { ...process.env, BSC_LOCAL_INTEGRATION: '1' }, windowsHide: true });
  const parse = output => {
    const jsonLine = output.trim().split(/\r?\n/).findLast(line => line.startsWith('{') && line.endsWith('}'));
    if (!jsonLine) throw new Error('Local checkout fixture returned no JSON result.');
    return JSON.parse(jsonLine);
  };
  const { id, login, password } = parse(run('create'));
  try {
    await loginFromAccount(page, routes.login, routes.account, login, password);
    await page.goto(`/checkout/?add-to-cart=${id}`);
    await expect(page.locator('form.checkout')).toBeVisible();
    await page.locator('#billing_first_name').fill('Prueba');
    await page.locator('#billing_last_name').fill('Local');
    await page.locator('#billing_cedula').fill('1000000000');
    await page.locator('#billing_phone').fill('3000000000');
    await selectCheckoutBillingDestination(page);
    await page.locator('#payment_method_bsc_local_audit').check({ force: true });
    const terms = page.locator('#terms');
    if (await terms.count()) await terms.check({ force: true });
    const responsePromise = page.waitForResponse(response => response.url().includes('wc-ajax=checkout') && response.request().method() === 'POST');
    await page.locator('#place_order').click();
    const response = await responsePromise;
    expect(response.ok()).toBeTruthy();
    await expect(page).toHaveURL(/order-received|gracias|thank/);
    expect(parse(run('verify', id))).toEqual({ paid: true, transaction: true, stock: 1, quantity: 1 });
  } finally { run('cleanup', id); }
});
