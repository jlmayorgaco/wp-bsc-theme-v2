const { spawn, execFileSync } = require('child_process');
const { resolvePhpBinary, resolvePhpIni } = require('../tests/e2e/helpers/wp-fixture');
const php = resolvePhpBinary();
const args = ['-c', resolvePhpIni(), 'tests/production-concurrency.php'];
const env = { ...process.env, BSC_LOCAL_INTEGRATION: '1' };
const parse = output => JSON.parse(output.trim().split(/\r?\n/).filter(line => line.startsWith('{')).pop());
const run = mode => execFileSync(php, [...args, mode], { encoding: 'utf8', env, windowsHide: true });
const state = parse(run('setup'));
state.start = Date.now() / 1000 + 10;
env.BSC_TEST_STATE = JSON.stringify(state);
(async () => {
  try {
    const workers = await Promise.all(Array.from({ length: 8 }, (_, i) => new Promise((resolve, reject) => {
      const child = spawn(php, [...args, 'worker', String(i)], { env, windowsHide: true });
      let out = ''; let err = '';
      child.stdout.on('data', x => { out += x; }); child.stderr.on('data', x => { err += x; });
      child.on('error', reject);
      child.on('close', code => code ? reject(Error(err || out)) : resolve(parse(out)));
    })));
    const result = parse(run('verify'));
    const successes = workers.filter(w => w.stock).length;
    console.log(JSON.stringify({ workers: workers.length, stock_successes: successes, ...result }, null, 2));
    if (successes !== 4 || result.remaining_stock !== 0 || result.balance !== 10 || result.ledger_rows !== 1 || !result.stale_admin_rejected || workers.some(w => !w.points)) throw Error('Concurrency invariant failed');
    console.log('PASS: no overselling, no cross-variant lost update, one points award, stale admin write rejected.');
  } finally { run('cleanup'); }
})().catch(error => { console.error(error.message); process.exitCode = 1; });
