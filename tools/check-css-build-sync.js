const fs = require('fs');
const path = require('path');
const sass = require('sass');

const rootDir = process.cwd();
const entrypoint = path.join(rootDir, 'sass', 'style.scss');
const cssPath = path.join(rootDir, 'style.css');
const mapPath = path.join(rootDir, 'style.css.map');

function normalize(value) {
  return String(value || '').replace(/\r\n/g, '\n').trimEnd();
}

function stripSourceMapComment(value) {
  return normalize(value)
    .replace(/\n\/\*# sourceMappingURL=style\.css\.map \*\/$/, '')
    .trimEnd();
}

function toRepoPath(fileUrl) {
  const filePath = fileUrl.startsWith('file:')
    ? decodeURIComponent(new URL(fileUrl).pathname)
    : fileUrl;
  const normalized = filePath.replace(/^\/([A-Za-z]:\/)/, '$1');
  return path.relative(rootDir, normalized).replace(/\\/g, '/');
}

const compiled = sass.compile(entrypoint, {
  style: 'expanded',
  sourceMap: true,
});

const actualCss = fs.readFileSync(cssPath, 'utf8');
const actualMap = JSON.parse(fs.readFileSync(mapPath, 'utf8'));
const compiledSources = (compiled.sourceMap?.sources || [])
  .map(toRepoPath)
  .sort();
const actualSources = (actualMap.sources || []).slice().sort();

let failed = false;

if (normalize(compiled.css) !== stripSourceMapComment(actualCss)) {
  console.error('CSS build sync failed: style.css is not generated from sass/style.scss.');
  failed = true;
}

if (actualMap.version !== 3 || !actualSources.includes('sass/style.scss')) {
  console.error('CSS build sync failed: style.css.map is missing the Sass entrypoint.');
  failed = true;
}

if (compiledSources.length && actualSources.join('\n') !== compiledSources.join('\n')) {
  console.error('CSS build sync failed: style.css.map sources do not match Sass compilation sources.');
  failed = true;
}

if (failed) {
  console.error('Run npm run compile:css and commit both style.css and style.css.map.');
  process.exit(1);
}

console.log('CSS build sync passed: style.css and style.css.map match Sass sources.');
