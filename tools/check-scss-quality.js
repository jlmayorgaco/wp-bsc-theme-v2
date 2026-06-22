const fs = require('fs');
const path = require('path');

const rootDir = process.cwd();
const sassDir = path.join(rootDir, 'sass');
const allowedLegacyImports = new Set(['sass/components/_components.scss']);

function toPosix(filePath) {
  return filePath.split(path.sep).join('/');
}

function collectScssFiles(dir) {
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  const files = [];

  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);

    if (entry.isDirectory()) {
      files.push(...collectScssFiles(fullPath));
      continue;
    }

    if (entry.isFile() && entry.name.endsWith('.scss')) {
      files.push(fullPath);
    }
  }

  return files.sort();
}

function stripLineComment(line) {
  const commentIndex = line.indexOf('//');
  return commentIndex === -1 ? line : line.slice(0, commentIndex);
}

function lineRef(relativePath, lineNumber) {
  return `${relativePath}:${lineNumber}`;
}

const files = collectScssFiles(sassDir);
const issues = [];

for (const file of files) {
  const relativePath = toPosix(path.relative(rootDir, file));
  const lines = fs.readFileSync(file, 'utf8').split(/\r?\n/);
  const lastUseIndex = relativePath === 'sass/style.scss'
    ? lines.reduce((last, line, index) => (stripLineComment(line).trim().startsWith('@use ') ? index : last), -1)
    : -1;

  lines.forEach((line, index) => {
    const active = stripLineComment(line);
    const trimmed = active.trim();
    const ref = lineRef(relativePath, index + 1);

    if (/@import\s+/.test(active) && !allowedLegacyImports.has(relativePath)) {
      issues.push(`${ref} uses @import. Use @use/@forward for active Sass modules.`);
    }

    if (/padding-(?:top|right|bottom|left)\s*:\s*-\d/.test(active)) {
      issues.push(`${ref} has negative padding, which is invalid CSS and is ignored by browsers.`);
    }

    const unitlessDimension = active.match(/(?:^|[{\s])((?:min-|max-)?(?:width|height))\s*:\s*(-?\d+(?:\.\d+)?)\s*(?:!important)?\s*;/);
    if (unitlessDimension && Number(unitlessDimension[2]) !== 0) {
      issues.push(`${ref} sets ${unitlessDimension[1]} with a non-zero unitless value.`);
    }

    if (relativePath === 'sass/tokens/_css-vars.scss' && /^\s*\.(?!bsc\s*\{)/.test(active)) {
      issues.push(`${ref} defines a component selector in the token layer.`);
    }

    if (
      relativePath === 'sass/style.scss'
      && index > lastUseIndex
      && trimmed
      && !trimmed.startsWith('/*')
      && !trimmed.startsWith('*')
      && !trimmed.startsWith('*/')
    ) {
      issues.push(`${ref} adds CSS after the Sass manifest. Put styles in a token, generic, page, or component partial.`);
    }
  });
}

if (issues.length > 0) {
  console.error('SCSS quality gate failed:');
  for (const issue of issues) {
    console.error(`- ${issue}`);
  }
  process.exit(1);
}

console.log('OK SCSS quality gate');
