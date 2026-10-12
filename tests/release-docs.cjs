const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const progress = fs.readFileSync('docs/progress.md', 'utf8');
for (let stage = 1; stage <= 16; stage++) {
  assert.match(progress, new RegExp(`^- \\[${stage <= 15 ? 'x' : ' '}\\] ${stage}\\.`, 'm'));
}
const gaps = fs.readFileSync('docs/gaps.md', 'utf8').split('\n').filter(line => /^\| GAP-\d+ \|/.test(line));
assert.equal(gaps.length, 17);
for (const row of gaps) assert.equal(row.split('|').length, 9);
const evidence = fs.readFileSync('docs/test-plan.md', 'utf8');
for (let id = 1; id <= 33; id++) assert.match(evidence, new RegExp(`REQ-${String(id).padStart(2, '0')}\\b`));
assert.match(fs.readFileSync('docs/user-testing.md', 'utf8'), /pending/i);
const blueprint = fs.readFileSync('docs/BizStock-Codex-Build-Blueprint.md');
assert.match(blueprint.toString(),/Authorized scope amendment/);
assert.equal(crypto.createHash('sha256').update(blueprint).digest('hex'), '6b7e8ce25b685c548c0f44513ad348fae06444c2bd80baac13abc557e639325d');
let links = 0;
for (const file of ['README.md', ...fs.readdirSync('docs').filter(name => name.endsWith('.md')).map(name => `docs/${name}`)]) {
  const content = fs.readFileSync(file, 'utf8');
  for (const match of content.matchAll(/\]\(([^\s)]+)\)/g)) {
    const target = match[1].split('#')[0];
    if (!target || /^(https?:|app:|mailto:)/.test(target) || path.isAbsolute(target)) continue;
    assert.ok(fs.existsSync(path.resolve(path.dirname(file), target)), `${file}: missing ${target}`);
    links++;
  }
}
const workflow = fs.readFileSync('.github/workflows/verify.yml', 'utf8');
assert.match(workflow, /workflow_dispatch/);
assert.match(workflow, /self-hosted/);
console.log(`PASS release documentation: 16 stage flags, 17 gap rows, 33 requirement mappings, ${links} local links, pinned authorized blueprint amendment and manual CI definition. Pending targets are not execution passes.`);

