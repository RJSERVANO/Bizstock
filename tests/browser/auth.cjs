const { chromium } = require('playwright');
const { AxeBuilder } = require('@axe-core/playwright');
const assert = require('assert/strict');
const fs = require('node:fs'), path = require('node:path');
const screenshots=path.resolve('docs/visual/refinement/after');fs.mkdirSync(screenshots,{recursive:true});
(async () => {
  const origin = process.env.STAGE6_ORIGIN;
  if (!origin || !process.env.STAGE6_PASSWORD) throw new Error('Private disposable integration fixture required.');
  const browser = await chromium.launch({ channel: process.env.BIZSTOCK_BROWSER || 'chrome', headless: true });
  let checks = 0;
  const check = (value, message) => { assert(value, message); checks++; };
  try {
    const context = await browser.newContext(); const page = await context.newPage();
    const errors = []; page.on('pageerror', error => errors.push(error.message));
    for (const width of [360, 768, 1440]) {
      await page.setViewportSize({ width, height: 900 }); await page.goto(`${origin}/login`);
      check(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'login width');
      check((await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa']).analyze()).violations.length === 0, 'login accessibility');
    }
    await page.getByLabel('Username', { exact: false }).fill('browser');
    await page.getByLabel(/^Password/, { exact: false }).fill('incorrect');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.locator('[data-form-errors]').waitFor({ state: 'visible' });
    check((await page.locator('[data-form-errors]').textContent()).includes('Invalid username or password.'), 'generic login error');
    check(await page.locator('[data-form-errors]').evaluate(element => element === document.activeElement), 'error receives focus');
    for(const width of [360,768,1440]){await page.setViewportSize({width,height:1000});await page.screenshot({path:path.join(screenshots,`login-error-${width}.png`),fullPage:true});}
    await page.getByLabel(/^Password/, { exact: false }).fill(process.env.STAGE6_PASSWORD);
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL(`${origin}/account`);
    check(await page.locator('.app-shell').count()===0,'forced password change keeps restricted focused screen');
    check((await page.locator('main').textContent()).includes('<script>bad()</script> Browser'), 'database name escaped');
    check(await page.locator('script:not([src])').count() === 0, 'no injected script');
    const cookies = await context.cookies(); const session = cookies.find(cookie => cookie.name === 'bizstock_session');
    check(session.httpOnly && session.sameSite === 'Lax' && !session.secure && session.path === '/bizstock/', 'local HTTP cookie narrow exception');
    check(await page.evaluate(() => localStorage.length === 0 && sessionStorage.length === 0), 'no browser token storage');
    for (const width of [360, 768, 1440]) {
      await page.setViewportSize({ width, height: 900 });
      check(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'account width');
      check((await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa']).analyze()).violations.length === 0, 'account accessibility');
      await page.screenshot({path:path.join(screenshots,`forced-password-${width}.png`),fullPage:true});
    }
    await page.getByLabel(/^Current password/, { exact: false }).fill(process.env.STAGE6_PASSWORD);
    await page.getByLabel(/^New password/).fill(process.env.STAGE6_NEXT);
    await page.getByLabel(/^Confirm new password/, { exact: false }).fill('mismatch');
    await page.getByRole('button', { name: 'Change password', exact: true }).click();
    check((await page.locator('[data-form-errors]').textContent()).includes('do not match'), 'confirmation rejects mismatch');
    await page.getByLabel(/^Confirm new password/, { exact: false }).fill(process.env.STAGE6_NEXT);
    await page.getByRole('button', { name: 'Change password', exact: true }).click();
    await page.locator('[data-form-status]').waitFor({ state: 'visible' });
    check((await page.locator('[data-form-status]').textContent()).includes('Password changed'), 'password API wired');
    check(await page.locator('[data-forced-notice]').count() === 0, 'forced-change notice clears after success');
    check(await page.locator('.app-shell').count()===1 && await page.locator('.login-card nav').count()===0,'successful forced change enters shared shell without duplicated card navigation');
    const me = await page.evaluate(async () => (await new window.BizStockApi.ApiClient().request('/auth/me')).data);
    check(me.role === 'Viewer' && !me.must_change_password && !('password_hash' in me), 'real session unrestricted after password change');
    await page.getByRole('button', { name: 'Sign out', exact: true }).click(); await page.waitForURL(`${origin}/login`);
    await page.goto(`${origin}/account`); await page.waitForURL(`${origin}/login`);
    check(page.url() === `${origin}/login`, 'logout destroys authentication');
    await page.getByLabel('Username', { exact: false }).fill('browser');
    await page.getByLabel(/^Password/, { exact: false }).fill(process.env.STAGE6_NEXT);
    await page.getByRole('button', { name: 'Sign in', exact: true }).click(); await page.waitForURL(`${origin}/dashboard`);await page.goto(`${origin}/account`);
    check(page.url() === `${origin}/account`, 'changed password persists across login');
    await page.getByRole('button', { name: 'Sign out', exact: true }).click(); await page.waitForURL(`${origin}/login`);
    await page.route('**/api/v1/auth/login', route => route.fulfill({status:429,contentType:'application/json',headers:{'Retry-After':'2'},body:JSON.stringify({error:{code:'RATE_LIMITED',message:'Too many sign-in attempts. Try again later.'},request_id:'timer-test'})}));
    await page.getByLabel('Username', { exact: false }).fill('browser');
    await page.getByLabel(/^Password/, { exact: false }).fill(process.env.STAGE6_NEXT);
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.locator('.auth-countdown').waitFor({state:'visible'});
    check(await page.getByRole('button', {name:'Sign in',exact:true}).isDisabled(),'rate limit disables repeated submits');
    check((await page.getByRole('timer').textContent()).includes('0:02'),'visible server-header countdown');
    await page.setViewportSize({width:360,height:1000});await page.screenshot({path:path.join(screenshots,'login-countdown-360.png'),fullPage:true});
    check((await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa','wcag21aa']).analyze()).violations.length===0,'countdown accessibility');
    await page.locator('.auth-countdown').waitFor({state:'hidden'});
    check(await page.getByRole('button', {name:'Sign in',exact:true}).isEnabled(),'countdown expiry enables manual retry');
    check((await page.locator('[data-form-status]').textContent()).includes('try signing in again'),'expiry announced without automatic login');
    check(errors.length === 0, 'no page errors');
    console.log(`PASS: ${checks} real Chrome authentication assertions; seven accessibility scans including visible countdown.`);
    await context.close();
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exit(1); });
