const fs = require('fs');
const path = require('path');

const rootDir = process.cwd();
const ignoredDirs = new Set([
  '.git',
  'node_modules',
  'vendor',
  'php-vendor',
  'test-results',
  'playwright-report',
  'Videos',
]);
const scannedExtensions = new Set(['.php', '.js', '.scss', '.css']);
const requiredLocalAssets = [
  'vendor/fontawesome/all.min.css',
  'vendor/swiper/swiper-bundle.min.css',
  'vendor/swiper/swiper-bundle.min.js',
  'vendor/webfonts/fa-brands-400.woff2',
  'vendor/webfonts/fa-regular-400.woff2',
  'vendor/webfonts/fa-solid-900.woff2',
];

function collectFiles(dir) {
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  const files = [];

  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      if (!ignoredDirs.has(entry.name)) {
        files.push(...collectFiles(fullPath));
      }
      continue;
    }

    if (entry.isFile() && scannedExtensions.has(path.extname(entry.name))) {
      files.push(fullPath);
    }
  }

  return files;
}

const missingAssets = requiredLocalAssets.filter((relativePath) => {
  const fullPath = path.join(rootDir, relativePath);
  return !fs.existsSync(fullPath) || fs.statSync(fullPath).size === 0;
});

const externalRuntimeReferences = [];
const externalPattern = /(?:@import\s+url\(|href=|src=|url\(|['"`])https?:\/\/([^'"`)>\s]+)/g;
const ignoredReferencePatterns = [
  /developer\.wordpress\.org/,
  /woocommerce\.com/,
  /github\.com/,
  /docs\.woocommerce\.com/,
  /codex\.wordpress\.org/,
  /underscores\.me/,
  /necolas\.github\.io/,
  /css-tricks\.com/,
  /example\.com/,
  /bsc\.local/,
];

for (const filePath of collectFiles(rootDir)) {
  const relativePath = path.relative(rootDir, filePath).replace(/\\/g, '/');
  const source = fs.readFileSync(filePath, 'utf8');

  source.split(/\r?\n/).forEach((line, index) => {
    let match;
    while ((match = externalPattern.exec(line))) {
      const url = match[0].replace(/^(?:@import\s+url\(|href=|src=|url\(|['"`])/, '');
      if (ignoredReferencePatterns.some((pattern) => pattern.test(url))) {
        continue;
      }

      externalRuntimeReferences.push({
        file: relativePath,
        line: index + 1,
        url,
      });
    }
  });
}

console.log(
  `Asset audit: ${requiredLocalAssets.length - missingAssets.length}/${requiredLocalAssets.length} required local vendor asset(s) present.`
);

if (missingAssets.length) {
  console.error('Missing required local assets:');
  missingAssets.forEach((asset) => console.error(`- ${asset}`));
}

console.log(`External runtime reference(s): ${externalRuntimeReferences.length}.`);
externalRuntimeReferences.slice(0, 30).forEach((finding) => {
  console.log(`${finding.file}:${finding.line}: ${finding.url}`);
});

if (missingAssets.length) {
  process.exit(1);
}
