// Keep the existing bundle command/output while including only runtime trees.
const fs = require('fs');
const path = require('path');
const DirArchiver = require('dir-archiver');
const root = process.cwd();
const trees = new Set(['admin', 'components', 'emails', 'fonts', 'helpers', 'images', 'inc', 'includes', 'js', 'languages', 'plugins', 'template-parts', 'woocommerce']);
const rootFiles = new Set(['LICENSE', 'readme.txt', 'screenshot.png']);
const scriptFiles = new Set(['scripts/script_init.php', 'scripts/script_custom_types.php']);
const vendorTrees = ['vendor/fontawesome/', 'vendor/swiper/', 'vendor/webfonts/'];
const unusedRuntimeAssets = new Set([
  'fonts/dlicon.json',
  'fonts/dlicon.ttf',
  'fonts/dlicon.woff',
  'fonts/dlicon.woff2',
]);
const denied = /(?:^|\/)(?:\.[^/]+|node_modules|tests|test-results|output|php-vendor|secrets|seeds)(?:\/|$)|\.(?:zip|sql|log|map|mp4|bak|pem|key|env|md)$/i;
const excluded = [];
function visit(directory) {
  for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
    const absolute = path.join(directory, entry.name);
    const native = path.relative(root, absolute);
    const relative = native.replace(/\\/g, '/');
    const top = relative.split('/')[0];
    const allowed = !denied.test(relative) && !unusedRuntimeAssets.has(relative) && (
      trees.has(top) || scriptFiles.has(relative) || relative === 'scripts' ||
      relative === 'vendor' || vendorTrees.some(prefix => relative.startsWith(prefix) || relative + '/' === prefix) ||
      (!relative.includes('/') && (rootFiles.has(relative) || /\.(php|css)$/.test(relative)) && !relative.startsWith('phpstan'))
    );
    if (!allowed || entry.isSymbolicLink()) { excluded.push(native); continue; }
    if (entry.isDirectory()) visit(absolute);
  }
}
visit(root);
new DirArchiver(root, path.resolve(root, '../wp-bsc-theme-v2.zip'), excluded).createZip();
