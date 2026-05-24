const { loadAuthFixture } = require('../tests/e2e/helpers/wp-fixture');

const fixture = loadAuthFixture();

if (!fixture) {
  console.error('Playwright fixture bootstrap failed or is disabled.');
  process.exit(1);
}

const productRoute = fixture.routes?.product || '';
const categoryRoute = fixture.routes?.categoryGrid || fixture.routes?.category || '';

if (!productRoute || !categoryRoute || !fixture.coupons?.fixed || !fixture.coupons?.freeShipping) {
  console.error('Playwright fixture bootstrap is incomplete.');
  process.exit(1);
}

console.log(
  JSON.stringify(
    {
      category: categoryRoute,
      product: productRoute,
      checkout: '/checkout/',
      coupons: fixture.coupons,
      account: fixture.routes?.account || '',
    },
    null,
    2
  )
);
