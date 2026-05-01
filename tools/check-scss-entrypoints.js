const path = require('path');
const sass = require('sass');

const rootDir = process.cwd();
const entrypoints = [
  'sass/style.scss',
  'sass/woocommerce.scss',
  'sass/admin-order-label-print.scss',
];

for (const entry of entrypoints) {
  const fullPath = path.join(rootDir, entry);
  sass.compile(fullPath, {
    style: 'expanded',
    loadPaths: [path.join(rootDir, 'sass')],
  });
  process.stdout.write(`OK ${entry}\n`);
}