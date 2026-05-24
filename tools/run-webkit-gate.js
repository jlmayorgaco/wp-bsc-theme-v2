#!/usr/bin/env node

const { spawnSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const localPlaywrightBin = path.join(
  process.cwd(),
  'node_modules',
  '.bin',
  process.platform === 'win32' ? 'playwright.cmd' : 'playwright'
);
const hasLocalPlaywrightBin = fs.existsSync(localPlaywrightBin);
const playwrightBin = hasLocalPlaywrightBin ? localPlaywrightBin : 'npx';
const args = [
  'test',
  'tests/e2e/smoke/webkit-safari-gate.spec.js',
  '--workers=1',
  '--reporter=list',
  '--project=webkit-iphone',
  '--project=webkit-desktop',
];
const command = hasLocalPlaywrightBin
  ? `"${playwrightBin}" ${args.join(' ')}`
  : `${playwrightBin} playwright ${args.join(' ')}`;

const result = spawnSync(
  command,
  [],
  {
  cwd: process.cwd(),
  env: {
    ...process.env,
    PW_ENABLE_WEBKIT_GATE: '1',
  },
  stdio: 'inherit',
  shell: true,
  windowsHide: true,
  }
);

if (result.error) {
  console.error(result.error.message);
}

process.exit(result.status ?? 1);
