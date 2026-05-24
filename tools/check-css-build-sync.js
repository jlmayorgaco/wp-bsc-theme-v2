const fs = require('fs');
const path = require('path');
const sass = require('sass');
const { execFileSync } = require('child_process');

const rootDir = process.cwd();
const sassDir = path.join(rootDir, 'sass');
const ignoredCssOutputs = new Set([
  'style-rtl.css',
  'vendor/fontawesome/all.min.css',
  'vendor/swiper/swiper-bundle.min.css',
]);

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
      files.push(fullPath);
    }
  }

  return files.sort();
}

function normalize(value) {
  return String(value || '').replace(/\r\n/g, '\n').trimEnd();
}

function stripSourceMapComment(value, cssFileName) {
  const escapedFileName = cssFileName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  return normalize(value)
    .replace(new RegExp(`\\n/\\*# sourceMappingURL=${escapedFileName}\\.map \\*/$`), '')
    .trimEnd();
}

function toRepoPath(fileUrl) {
  const filePath = fileUrl.startsWith('file:')
    ? decodeURIComponent(new URL(fileUrl).pathname)
    : fileUrl;
  const normalized = filePath.replace(/^\/([A-Za-z]:\/)/, '$1');
  return path.relative(rootDir, normalized).replace(/\\/g, '/');
}

function normalizeActualMapSource(source, relativeCss) {
  return path
    .normalize(path.join(path.dirname(relativeCss), source))
    .replace(/\\/g, '/');
}

let failed = false;
const expectedCssOutputs = new Set();

for (const entrypoint of collectEntrypoints(sassDir)) {
  const relativeEntry = path.relative(sassDir, entrypoint).replace(/\\/g, '/');
  const relativeCss = relativeEntry.replace(/\.scss$/, '.css');
  const cssPath = path.join(rootDir, relativeCss);
  const mapPath = `${cssPath}.map`;
  const cssFileName = path.basename(cssPath);
  expectedCssOutputs.add(relativeCss);

  if (!fs.existsSync(cssPath)) {
    console.error(`CSS build sync failed: missing compiled output ${relativeCss}.`);
    failed = true;
    continue;
  }

  if (!fs.existsSync(mapPath)) {
    console.error(`CSS build sync failed: missing source map ${relativeCss}.map.`);
    failed = true;
    continue;
  }

  const compiled = sass.compile(entrypoint, {
    style: 'expanded',
    sourceMap: true,
    loadPaths: [sassDir],
  });
  const actualCss = fs.readFileSync(cssPath, 'utf8');
  const actualMap = JSON.parse(fs.readFileSync(mapPath, 'utf8'));
  const compiledSources = (compiled.sourceMap?.sources || [])
    .map(toRepoPath)
    .sort();
  const actualSources = (actualMap.sources || [])
    .map((source) => normalizeActualMapSource(source, relativeCss))
    .sort();

  if (normalize(compiled.css) !== stripSourceMapComment(actualCss, cssFileName)) {
    console.error(`CSS build sync failed: ${relativeCss} is not generated from sass/${relativeEntry}.`);
    failed = true;
  }

  if (actualMap.version !== 3) {
    console.error(`CSS build sync failed: ${relativeCss}.map has an invalid source map version.`);
    failed = true;
  }

  if (compiledSources.length && actualSources.join('\n') !== compiledSources.join('\n')) {
    console.error(`CSS build sync failed: ${relativeCss}.map sources do not match Sass compilation sources.`);
    failed = true;
  }
}

const trackedCssOutputs = execFileSync('git', ['ls-files', '*.css'], {
  cwd: rootDir,
  encoding: 'utf8',
})
  .split(/\r?\n/)
  .filter(Boolean)
  .map((file) => file.replace(/\\/g, '/'))
  .filter((file) => !ignoredCssOutputs.has(file));

for (const cssFile of trackedCssOutputs) {
  if (!expectedCssOutputs.has(cssFile)) {
    console.error(`CSS source of truth failed: ${cssFile} has no Sass entrypoint.`);
    failed = true;
  }
}

if (failed) {
  console.error('Run npm run compile:css and commit the generated CSS and source maps.');
  process.exit(1);
}

console.log(`CSS build sync passed: ${expectedCssOutputs.size} Sass entrypoint(s) match compiled CSS outputs.`);
