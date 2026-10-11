const {chromium}=require('playwright');
const {AxeBuilder}=require('@axe-core/playwright');
const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const origin=process.env.BUSINESS_ORIGIN,phase=process.env.UI_PHASE||'after';
const output=path.resolve(`docs/visual/refinement/${phase}`);fs.mkdirSync(output,{recursive:true});
(async()=>{const browser=await chromium.launch({channel:'chrome',headless:true});let checks=0,scans=0;const computed={};try{
 const context=await browser.newContext(),page=await context.newPage();
 await page.goto(`${origin}/login`);
 for(const width of [360,768,1440]){await page.setViewportSize({width,height:1000});await page.screenshot({path:path.join(output,`login-${width}.png`),fullPage:true});if(phase==='after'){const result=await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa','wcag21aa']).analyze();assert.equal(result.violations.length,0);scans++;}}
 await page.getByLabel('Username',{exact:false}).fill('test1');await page.getByLabel(/^Password/,{exact:false}).fill(process.env.BUSINESS_PASSWORD);await page.getByRole('button',{name:'Sign in',exact:true}).click();await page.waitForURL(`${origin}/dashboard`);await page.goto(`${origin}/account`);
 for(const screen of ['dashboard','products','products/new','movements','history','reports/inventory','reports/transactions','categories','suppliers','users','audit-events','account']){
  await page.goto(`${origin}/${screen}`);await page.locator('[data-loading]').waitFor({state:'hidden'}).catch(()=>{});
  for(const width of [360,768,1440]){await page.setViewportSize({width,height:1000});assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),`${screen} ${width} overflow`);checks++;
   await page.screenshot({path:path.join(output,`${screen.replaceAll('/','-')}-${width}.png`),fullPage:true});
   if(phase==='after'){const result=await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze();assert.equal(result.violations.length,0,JSON.stringify(result.violations.map(v=>({id:v.id,nodes:v.nodes.map(n=>n.target)}))));scans++;}
  }
  computed[screen]=await page.evaluate(()=>{const styles=el=>{const s=getComputedStyle(el);return {font:s.fontFamily,size:s.fontSize,color:s.color,background:s.backgroundColor,padding:s.padding,display:s.display}};return {body:styles(document.body),heading:styles(document.querySelector('h1')),stylesheets:[...document.styleSheets].map(s=>s.href),filter:document.querySelector('[data-search]')?styles(document.querySelector('[data-search]')):null};});
 }
 if(phase==='after') {
  await page.goto(`${origin}/products`);await page.locator('[data-loading]').waitFor({state:'hidden'});
  assert.equal(await page.locator('.advanced-filters').getAttribute('open'),null);checks++;
  await page.getByText('More filters and sorting',{exact:true}).click();
  for(const name of ['supplier_id','active','low_stock','sort','direction'])assert(await page.locator(`[name="${name}"]`).isVisible(),`${name} preserved`);checks+=5;
  await page.getByLabel('Order',{exact:true}).selectOption('desc');await page.getByRole('button',{name:'Search',exact:true}).click();await page.locator('[data-loading]').waitFor({state:'hidden'});checks++;
  await page.goto(`${origin}/reports/transactions`);await page.locator('[data-loading]').waitFor({state:'hidden'});
  const name=page.getByLabel('Find product by name or SKU',{exact:true});await name.fill('Everyday');await page.locator('#product_id-name-search-choices option').first().waitFor({state:'attached'});const choice=await page.locator('#product_id-name-search-choices option').first().getAttribute('value');await name.fill(choice);await name.dispatchEvent('change');await page.getByRole('button',{name:'Apply filters',exact:true}).click();await page.locator('[data-loading]').waitFor({state:'hidden'});assert((await page.locator('tbody').textContent()).includes('Everyday'));assert.equal(await page.locator('tbody tr').count(),1);checks+=2;
  await page.goto(`${origin}/users`);await page.locator('[data-loading]').waitFor({state:'hidden'});assert(await page.locator('[data-user-editor]').isHidden());await page.getByRole('button',{name:'Add account',exact:true}).click();assert(await page.locator('[data-user-editor]').isVisible());await page.getByRole('button',{name:'Cancel',exact:true}).click();assert(await page.locator('[data-user-editor]').isHidden());checks+=3;
  for(const [screen,endpoint] of [['products','products'],['history','transactions'],['reports/inventory','reports/inventory'],['reports/transactions','reports/transactions'],['categories','categories'],['suppliers','suppliers'],['users','users'],['audit-events','audit-events']]){
   await page.goto(`${origin}/${screen}`);await page.locator('[data-loading]').waitFor({state:'hidden'});
   const form=page.locator('[data-search]');if(await form.locator('[name="q"]').count())await form.locator('[name="q"]').fill('No matching isolated record');else{await form.locator('.advanced-filters summary').click();await form.locator('[name="user_id"], [name="actor_id"]').fill('999999');}
   await form.locator('[type="submit"]').click();await page.locator('[data-empty]').waitFor({state:'visible'});
   for(const width of [360,768,1440]){await page.setViewportSize({width,height:1000});await page.screenshot({path:path.join(output,`${screen.replaceAll('/','-')}-empty-${width}.png`),fullPage:true});assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));checks++;const result=await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa','wcag21aa']).analyze();assert.equal(result.violations.length,0,JSON.stringify(result.violations.map(v=>v.id)));scans++;}
   let release;const gate=new Promise(resolve=>release=resolve);const pattern=`**/api/v1/${endpoint}?*`;
   await page.route(pattern,async route=>{await gate;await route.fulfill({status:503,contentType:'application/json',body:JSON.stringify({error:{code:'UNAVAILABLE',message:'Please try again shortly.'},request_id:'visual-error-reference'})});});
   await page.goto(`${origin}/${screen}`);await page.locator('[data-loading]').waitFor({state:'visible'});
   for(const width of [360,768,1440]){await page.setViewportSize({width,height:1000});await page.screenshot({path:path.join(output,`${screen.replaceAll('/','-')}-loading-${width}.png`),fullPage:true});assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));checks++;const result=await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa','wcag21aa']).analyze();assert.equal(result.violations.length,0);scans++;}
   release();await page.locator('[data-error]').waitFor({state:'visible'});assert(await page.locator('[data-message][role="alert"]').isVisible());assert(await page.locator('.diagnostic-reference').isVisible());assert.equal(await page.locator('.diagnostic-reference').getAttribute('open'),null);checks+=3;
   for(const width of [360,768,1440]){await page.setViewportSize({width,height:1000});await page.screenshot({path:path.join(output,`${screen.replaceAll('/','-')}-error-${width}.png`),fullPage:true});assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));checks++;const result=await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa','wcag21aa']).analyze();assert.equal(result.violations.length,0);scans++;}
   await page.unroute(pattern);await page.locator('[data-refresh]').click();await page.locator('[data-results]').waitFor({state:'visible'});checks++;
  }
 }
 fs.writeFileSync(path.join(output,'computed-styles.json'),JSON.stringify(computed,null,2));
 fs.writeFileSync(path.join(output,'results.json'),JSON.stringify({phase,checks,scans,screenshots:fs.readdirSync(output).filter(x=>x.endsWith('.png')).length,states:'empty via actual filters; loading/error via explicit browser fault injection'},null,2));
 console.log(`PASS UI ${phase}: ${checks} checks; ${scans} axe scans; ${fs.readdirSync(output).filter(x=>x.endsWith('.png')).length} actual rendered screenshots.`);
 await context.close();
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exit(1);});
