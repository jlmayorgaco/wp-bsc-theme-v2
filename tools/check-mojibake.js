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
const scannedExtensions = new Set([
  '.php',
  '.js',
  '.scss',
  '.css',
  '.md',
]);

const mojibakePattern = /[\u00c3\u00c2]|\u00e2[\u0080-\uffff]|\ufffd/u;

function collectFiles(dir) {
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  const files = [];

  for (const entry of entries) {
    if (entry.isDirectory()) {
      if (!ignoredDirs.has(entry.name)) {
        files.push(...collectFiles(path.join(dir, entry.name)));
      }
      continue;
    }

    if (entry.isFile() && scannedExtensions.has(path.extname(entry.name))) {
      files.push(path.join(dir, entry.name));
    }
  }

  return files;
}

const findings = [];

for (const filePath of collectFiles(rootDir)) {
  const source = fs.readFileSync(filePath, 'utf8');
  const relativePath = path.relative(rootDir, filePath);

  source.split(/\r?\n/).forEach((line, index) => {
    if (mojibakePattern.test(line)) {
      findings.push({
        file: relativePath,
        line: index + 1,
        text: line.trim().slice(0, 160),
      });
    }
  });
}

if (!findings.length) {
  console.log('Encoding check passed: no mojibake signatures found.');
  process.exit(0);
}

console.error(`Encoding check failed: ${findings.length} mojibake signature(s) found.`);
for (const finding of findings.slice(0, 40)) {
  console.error(`${finding.file}:${finding.line}: ${finding.text}`);
}

if (findings.length > 40) {
  console.error(`...and ${findings.length - 40} more.`);
}

process.exit(1);
