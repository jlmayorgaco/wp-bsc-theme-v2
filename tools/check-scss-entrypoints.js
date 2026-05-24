const path = require('path');
const sass = require('sass');
const fs = require('fs');

const rootDir = process.cwd();
const sassDir = path.join(rootDir, 'sass');

function collectEntrypoints(dir) {
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  const files = [];

  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);

    if (entry.isDirectory()) {
      files.push(...collectEntrypoints(fullPath));
      continue;
    }

    if (entry.isFile() && entry.name.endsWith('.scss') && !entry.name.startsWith('_')) {
      files.push(path.relative(rootDir, fullPath).replace(/\\/g, '/'));
    }
  }

  return files.sort();
}

const entrypoints = collectEntrypoints(sassDir);

for (const entry of entrypoints) {
  const fullPath = path.join(rootDir, entry);
  sass.compile(fullPath, {
    style: 'expanded',
    loadPaths: [path.join(rootDir, 'sass')],
  });
  process.stdout.write(`OK ${entry}\n`);
}
