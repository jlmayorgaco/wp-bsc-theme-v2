const fs = require('fs');
const path = require('path');

const rootDir = process.cwd();
const themeTemplatesDir = path.join(rootDir, 'woocommerce');
const pluginTemplatesDir =
  process.env.WC_TEMPLATES_DIR ||
  path.resolve(rootDir, '..', '..', 'plugins', 'woocommerce', 'templates');
const reviewFile = path.join(rootDir, 'tools', 'woocommerce-template-review.json');
const strictMode =
  process.argv.includes('--strict') ||
  process.env.BSC_STRICT_WC_TEMPLATE_AUDIT === '1';

function collectPhpFiles(dir) {
  if (!fs.existsSync(dir)) {
    return [];
  }

  const files = [];
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const fullPath = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      files.push(...collectPhpFiles(fullPath));
    } else if (entry.isFile() && entry.name.endsWith('.php')) {
      files.push(fullPath);
    }
  }

  return files;
}

function readVersion(filePath) {
  const source = fs.readFileSync(filePath, 'utf8');
  const match = source.match(/@version\s+([0-9]+(?:\.[0-9]+){1,3})/);
  return match ? match[1] : '';
}

function compareVersions(left, right) {
  const l = left.split('.').map((part) => Number(part) || 0);
  const r = right.split('.').map((part) => Number(part) || 0);
  const max = Math.max(l.length, r.length);

  for (let index = 0; index < max; index += 1) {
    const diff = (l[index] || 0) - (r[index] || 0);
    if (diff !== 0) {
      return diff;
    }
  }

  return 0;
}

function readReviewDecisions() {
  if (!fs.existsSync(reviewFile)) {
    return {};
  }

  return JSON.parse(fs.readFileSync(reviewFile, 'utf8'));
}

if (!fs.existsSync(pluginTemplatesDir)) {
  console.error(`WooCommerce templates directory was not found: ${pluginTemplatesDir}`);
  process.exit(1);
}

const reviews = readReviewDecisions();

const rows = collectPhpFiles(themeTemplatesDir).map((themePath) => {
  const relative = path.relative(themeTemplatesDir, themePath).replace(/\\/g, '/');
  const upstreamPath = path.join(pluginTemplatesDir, relative);
  const themeVersion = readVersion(themePath);
  const upstreamVersion = fs.existsSync(upstreamPath) ? readVersion(upstreamPath) : '';
  const rawStatus = !fs.existsSync(upstreamPath)
    ? 'missing-upstream'
    : !themeVersion
      ? 'missing-theme-version'
      : !upstreamVersion
        ? 'missing-upstream-version'
        : compareVersions(themeVersion, upstreamVersion) < 0
          ? 'outdated'
          : 'ok';
  const hasReview = Boolean(reviews[relative]?.status && reviews[relative]?.reviewedOn);
  const status = rawStatus === 'ok' ? rawStatus : hasReview ? 'reviewed-custom' : rawStatus;

  return {
    relative,
    themeVersion,
    upstreamVersion,
    rawStatus,
    status,
    review: reviews[relative] || null,
  };
});

const counts = rows.reduce((accumulator, row) => {
  accumulator[row.status] = (accumulator[row.status] || 0) + 1;
  return accumulator;
}, {});

console.log(
  `WooCommerce template audit: ${rows.length} override(s), ` +
    `ok=${counts.ok || 0}, outdated=${counts.outdated || 0}, ` +
    `missing=${(counts['missing-upstream'] || 0) + (counts['missing-theme-version'] || 0) + (counts['missing-upstream-version'] || 0)}, ` +
    `reviewed=${counts['reviewed-custom'] || 0}.`
);

for (const row of rows.filter((item) => item.status !== 'ok').slice(0, 30)) {
  console.log(
    `${row.relative}: ${row.status}` +
      (row.rawStatus !== row.status ? ` (${row.rawStatus})` : '') +
      ` (theme ${row.themeVersion || 'n/a'} / upstream ${row.upstreamVersion || 'n/a'})`
  );
}

if (rows.filter((item) => item.status !== 'ok').length > 30) {
  console.log(`...and ${rows.filter((item) => item.status !== 'ok').length - 30} more.`);
}

if (strictMode && rows.some((row) => row.status !== 'ok' && row.status !== 'reviewed-custom')) {
  process.exit(1);
}
