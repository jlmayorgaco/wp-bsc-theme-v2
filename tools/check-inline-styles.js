#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

const root = process.cwd();
const allowed = new Map([
  ['admin/order-label-print.php', ['<style id="bsc-label-dynamic-size"></style>']],
  ['inc/custom-header.php', ['wp_add_inline_style']],
  ['inc/woocommerce.php', ['wp_add_inline_style']],
]);

function toPosix(filePath) {
  return filePath.split(path.sep).join('/');
}

function walk(dir, files = []) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    if (entry.name === '.git' || entry.name === 'node_modules' || entry.name === 'vendor' || entry.name === 'php-vendor') {
      continue;
    }

    const fullPath = path.join(dir, entry.name);

    if (entry.isDirectory()) {
      walk(fullPath, files);
      continue;
    }

    if (entry.isFile() && entry.name.endsWith('.php')) {
      files.push(fullPath);
    }
  }

  return files;
}

const findings = [];

for (const file of walk(root)) {
  const relative = toPosix(path.relative(root, file));

  if (relative.startsWith('emails/')) {
    continue;
  }

  const text = fs.readFileSync(file, 'utf8');
  const fileAllowlist = allowed.get(relative) || [];
  const patterns = [
    { label: '<style>', regex: /<style\b[^>]*>[\s\S]*?<\/style>/gi },
    { label: 'style=', regex: /\sstyle=(["']).*?\1/gi },
    { label: 'wp_add_inline_style', regex: /wp_add_inline_style\s*\(/g },
  ];

  for (const { label, regex } of patterns) {
    const matches = text.match(regex) || [];

    for (const match of matches) {
      const isAllowed = fileAllowlist.some((token) => match.includes(token));

      if (!isAllowed) {
        findings.push(`${relative}: static inline style found (${label})`);
      }
    }
  }
}

if (findings.length > 0) {
  console.error('Inline style check failed:');
  for (const finding of findings) {
    console.error(`- ${finding}`);
  }
  process.exit(1);
}

console.log('Inline style check passed: no static PHP inline styles outside emails.');
