const fs = require('fs');
const path = require('path');

const rootDir = process.cwd();
const reviewedFile = path.join(__dirname, 'php-request-audit-reviewed.json');
const ignoredDirs = new Set([
  '.git',
  'node_modules',
  'vendor',
  'php-vendor',
  'test-results',
  'playwright-report',
  'Videos',
]);

const strictMode =
  process.argv.includes('--strict') ||
  process.env.BSC_STRICT_PHP_REQUEST_AUDIT === '1';
const maxExamples = Number(process.env.BSC_PHP_REQUEST_AUDIT_EXAMPLES || 30);

const superglobalPattern = /\$_(POST|GET|REQUEST|SERVER|COOKIE|FILES)\b/g;
const sanitizerPattern =
  /wp_unslash|sanitize_|esc_url_raw|absint|intval|floatval|wc_clean|filter_input|wp_verify_nonce|check_ajax_referer|check_admin_referer|isset\s*\(|empty\s*\(|array_key_exists\s*\(/;

function normalizePath(filePath) {
  return filePath.replace(/\\/g, '/');
}

function findingKey(finding) {
  return `${normalizePath(finding.file)}:${finding.line}:${finding.global}:${finding.text}`;
}

function loadReviewedFindings() {
  if (!fs.existsSync(reviewedFile)) {
    return new Set();
  }

  const records = JSON.parse(fs.readFileSync(reviewedFile, 'utf8'));
  if (!Array.isArray(records)) {
    throw new Error(`${reviewedFile} must contain an array.`);
  }

  return new Set(records.map(findingKey));
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

const findings = [];
const reviewRequired = [];
const reviewedFindings = loadReviewedFindings();
const counts = new Map();

for (const filePath of collectPhpFiles(rootDir)) {
  const relativePath = normalizePath(path.relative(rootDir, filePath));
  const source = fs.readFileSync(filePath, 'utf8');

  source.split(/\r?\n/).forEach((line, index) => {
    const matches = [...line.matchAll(superglobalPattern)];

    for (const match of matches) {
      const name = match[1];
      const finding = {
        file: relativePath,
        line: index + 1,
        global: `$_${name}`,
        text: line.trim().slice(0, 180),
      };

      findings.push(finding);
      counts.set(name, (counts.get(name) || 0) + 1);

      if (!sanitizerPattern.test(line)) {
        reviewRequired.push(finding);
      }
    }
  });
}

const countSummary = [...counts.entries()]
  .sort(([left], [right]) => left.localeCompare(right))
  .map(([name, count]) => `$_${name}: ${count}`)
  .join(', ');

console.log(
  `PHP request audit: ${findings.length} request superglobal reference(s)` +
    (countSummary ? ` (${countSummary}).` : '.')
);

if (reviewRequired.length) {
  const unreviewed = reviewRequired.filter(
    (finding) => !reviewedFindings.has(findingKey(finding))
  );
  const reviewedCount = reviewRequired.length - unreviewed.length;

  console.log(
    `${reviewRequired.length} reference(s) need manual review or a narrower sanitizer pattern ` +
      `(${reviewedCount} reviewed, ${unreviewed.length} unreviewed).`
  );

  for (const finding of unreviewed.slice(0, maxExamples)) {
    console.log(`${finding.file}:${finding.line}: ${finding.global} ${finding.text}`);
  }

  if (unreviewed.length > maxExamples) {
    console.log(`...and ${unreviewed.length - maxExamples} more.`);
  }

  if (strictMode && unreviewed.length) {
    process.exit(1);
  }
}
