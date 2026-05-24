const fs = require('fs');
const path = require('path');

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
const strictMode =
  process.argv.includes('--strict') ||
  process.env.BSC_STRICT_PHP_OUTPUT_AUDIT === '1';
const maxExamples = Number(process.env.BSC_PHP_OUTPUT_AUDIT_EXAMPLES || 40);

const outputPattern = /\becho\s+\$|\bprint\s+\$|<\?=\s*\$|\bprintf\s*\(/;
const safePattern =
  /esc_html|esc_attr|esc_url|esc_textarea|wp_kses_post|wp_json_encode|wc_price|disabled\(|selected\(|checked\(|paginate_links|apply_filters|\/\/\s*phpcs:ignore|\/\/\s*WPCS: XSS ok|\/\/\s*PHPCS: XSS ok|@codingStandardsIgnoreLine/;

function collectPhpFiles(dir) {
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  const files = [];

  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      if (!ignoredDirs.has(entry.name)) {
        files.push(...collectPhpFiles(fullPath));
      }
      continue;
    }

    if (entry.isFile() && entry.name.endsWith('.php')) {
      files.push(fullPath);
    }
  }

  return files;
}

const findings = [];

for (const filePath of collectPhpFiles(rootDir)) {
  const relativePath = path.relative(rootDir, filePath).replace(/\\/g, '/');
  const source = fs.readFileSync(filePath, 'utf8');

  source.split(/\r?\n/).forEach((line, index) => {
    if (!outputPattern.test(line)) {
      return;
    }

    if (safePattern.test(line)) {
      return;
    }

    findings.push({
      file: relativePath,
      line: index + 1,
      text: line.trim().slice(0, 180),
    });
  });
}

console.log(`PHP output escaping audit: ${findings.length} line(s) need review.`);
for (const finding of findings.slice(0, maxExamples)) {
  console.log(`${finding.file}:${finding.line}: ${finding.text}`);
}

if (findings.length > maxExamples) {
  console.log(`...and ${findings.length - maxExamples} more.`);
}

if (strictMode && findings.length) {
  process.exit(1);
}
