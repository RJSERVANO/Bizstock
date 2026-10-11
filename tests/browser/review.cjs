/* Stage 3 real-browser review. Fixtures only; never attempts database access. */
const { chromium } = require('playwright');
const { default: AxeBuilder } = require('@axe-core/playwright');
const { spawn } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const net = require('node:net');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '../..');
const output = path.join(root, 'docs/visual/stage3');
fs.mkdirSync(output, { recursive: true });
const php = process.env.BIZSTOCK_PHP || path.join(root, '.tools/php/php.exe');
const portSocket = net.createServer();
let server, browser, checks = 0;
const results = [];
const check = (condition, message) => { assert.ok(condition, message); checks++; };
const wait = ms => new Promise(resolve => setTimeout(resolve, ms));
const overflow = page => page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);

(async () => {
  await new Promise(resolve => portSocket.listen(0, '127.0.0.1', resolve));
  const port = portSocket.address().port;
  await new Promise(resolve => portSocket.close(resolve));
  const base = process.env.BIZSTOCK_TEST_BASE || '';
  server = spawn(php, ['-d', 'display_errors=0', '-S', `127.0.0.1:${port}`, '-t', 'public', 'bin/router.php'],
    { cwd: root, env: { ...process.env, APP_ENV: 'test', APP_BASE_PATH: base }, windowsHide: true, stdio: 'ignore' });
  let ready = false;
  for (let attempt = 0; attempt < 50; attempt++) {
    try { const response = await fetch(`http://127.0.0.1:${port}${base}/api/v1/health`); if (response.ok) { ready = true; break; } } catch {}
    await wait(100);
  }
  check(ready, 'PHP browser test server ready');
  browser = await chromium.launch({ channel: process.env.BIZSTOCK_BROWSER || 'chrome', headless: true });
  const context = await browser.newContext({ reducedMotion: 'reduce' });
  const page = await context.newPage();
  const jsErrors = [];
  page.on('pageerror', error => jsErrors.push(error.message));
  const origin = `http://127.0.0.1:${port}${base}`;
  async function visit(route, width) {
    await page.setViewportSize({ width, height: width === 768 ? 1024 : 900 });
    const response = await page.goto(origin + route);
    check(response.status() === 200, `render ${route} @${width}`);
    await page.evaluate(() => document.fonts.ready);
    check(!await overflow(page), `no page clipping ${route} @${width}`);
    check(jsErrors.length === 0, `no JS error ${route}: ${jsErrors}`);
  }
  const routes = { dashboard: '/preview/dashboard', products: '/preview/products', login: '/preview/login', form: '/preview/products/new', gallery: '/preview/components', viewer: '/preview/viewer/products' };
  for (const width of [360, 768, 1440]) {
    for (const [name, route] of Object.entries(routes)) {
      await visit(route, width);
      const axe = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa']).analyze();
      results.push({ screen: name, width, state: 'populated', violations: axe.violations.map(v => ({ id: v.id, impact: v.impact, nodes: v.nodes.map(n => n.target) })) });
      if (axe.violations.length) throw new Error(`axe ${name}@${width}: ${JSON.stringify(results.at(-1))}`);
      checks++;
      await page.screenshot({ path: path.join(output, `${name}-${width}.png`), fullPage: true, animations: 'disabled' });
    }
    for (const name of ['dashboard', 'products', 'login', 'form']) {
      for (const state of ['loading', 'empty', 'error', 'restricted']) {
        await visit(routes[name] + '?state=' + state, width);
        const axe = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa']).analyze();
        results.push({ screen: name, width, state, violations: axe.violations.map(v => ({ id: v.id, nodes: v.nodes.map(n => n.target) })) });
        check(axe.violations.length === 0, `axe ${name}/${state}@${width}: ${JSON.stringify(axe.violations.map(v => v.id))}`);
        await page.screenshot({ path: path.join(output, `${name}-${state}-${width}.png`), fullPage: true, animations: 'disabled' });
      }
    }
  }
  for (const width of [480, 900, 1024]) {
    for (const route of Object.values(routes)) await visit(route, width);
  }
  await visit('/preview/products', 1440);
  check(await page.locator('[data-product-row]:visible').first().locator('td').first().evaluate(el => getComputedStyle(el).whiteSpace === 'nowrap'), 'SKU stays on one line');
  await page.locator('#product-search').fill('not-a-product');
  check(await page.locator('[data-no-results]').isVisible(), 'search empty state');
  await page.getByRole('button', { name: 'Clear filters' }).click();
  check(await page.locator('[data-product-row]:visible').count() === 4, 'pagination first page');
  await page.getByRole('button', { name: 'Next', exact: true }).click();
  check(await page.locator('[data-product-row]:visible').count() === 2, 'pagination second page');
  await page.locator('#status-filter').selectOption('low');
  check(await page.locator('[data-product-row]:visible').count() === 3, 'low filter includes zero and threshold equality');
  await page.locator('[data-sort="quantity"]').click();
  check(await page.locator('th[aria-sort="ascending"]').count() === 1, 'sort state announced');
  check(await page.locator('[data-product-row]:visible').first().getAttribute('data-quantity') === '0', 'numeric sort');
  await page.locator('#category-filter').selectOption('Office supplies');
  check(await page.locator('[data-product-row]:visible').count() === 1, 'combined filters');
  await visit('/preview/products', 360);
  check(await page.locator('[data-product-row]:visible .mobile-stock-summary').first().isVisible(), 'quantity and threshold discoverable on mobile');
  const region = page.locator('.table-scroll');
  await region.focus(); await page.keyboard.press('ArrowRight'); await wait(200);
  check(await region.evaluate(el => el.scrollLeft > 0), 'table scrolls from keyboard');
  await visit('/preview/components', 1440);
  for (const [name, expected] of [['Primary', 'rgb(30, 64, 175)'], ['Secondary', 'rgb(241, 245, 249)'], ['Danger', 'rgb(153, 27, 27)'], ['Ghost', 'rgb(241, 245, 249)']]) {
    const button = page.getByRole('button', { name, exact: true });
    await button.hover();
    check(await button.evaluate(el => getComputedStyle(el).backgroundColor) === expected, 'button hover ' + name);
    await button.focus(); await page.keyboard.press('Tab'); await page.keyboard.press('Shift+Tab');
    check(await button.evaluate(el => getComputedStyle(el).outlineWidth === '3px'), 'visible keyboard focus ' + name);
  }
  await page.screenshot({ path: path.join(output, 'gallery-focus-1440.png'), fullPage: true });
  check(await page.getByRole('button', { name: 'Disabled', exact: true }).isDisabled(), 'disabled state');
  check(await page.getByRole('button', { name: 'Saving…', exact: true }).getAttribute('aria-busy') === 'true', 'busy state');
  check(await page.locator('.bs-button[aria-busy="true"]').evaluate(el => getComputedStyle(el, '::before').animationName === 'none'), 'reduced motion disables spinner');

  await visit('/preview/dashboard', 360);
  const menu = page.getByRole('button', { name: 'Open navigation' });
  await menu.click();
  check(await page.locator('#sidebar').getAttribute('aria-modal') === 'true', 'mobile drawer modal');
  check(await page.locator('#workspace').evaluate(el => el.inert), 'background inert');
  const drawerAxe = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa']).analyze();
  check(drawerAxe.violations.length === 0, `drawer accessibility: ${JSON.stringify(drawerAxe.violations.map(v=>v.id))}`);
  await page.screenshot({ path: path.join(output, 'drawer-360.png'), fullPage: true });
  await page.keyboard.press('Shift+Tab');
  check(await page.locator('#sidebar').evaluate(el => el.contains(document.activeElement)), 'drawer reverse focus trap');
  for (let i = 0; i < 12; i++) await page.keyboard.press('Tab');
  check(await page.locator('#sidebar').evaluate(el => el.contains(document.activeElement)), 'drawer forward focus trap');
  await page.keyboard.press('Escape');
  check(await menu.evaluate(el => el === document.activeElement), 'drawer focus restored');

  await visit('/preview/login', 360);
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  check(await page.locator('[data-form-errors]').isVisible(), 'login error summary');
  check(await page.locator('#username').getAttribute('aria-invalid') === 'true', 'login field errors');
  await page.screenshot({ path: path.join(output, 'login-validation-360.png'), fullPage: true });
  await page.locator('#username').fill('fixture-owner'); await page.locator('#password').fill('not-a-real-password');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  check(await page.locator('#login-review').isVisible(), 'login fixture notice rather than session');
  await page.keyboard.press('Escape');
  check(await page.getByRole('button', { name: 'Sign in', exact: true }).evaluate(el => el === document.activeElement), 'dialog focus restored');

  await visit('/preview/products/new', 768);
  await page.getByRole('button', { name: 'Save product' }).click();
  check(await page.locator('[data-form-errors]').isVisible(), 'product validation summary');
  await page.locator('#product-name').fill('Fixture paper'); await page.locator('#sku').fill('OFF-TEST');
  await page.locator('#category').selectOption('office'); await page.locator('#supplier').selectOption('central');
  await page.locator('#price').fill('245.00');
  await page.getByRole('button', { name: 'Save product' }).click();
  check(await page.locator('#product-review').isVisible(), 'valid product review, no save');
  const dialogAxe = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa']).analyze();
  check(dialogAxe.violations.length === 0, 'dialog accessibility');
  await page.screenshot({ path: path.join(output, 'product-review-768.png'), fullPage: true });
  await page.keyboard.press('Escape');
  await page.getByRole('link', { name: 'Cancel', exact: true }).click();
  check(await page.locator('#unsaved-review').isVisible(), 'unsaved navigation warning');
  await page.getByRole('button', { name: 'Keep editing' }).click();
  check(page.url().endsWith('/preview/products/new'), 'keep editing preserves route');
  await page.getByRole('link', { name: 'Cancel', exact: true }).click();
  await page.getByRole('button', { name: 'Discard changes' }).click();
  await page.waitForURL('**/preview/products');
  checks++;
  await visit('/preview/products/new?mode=edit', 360);
  check(await page.locator('#current-quantity').getAttribute('readonly') !== null && await page.locator('#sku').getAttribute('readonly') !== null, 'edit quantity/SKU readonly');
  await visit('/preview/viewer/dashboard', 1440);
  check(await page.getByText('Recent movements', { exact: true }).count() === 0, 'Viewer history absent');
  check(await page.getByText('Suppliers', { exact: true }).count() === 0, 'Viewer supplier navigation absent');
  const tokens = await page.evaluate(() => { const s = getComputedStyle(document.documentElement); return ['--primary', '--low-text', '--low-bg', '--out-text', '--out-bg', '--in-text', '--in-bg'].map(k => s.getPropertyValue(k).trim()); });
  function luminance(hex) { const rgb = hex.slice(1).match(/../g).map(h => parseInt(h,16)/255).map(v=>v<=.04045?v/12.92:((v+.055)/1.055)**2.4); return rgb[0]*.2126+rgb[1]*.7152+rgb[2]*.0722; }
  function contrast(a,b) { const x=luminance(a), y=luminance(b); return (Math.max(x,y)+.05)/(Math.min(x,y)+.05); }
  const ratios = { primary: contrast(tokens[0], '#ffffff'), low: contrast(tokens[1],tokens[2]), out: contrast(tokens[3],tokens[4]), in: contrast(tokens[5],tokens[6]) };
  check(Object.values(ratios).every(v=>v>=4.5), 'text token contrast at least 4.5:1');
  check(await page.evaluate(() => window.matchMedia('(prefers-reduced-motion: reduce)').matches), 'reduced motion preference');
  check(jsErrors.length === 0, 'no page errors across review');
  fs.writeFileSync(path.join(output, 'results.json'), JSON.stringify({ checks, browser: browser.version(), base, screenshots: fs.readdirSync(output).filter(n=>n.endsWith('.png')).length, ratios, results }, null, 2));
  console.log(`PASS: ${checks} browser assertions; ${results.length} axe screen/state/viewport scans; screenshots in docs/visual/stage3; browser ${browser.version()}`);
})().catch(error => { fs.writeFileSync(path.join(output,'failure.json'), JSON.stringify({ message: error.message, results },null,2)); console.error(error); process.exitCode=1; }).finally(async () => { if(browser) await browser.close(); if(server) server.kill(); });
