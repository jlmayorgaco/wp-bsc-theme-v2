const fs = require('fs');
const path = require('path');
const { spawnSync } = require('child_process');

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

function tryCommand(command, args) {
  const result = spawnSync(command, args, { stdio: 'pipe', encoding: 'utf8' });
  return !result.error && result.status === 0;
}

function findPhpBinary() {
  const explicit = process.env.PHP_BIN || process.env.PW_PHP_BIN;
  if (explicit && fs.existsSync(explicit)) {
    return explicit;
  }

  if (tryCommand('php', ['-v'])) {
    return 'php';
  }

  const appData = process.env.APPDATA;
  if (!appData) {
    return null;
  }

  const lightningServicesDir = path.join(appData, 'Local', 'lightning-services');
  if (!fs.existsSync(lightningServicesDir)) {
    return null;
  }

  const phpDirs = fs.readdirSync(lightningServicesDir)
    .filter((entry) => entry.startsWith('php-'))
    .sort()
    .reverse();

  for (const dir of phpDirs) {
    const win64 = path.join(lightningServicesDir, dir, 'bin', 'win64', 'php.exe');
    const win32 = path.join(lightningServicesDir, dir, 'bin', 'win32', 'php.exe');

    if (fs.existsSync(win64)) {
      return win64;
    }

    if (fs.existsSync(win32)) {
      return win32;
    }
  }

  return null;
}

function collectPhpFiles(dir) {
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  const files = [];

  for (const entry of entries) {
    if (entry.isDirectory()) {
      if (!ignoredDirs.has(entry.name)) {
        files.push(...collectPhpFiles(path.join(dir, entry.name)));
      }
      continue;
    }

    if (entry.isFile() && entry.name.endsWith('.php')) {
      files.push(path.join(dir, entry.name));
    }
  }

  return files;
}

const phpBin = findPhpBinary();
if (!phpBin) {
  console.error('Unable to locate a PHP binary. Set PHP_BIN or PW_PHP_BIN if needed.');
  process.exit(1);
}

const targetFiles = process.argv.slice(2);
const phpFiles = targetFiles.length
  ? targetFiles.map((file) => path.resolve(rootDir, file))
  : collectPhpFiles(rootDir);

let failed = false;

for (const phpFile of phpFiles) {
  const result = spawnSync(phpBin, ['-l', phpFile], { stdio: 'pipe', encoding: 'utf8' });

  if (result.status !== 0) {
    failed = true;
    process.stderr.write(result.stdout || '');
    process.stderr.write(result.stderr || '');
    continue;
  }

  process.stdout.write(result.stdout || `No syntax errors detected in ${phpFile}\n`);
}

process.exit(failed ? 1 : 0);
