// Run: PHP_BIN=/path/to/php node --test tests/wompi-widget-return.js
// Executes the PHP-generated widget script with an isolated browser and gateway.
const { execFileSync } = require('node:child_process');
const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const path = require('node:path');

const script = execFileSync(process.env.PHP_BIN || 'php', [
  '-n', path.join(__dirname, 'order-payment-status.php'), '--widget-script',
], { encoding: 'utf8' });

function openWidget({ failFirstOpen = false } = {}) {
  const callbacks = [];
  let openAttempts = 0;
  const buttonEvents = {};
  const paymentUrl = 'https://shop.example.test/checkout/order-pay/69087/?key=test';
  const window = {
    location: { href: paymentUrl },
    bscWompiData: {
      reference: '69087',
      redirectUrl: 'https://shop.example.test/checkout/order-received/69087/?key=test',
    },
    WidgetCheckout: function (options) {
      assert.equal(options.reference, '69087');
      this.open = callback => {
        openAttempts += 1;
        if (failFirstOpen && openAttempts === 1) throw new Error('Temporary widget error');
        callbacks.push(callback);
      };
    },
  };
  const document = {
    readyState: 'complete',
    querySelectorAll: () => [{ addEventListener: (name, callback) => { buttonEvents[name] = callback; } }],
  };
  vm.runInNewContext(script, { window, document, URL });
  return { window, callbacks, buttonEvents, paymentUrl };
}

test('closing without a transaction stays on payment and permits retry of the same order', () => {
  for (const result of [undefined, null, {}, { transaction: null }, { transaction: {} }]) {
    const ui = openWidget();
    assert.equal(ui.callbacks.length, 1);
    ui.callbacks[0](result);
    assert.equal(ui.window.location.href, ui.paymentUrl);
    ui.buttonEvents.click({ preventDefault() {} });
    assert.equal(ui.callbacks.length, 2);
    assert.equal(ui.window.location.href, ui.paymentUrl);
  }
});

test('real transactions retain the order key and send their ID to server verification', () => {
  for (const status of ['APPROVED', 'PENDING', 'DECLINED']) {
    const ui = openWidget();
    ui.callbacks[0]({ transaction: { id: 'test-transaction', status } });
    const target = new URL(ui.window.location.href);
    assert.equal(target.pathname, '/checkout/order-received/69087/');
    assert.equal(target.searchParams.get('key'), 'test');
    assert.equal(target.searchParams.get('bsc_wompi_return'), '1');
    assert.equal(target.searchParams.get('bsc_wompi_transaction_id'), 'test-transaction');
    assert.equal(target.searchParams.get('bsc_wompi_status'), status);
  }
});

test('repeated clicks do not open overlapping payment widgets', () => {
  const ui = openWidget();
  ui.buttonEvents.click({ preventDefault() {} });
  ui.buttonEvents.click({ preventDefault() {} });
  assert.equal(ui.callbacks.length, 1);
  ui.callbacks[0](null);
  ui.buttonEvents.click({ preventDefault() {} });
  assert.equal(ui.callbacks.length, 2);
});

test('the callback cannot replace the configured order return URL', () => {
  const ui = openWidget();
  ui.callbacks[0]({ transaction: {
    id: 'test-transaction', status: 'APPROVED', redirectUrl: 'https://other.example.test/',
  } });
  const target = new URL(ui.window.location.href);
  assert.equal(target.origin, 'https://shop.example.test');
  assert.equal(target.pathname, '/checkout/order-received/69087/');
  assert.equal(target.searchParams.get('key'), 'test');
});

test('a widget error releases the button for another attempt', () => {
  const ui = openWidget({ failFirstOpen: true });
  assert.equal(ui.callbacks.length, 0);
  ui.buttonEvents.click({ preventDefault() {} });
  assert.equal(ui.callbacks.length, 1);
});
