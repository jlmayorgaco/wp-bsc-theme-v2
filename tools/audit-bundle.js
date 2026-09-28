const fs = require('fs');
const path = require('path');

const archivePath = path.resolve(process.cwd(), process.argv[2] || '../wp-bsc-theme-v2.zip');
const requiredEntries = [
  'vendor/fontawesome/all.min.css',
  'vendor/swiper/swiper-bundle.min.css',
  'vendor/swiper/swiper-bundle.min.js',
  'vendor/webfonts/fa-solid-900.woff2',
];
const unusedRuntimeAssets = [
  'fonts/dlicon.json',
  'fonts/dlicon.ttf',
  'fonts/dlicon.woff',
  'fonts/dlicon.woff2',
];
const forbiddenPrefixes = ['node_modules/', 'tests/', 'tools/', 'sass/', 'output/', 'docs/', '.codex-remote-attachments/', '.claude/', '.github/', '.git/', '.playwright-cli/', 'test-results/', 'playwright-report/', 'cicd/', 'Videos/', 'plugins/bubble-points/seeds/'];

if (!fs.existsSync(archivePath)) {
  console.error(`Bundle not found: ${archivePath}`);
  process.exit(1);
}

const bytes = fs.readFileSync(archivePath);
const eocdSignature = Buffer.from([0x50, 0x4b, 0x05, 0x06]);
const eocdOffset = bytes.lastIndexOf(eocdSignature);
if (eocdOffset < 0) {
  console.error('Bundle is not a readable ZIP archive.');
  process.exit(1);
}

const entryCount = bytes.readUInt16LE(eocdOffset + 10);
let cursor = bytes.readUInt32LE(eocdOffset + 16);
const entries = [];

for (let index = 0; index < entryCount; index += 1) {
  if (bytes.readUInt32LE(cursor) !== 0x02014b50) {
    console.error(`Invalid central-directory entry at offset ${cursor}.`);
    process.exit(1);
  }

  const nameLength = bytes.readUInt16LE(cursor + 28);
  const extraLength = bytes.readUInt16LE(cursor + 30);
  const commentLength = bytes.readUInt16LE(cursor + 32);
  entries.push(bytes.toString('utf8', cursor + 46, cursor + 46 + nameLength).replace(/\\/g, '/'));
  cursor += 46 + nameLength + extraLength + commentLength;
}

const missing = requiredEntries.filter((entry) => !entries.includes(entry));
const leaked = entries.filter((entry) => forbiddenPrefixes.some((prefix) => entry.startsWith(prefix)) || unusedRuntimeAssets.includes(entry) || /\.(?:zip|sql|log|map|mp4|pem|key|env|bak)$/i.test(entry) || /(?:^|\/)\.[^/]+/.test(entry));

if (missing.length || leaked.length) {
  if (missing.length) console.error(`Missing runtime assets: ${missing.join(', ')}`);
  if (leaked.length) console.error(`Development files leaked into bundle: ${leaked.slice(0, 10).join(', ')}`);
  process.exit(1);
}

console.log(`Bundle audit passed: ${entries.length} entries, runtime vendor assets present, development trees excluded.`);
