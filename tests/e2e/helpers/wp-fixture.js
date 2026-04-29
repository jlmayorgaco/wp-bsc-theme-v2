const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');

let cachedFixture;
let warned = false;

function fileExists(targetPath) {
  return Boolean(targetPath && fs.existsSync(targetPath));
}

function findNewestFile(rootPath, relativeSegments) {
  if (!fileExists(rootPath)) {
    return '';
  }

  const candidates = [];

  for (const entry of fs.readdirSync(rootPath, { withFileTypes: true })) {
    if (!entry.isDirectory()) {
      continue;
    }

    const candidate = path.join(rootPath, entry.name, ...relativeSegments);

    if (!fileExists(candidate)) {
      continue;
    }

    const stat = fs.statSync(candidate);
    candidates.push({ path: candidate, mtimeMs: stat.mtimeMs });
  }

  candidates.sort((left, right) => right.mtimeMs - left.mtimeMs);
  return candidates[0]?.path || '';
}

function resolvePhpBinary() {
  if (fileExists(process.env.PW_PHP_BIN)) {
    return process.env.PW_PHP_BIN;
  }

  const appData = process.env.APPDATA || '';
  const lightningRoot = path.join(appData, 'Local', 'lightning-services');

  if (!fileExists(lightningRoot)) {
    return '';
  }

  const phpDir = findNewestFile(lightningRoot, ['bin', 'win64', 'php.exe']);
  return phpDir;
}

function resolvePhpIni() {
  if (fileExists(process.env.PW_PHP_INI)) {
    return process.env.PW_PHP_INI;
  }

  const appData = process.env.APPDATA || '';
  const localRunRoot = path.join(appData, 'Local', 'run');
  return findNewestFile(localRunRoot, ['conf', 'php', 'php.ini']);
}

function shouldSkipFixtureLoad() {
  return process.env.PW_DISABLE_WP_FIXTURE === '1';
}

function extractJsonPayload(stdout) {
  const jsonLine = String(stdout || '')
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter(Boolean)
    .reverse()
    .find((line) => line.startsWith('{') && line.endsWith('}'));

  if (!jsonLine) {
    throw new Error('No JSON payload was found in the fixture bootstrap output.');
  }

  return JSON.parse(jsonLine);
}

function loadAuthFixture() {
  if (cachedFixture !== undefined) {
    return cachedFixture;
  }

  if (shouldSkipFixtureLoad()) {
    cachedFixture = null;
    return cachedFixture;
  }

  const phpBinary = resolvePhpBinary();
  const phpIni = resolvePhpIni();
  const scriptPath = path.resolve(__dirname, '../fixtures/bootstrap-auth-visual-fixtures.php');

  if (!fileExists(scriptPath) || !fileExists(phpBinary) || !fileExists(phpIni)) {
    cachedFixture = null;

    if (!warned) {
      warned = true;
      console.warn('[playwright] Auth fixture disabled: Local PHP runtime was not resolved. Falling back to env vars.');
    }

    return cachedFixture;
  }

  try {
    const stdout = execFileSync(
      phpBinary,
      ['-c', phpIni, scriptPath],
      {
        cwd: path.resolve(__dirname, '../../..'),
        encoding: 'utf8',
        env: process.env,
        windowsHide: true,
      }
    );

    cachedFixture = extractJsonPayload(stdout);
    return cachedFixture;
  } catch (error) {
    cachedFixture = null;

    if (!warned) {
      warned = true;
      console.warn(`[playwright] Auth fixture bootstrap failed: ${error.message}`);
    }

    return cachedFixture;
  }
}

module.exports = {
  loadAuthFixture,
  resolvePhpBinary,
  resolvePhpIni,
};
