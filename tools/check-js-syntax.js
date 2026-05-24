const fs = require('fs');
const path = require('path');
const { spawnSync } = require('child_process');

const rootDir = process.cwd();
const targets = ['js', path.join('tests', 'e2e'), 'tools'];
const ignoredDirs = new Set(['node_modules', 'test-results', 'playwright-report', 'Videos', 'php-vendor']);

function collectJsFiles(dir) {
  if (!fs.existsSync(dir)) {
    return [];
  }

  const entries = fs.readdirSync(dir, { withFileTypes: true });
  const files = [];

  for (const entry of entries) {
    if (entry.isDirectory()) {
      if (!ignoredDirs.has(entry.name)) {
        files.push(...collectJsFiles(path.join(dir, entry.name)));
      }
      continue;
    }

    if (entry.isFile() && entry.name.endsWith('.js')) {
      files.push(path.join(dir, entry.name));
    }
  }

  return files;
}

const jsFiles = targets.flatMap((target) => collectJsFiles(path.join(rootDir, target)));
let failed = false;

for (const jsFile of jsFiles) {
  const result = spawnSync(process.execPath, ['--check', jsFile], { stdio: 'pipe', encoding: 'utf8' });

  if (result.status !== 0) {
    failed = true;
    process.stderr.write(result.stderr || result.stdout || `Syntax check failed: ${jsFile}\n`);
    continue;
  }

  process.stdout.write(`OK ${path.relative(rootDir, jsFile)}\n`);
}

process.exit(failed ? 1 : 0);
