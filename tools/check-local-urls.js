#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

const root = process.cwd();
const ignoredDirs = new Set([
  '.git',
  'node_modules',
  'vendor',
  'php-vendor',
  'test-results',
  'playwright-report',
  'Videos',
  'tests',
]);
const ignoredFiles = new Set([
  'CLAUDE.md',
  'ROADMAP_BSC.md',
  'composer.lock',
  'package-lock.json',
  'playwright.config.js',
  'scripts/backup-config.sh.example',
]);
const scannedExtensions = new Set([
  '.css',
  '.html',
  '.js',
  '.php',
  '.scss',
  '.svg',
  '.txt',
]);
const blockedPatterns = [
  /\bbsc\.local\b/i,
  /\blocalhost\b/i,
  /\b127\.0\.0\.1\b/,
];
const allowedReferences = new Map([
  [
    'inc/routing/frontend-routing.php',
    new Set(['bsc-local-host-exception']),
  ],
]);

function toPosix(filePath) {
  return filePath.split(path.sep).join('/');
}

function collectFiles(dir, files = []) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    if (entry.isDirectory()) {
      if (!ignoredDirs.has(entry.name)) {
        collectFiles(path.join(dir, entry.name), files);
      }
      continue;
    }

    if (!entry.isFile()) {
      continue;
    }

    const fullPath = path.join(dir, entry.name);
    const relativePath = toPosix(path.relative(root, fullPath));

    if (ignoredFiles.has(relativePath)) {
      continue;
    }

    if (scannedExtensions.has(path.extname(entry.name))) {
      files.push(fullPath);
    }
  }

  return files;
}

const findings = [];

for (const file of collectFiles(root)) {
  const relativePath = toPosix(path.relative(root, file));
  const lines = fs.readFileSync(file, 'utf8').split(/\r?\n/);

  lines.forEach((line, index) => {
    const isApprovedReference = [...(allowedReferences.get(relativePath) || [])].some(
      (marker) => line.includes(marker),
    );

    if (!isApprovedReference && blockedPatterns.some((pattern) => pattern.test(line))) {
      findings.push({
        file: relativePath,
        line: index + 1,
        text: line.trim().slice(0, 180),
      });
    }
  });
}

if (!findings.length) {
  console.log('Local URL check passed: no local dev hosts found in production files.');
  process.exit(0);
}

console.error(`Local URL check failed: ${findings.length} local dev host reference(s) found.`);
for (const finding of findings.slice(0, 40)) {
  console.error(`${finding.file}:${finding.line}: ${finding.text}`);
}

if (findings.length > 40) {
  console.error(`...and ${findings.length - 40} more.`);
}

process.exit(1);
