const fs = require('fs');
const path = require('path');

const rootDir = process.cwd();

function read(relativePath) {
  return fs.readFileSync(path.join(rootDir, relativePath), 'utf8');
}

function assertIncludes(source, needle, message) {
  if (!source.includes(needle)) {
    throw new Error(message);
  }
}

function assertMatches(source, pattern, message) {
  if (!pattern.test(source)) {
    throw new Error(message);
  }
}

const stockSource = read('includes/class-bsc-stock.php');
assertIncludes(
  stockSource,
  '$from_bodega = min( max( 0, (int) $stock[\'bodega\'] ), $qty );',
  'BSC_Stock must allocate from bodega first.'
);
assertIncludes(
  stockSource,
  '$from_tienda = min( max( 0, (int) $stock[\'tienda\'] ), $remaining );',
  'BSC_Stock must allocate remaining quantity from tienda.'
);
assertIncludes(
  stockSource,
  'CAST(meta_value AS SIGNED) >= %d',
  'BSC_Stock::adjust_strict must keep the atomic stock-shortage guard.'
);
assertIncludes(
  stockSource,
  "add_metadata( 'order_item', $item_id, '_bsc_stock_allocation_lock'",
  'BSC_Stock must keep per-order-item allocation locks.'
);
assertMatches(
  stockSource,
  /return new WP_Error\( 'bsc_stock_shortage'/,
  'BSC_Stock must return stock-shortage WP_Error failures.'
);

const bubblePointsStore = read('plugins/bubble-points/includes/store.php');
assertIncludes(
  bubblePointsStore,
  'SELECT GET_LOCK(%s, %d)',
  'Bubble Points ledger writes must use a per-user database lock.'
);
assertIncludes(
  bubblePointsStore,
  'SELECT RELEASE_LOCK(%s)',
  'Bubble Points ledger writes must release the per-user database lock.'
);
assertMatches(
  bubblePointsStore,
  /'ok'\s*=>\s*false[\s\S]*'balance'\s*=>\s*\$current[\s\S]*'error'\s*=>\s*'insufficient_points'/,
  'Bubble Points debits must reject insufficient balances.'
);
assertMatches(
  bubblePointsStore,
  /bsc_bp_set_balance\(\s*\$user_id,\s*\$current\s*\);/,
  'Bubble Points must revert balance if ledger insert fails.'
);

const redeemSource = read('plugins/bubble-points/ajax/redeem.php');
assertIncludes(
  redeemSource,
  "bsc_rate_limit( 'bubble_points_redeem'",
  'Bubble Points redemption must keep rate limiting.'
);
assertIncludes(
  redeemSource,
  'bsc_bp_add_ledger_entry',
  'Bubble Points redemption must write through the ledger.'
);

console.log('Domain guard tests passed: stock and Bubble Points contracts are intact.');
