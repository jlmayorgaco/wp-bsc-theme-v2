const { loadAuthFixture } = require('./wp-fixture');

const fixture = loadAuthFixture();
const publicMode = process.env.PW_PUBLIC_MODE || 'storefront';

const routes = {
  home: process.env.PW_ROUTE_HOME || '/',
  category:
    process.env.PW_ROUTE_CATEGORY ||
    fixture?.routes?.category ||
    '/product-category/group-skin-care/',
  categoryGrid:
    process.env.PW_ROUTE_CATEGORY_GRID ||
    fixture?.routes?.categoryGrid ||
    fixture?.routes?.category ||
    fixture?.routes?.groupCategory ||
    '/product-category/group-skin-care/',
  groupCategory:
    process.env.PW_ROUTE_GROUP_CATEGORY ||
    fixture?.routes?.groupCategory ||
    '/product-category/group-skin-care/',
  product:
    process.env.PW_ROUTE_PRODUCT ||
    fixture?.routes?.product ||
    '',
  checkout: process.env.PW_ROUTE_CHECKOUT || '/checkout/',
  shop: process.env.PW_ROUTE_SHOP || '/shop/',
  contact: process.env.PW_ROUTE_CONTACT || '/contact-us/',
  login:
    process.env.PW_ROUTE_LOGIN ||
    fixture?.routes?.login ||
    '/login/',
  register: process.env.PW_ROUTE_REGISTER || '/register/',
  bubbleCreators: process.env.PW_ROUTE_BUBBLE_CREATORS || '/bubble-creators/',
  account:
    process.env.PW_ROUTE_ACCOUNT ||
    fixture?.routes?.account ||
    '/mi-cuenta/',
  accountAddresses:
    process.env.PW_ROUTE_ACCOUNT_ADDRESSES ||
    fixture?.routes?.accountAddresses ||
    '/mi-cuenta/edit-address/',
  accountEdit:
    process.env.PW_ROUTE_ACCOUNT_EDIT ||
    fixture?.routes?.accountEdit ||
    '/mi-cuenta/edit-account/',
  accountOrders:
    process.env.PW_ROUTE_ACCOUNT_ORDERS ||
    fixture?.routes?.accountOrders ||
    '/mi-cuenta/orders/',
  bubblePoints:
    process.env.PW_ROUTE_BUBBLE_POINTS ||
    fixture?.routes?.bubblePoints ||
    '/mi-cuenta/bubble-points/',
  viewOrder:
    process.env.PW_ROUTE_VIEW_ORDER ||
    fixture?.routes?.viewOrder ||
    '',
  thankYou:
    process.env.PW_ROUTE_THANK_YOU ||
    (publicMode === 'storefront' ? fixture?.routes?.thankYou : '') ||
    '',
};

const auth = {
  email: process.env.PW_ACCOUNT_EMAIL || fixture?.auth?.email || '',
  username: process.env.PW_ACCOUNT_USERNAME || fixture?.auth?.username || '',
  password: process.env.PW_ACCOUNT_PASSWORD || fixture?.auth?.password || '',
};

function hasAccountAuth() {
  return Boolean((auth.username || auth.email) && auth.password);
}

function hasThankYouRoute() {
  return Boolean(routes.thankYou);
}

function hasViewOrderRoute() {
  return Boolean(routes.viewOrder);
}

function expectsStorefront() {
  return publicMode === 'storefront';
}

module.exports = {
  auth,
  expectsStorefront,
  fixture,
  hasAccountAuth,
  hasThankYouRoute,
  hasViewOrderRoute,
  publicMode,
  routes,
};
