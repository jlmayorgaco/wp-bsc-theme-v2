const { expect, test } = require('@playwright/test');
const { routes } = require('../helpers/env');

test('custom frontend assets expose a release and per-file cache revision', async ({ page }) => {
  await page.goto(routes.groupCategory, { waitUntil: 'domcontentloaded' });

  const themeAssetUrls = await page.evaluate(() => [
    ...Array.from(document.querySelectorAll('link[rel="stylesheet"]'), (link) => link.href),
    ...Array.from(document.scripts, (script) => script.src),
  ].filter((url) => (
    url.includes('/wp-content/themes/wp-bsc-theme-v2/') &&
    !url.includes('/vendor/')
  )));

  expect(themeAssetUrls.length).toBeGreaterThan(2);

  for (const assetUrl of themeAssetUrls) {
    const version = new URL(assetUrl).searchParams.get('ver');
    expect(version, `Missing per-file revision in ${assetUrl}`).toMatch(
      /^2\.1\.3-\d+-\d+$/
    );
  }

  const mainStyle = themeAssetUrls.find((url) => url.includes('/style.css'));
  const wooStyle = themeAssetUrls.find((url) => url.includes('/woocommerce.css'));

  expect(mainStyle).toBeTruthy();
  expect(wooStyle).toBeTruthy();
  expect(new URL(mainStyle).searchParams.get('ver')).not.toBe(
    new URL(wooStyle).searchParams.get('ver')
  );
});
