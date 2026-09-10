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

function collectDomainFiles(dir, files = []) {
  const ignoredDirs = new Set([
    '.git',
    'node_modules',
    'vendor',
    'php-vendor',
    'test-results',
    'playwright-report',
  ]);
  const extensions = new Set([
    '.css',
    '.html',
    '.js',
    '.json',
    '.md',
    '.php',
    '.scss',
    '.sh',
    '.svg',
    '.txt',
    '.yaml',
    '.yml',
  ]);

  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    if (entry.isDirectory()) {
      if (!ignoredDirs.has(entry.name)) {
        collectDomainFiles(path.join(dir, entry.name), files);
      }
      continue;
    }

    if (entry.isFile() && extensions.has(path.extname(entry.name))) {
      files.push(path.join(dir, entry.name));
    }
  }

  return files;
}

const domainGuardFile = path.join(rootDir, 'tools', 'test-domain-guards.js');
const wrongSingleSDomain = new RegExp(`\\b${'bubble' + 'skincare'}\\.(?:co|com)\\b`, 'i');
const wrongCountryDomain = new RegExp(`\\b${'bubbles' + 'skincare'}\\.co\\b`, 'i');
const wrongDomainFindings = [];

for (const file of collectDomainFiles(rootDir)) {
  if (file === domainGuardFile) {
    continue;
  }

  const lines = fs.readFileSync(file, 'utf8').split(/\r?\n/);
  lines.forEach((line, index) => {
    if (wrongSingleSDomain.test(line) || wrongCountryDomain.test(line)) {
      wrongDomainFindings.push(`${path.relative(rootDir, file)}:${index + 1}`);
    }
  });
}

if (wrongDomainFindings.length) {
  throw new Error(
    `Only bubblesskincare.com is allowed as the production domain. Found legacy references at: ${wrongDomainFindings.join(', ')}`,
  );
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

console.log('Domain guard tests passed: canonical domain, stock, and Bubble Points contracts are intact.');
