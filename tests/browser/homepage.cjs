const {chromium}=require('playwright');
const {AxeBuilder}=require('@axe-core/playwright');
const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path');
const H=require('./business-helpers.cjs')();
const output=path.resolve('docs/visual/homepage/after');fs.mkdirSync(output,{recursive:true});
(async()=>{
 const browser=await chromium.launch({channel:'chrome',headless:true});let scans=0;const measurements=[];
 const axe=async page=>{const r=await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze();assert.deepEqual(r.violations.map(v=>({id:v.id,nodes:v.nodes.map(n=>n.target)})),[]);scans++;};
 const shot=async(p,name)=>p.screenshot({path:path.join(output,name+'.png'),fullPage:true});
 const fit=async p=>H.check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'whole-page width without clipping');
 try {
  const context=await browser.newContext(),page=await context.newPage();const errors=[],privateCalls=[];page.on('pageerror',e=>errors.push(e.message));page.on('request',r=>{if(page.url()===H.origin+'/'&&r.url().includes('/api/v1/'))privateCalls.push(r.url());});
  for(const width of [360,768,1440]) {
   await page.setViewportSize({width,height:1000});await page.goto(H.origin+'/');await fit(page);await axe(page);await shot(page,'home-'+width);
   H.check(await page.getByRole('heading',{level:1}).textContent()==='Know your stock. Run your business with confidence.','exact hero');
   H.check(await page.locator('.stockroom-photo img').evaluate(el=>el.complete&&el.naturalWidth>0),'local responsive image loads');
   H.check(await page.getByRole('navigation',{name:'Public navigation'}).getByRole('link').count()===4,'public navigation');
   await page.getByRole('link',{name:'How it works',exact:true}).click();H.check(page.url().endsWith('#workflow'),'workflow anchor');
   await page.goto(H.origin+'/login');await fit(page);await axe(page);await shot(page,'login-'+width);
   H.check(await page.locator('[data-auth-form]').count()===1&&await page.locator('[data-role-hint]').count()===3,'single shared form and three role guidance controls');
   await page.getByRole('button',{name:'Viewer',exact:true}).click();H.check(await page.getByLabel(/^Username/).inputValue()===''&&await page.locator('[name="role"]').count()===0,'role hints do not prefill or switch access');
   await page.getByRole('link',{name:'Back to home'}).click();H.check(page.url()===H.origin+'/','back home supports base path');
  }
  H.check(privateCalls.length===0,'anonymous home makes no private API/image requests');
  await page.emulateMedia({reducedMotion:'reduce'});await page.goto(H.origin+'/');
  H.check(await page.locator('.stockroom-photo').evaluate(el=>getComputedStyle(el).animationName==='none'),'reduced motion disables entry');
  await page.locator('.public-actions .public-button').hover();H.check(await page.locator('.public-actions .public-button').evaluate(el=>getComputedStyle(el).transform==='none'),'reduced motion disables hover translation');
  await page.emulateMedia({reducedMotion:'no-preference'});
  for(const [username,role] of [['test1','Admin'],['test2','Staff'],['test5','Viewer']]) {
   await page.goto(H.origin+'/login');await page.getByLabel(/^Username/).fill(username);await page.getByLabel(/^Password/).fill(process.env.BUSINESS_PASSWORD);await page.getByRole('button',{name:'Sign in',exact:true}).click();await page.waitForURL(H.origin+'/dashboard');
   H.check(page.url()===H.origin+'/dashboard','normal '+role+' login opens real dashboard');
   await page.goto(H.origin+'/');H.check(await page.locator('.public-header').getByRole('link',{name:'Open dashboard',exact:true}).count()===1,'signed '+role+' home CTA');
   H.check(!(await page.locator('main').innerHTML()).includes(username),'signed public page has no private account');
   await page.goto(H.origin+'/account');await page.locator('[data-own-avatar]').first().waitFor();
   for(const width of [360,768,1440]) {await page.setViewportSize({width,height:1000});await fit(page);await axe(page);await shot(page,'account-'+role.toLowerCase()+'-'+width);}
   if(role==='Admin') {
    await page.setViewportSize({width:1440,height:1000});
    const icons=await page.locator('.sidebar .nav-link').evaluateAll(els=>Object.fromEntries(els.map(el=>[el.getAttribute('aria-label'),el.querySelector('svg').getAttribute('data-icon')])));
    H.check(icons['Your account']==='user'&&icons.Users==='users'&&icons['Audit log']==='audit'&&icons.History==='history','four distinct outline icons');
    await page.locator('[data-sidebar-toggle]').focus();await page.keyboard.press('Enter');H.check(await page.locator('html').evaluate(el=>el.classList.contains('sidebar-compact')),'keyboard rail toggle');
    const axes=await page.locator('.sidebar').evaluate(el=>{const center=s=>{const r=el.querySelector(s).getBoundingClientRect();return r.x+r.width/2;};return [center('.brand-mark'),center('[data-sidebar-toggle]'),...[...el.querySelectorAll('.nav-link svg')].map(e=>{const r=e.getBoundingClientRect();return r.x+r.width/2;})];});
    measurements.push({width:1440,mode:'collapsed',centers:axes});H.check(Math.max(...axes)-Math.min(...axes)<1,'logo toggle every icon share one horizontal center');
    await shot(page,'account-collapsed-1440');await axe(page);
    const users=page.locator('.sidebar').getByRole('link',{name:'Users',exact:true});await users.focus();H.check(await users.getAttribute('data-tooltip')==='Users','named collapsed tooltip');
    H.check(await users.evaluate(el=>getComputedStyle(el,'::after').content.includes('Users')),'tooltip visible on keyboard focus');await page.keyboard.press('Escape');H.check(await users.evaluate(el=>getComputedStyle(el,'::after').display==='none'),'Escape dismisses tooltip');
    await page.reload();H.check(await page.locator('html').evaluate(el=>el.classList.contains('sidebar-compact')),'rail preference survives reload');
    await page.locator('[data-sidebar-toggle]').click();await shot(page,'account-expanded-1440');
    const expanded=await page.locator('.drawer-heading').evaluate(el=>{const r=e=>e.getBoundingClientRect();return [...el.querySelectorAll('.brand-mark,.brand > span:last-child,[data-sidebar-toggle]')].map(e=>({x:r(e).x,y:r(e).y,w:r(e).width,h:r(e).height}));});measurements.push({width:1440,mode:'expanded',boxes:expanded});H.check(expanded[0].x+expanded[0].w<=expanded[1].x&&expanded[1].x+expanded[1].w<=expanded[2].x,'expanded brand and toggle never overlap');
    await page.goto(H.origin+'/products');await page.locator('[data-results]').waitFor({state:'visible'});
    const fullWidth=await page.locator('.workspace').evaluate(el=>el.getBoundingClientRect().width);
    await page.locator('[data-sidebar-toggle]').click();
    const railWidth=await page.locator('.workspace').evaluate(el=>el.getBoundingClientRect().width);
    H.check(Math.abs(railWidth-fullWidth-168)<1,'inventory workspace expands by the sidebar width difference');
    H.check(await page.locator('.table-scroll').evaluate(el=>{const r=el.getBoundingClientRect(),w=document.querySelector('.workspace').getBoundingClientRect();return r.left>=w.left&&r.right<=w.right;}),'inventory table scroll region stays inside expanded workspace');
    await fit(page);await page.locator('[data-sidebar-toggle]').click();await page.goto(H.origin+'/account');
    for(const width of [360,768]) {
     await page.setViewportSize({width,height:1000});await page.locator('[data-drawer-open]').focus();await page.keyboard.press('Enter');H.check(await page.locator('.sidebar').evaluate(el=>el.classList.contains('is-open')),'mobile keyboard drawer opens');await axe(page);await shot(page,'drawer-'+width);
     const last=page.locator('.sidebar .nav-link').last();await last.focus();await page.keyboard.press('Tab');H.check(await page.locator('.sidebar .brand').evaluate(el=>el===document.activeElement),'drawer Tab focus wraps');
     await page.keyboard.press('Escape');H.check(await page.locator('[data-drawer-open]').evaluate(el=>el===document.activeElement),'drawer dismissal restores focus');await fit(page);
    }
   }
   await page.locator('[data-auth-logout] button').click();await page.waitForURL(H.origin+'/login');
  }
  H.check(errors.length===0,'no browser script errors');fs.writeFileSync(path.join(output,'layout-evidence.json'),JSON.stringify(measurements,null,2));
  console.log(`PASS: ${H.count()} homepage/navigation/login Chrome assertions; ${scans} clean WCAG axe scans.`);
  await context.close();
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
