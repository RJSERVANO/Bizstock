const { chromium } = require('playwright');
const { AxeBuilder } = require('@axe-core/playwright');
const assert = require('assert/strict');
(async () => {
  const origin = process.env.STAGE7_ORIGIN; if (!origin) throw new Error('Disposable fixture required');
  const browser = await chromium.launch({channel:process.env.BIZSTOCK_BROWSER||'chrome',headless:true}); let checks=0;
  const check=(ok,label)=>{assert(ok,label);checks++;};
  const signin=async (page,role)=>{
    await page.goto(`${origin}/login`); await page.getByLabel(/^Username/).fill(role); await page.getByLabel(/^Password/).fill(process.env.STAGE7_PASSWORD);
    await page.getByRole('button',{name:'Sign in',exact:true}).click(); await page.waitForURL(`${origin}/dashboard`);await page.goto(`${origin}/account`);
  };
  const search=async (page,value)=>{await page.locator('#lookup-search').fill(value);await page.getByRole('button',{name:'Search',exact:true}).click();await page.locator('[data-loading]').waitFor({state:'hidden'});};
  try {
    const context=await browser.newContext(), page=await context.newPage(); const errors=[];page.on('pageerror',e=>errors.push(e.message));
    await signin(page,'admin');
    for(const resource of ['categories','suppliers']) {
      await page.goto(`${origin}/${resource}`); await page.locator('[data-loading]').waitFor({state:'hidden'});
      for(const width of [360,768,1440]) {
        await page.setViewportSize({width,height:900});
        check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'lookup responsive width');
        const result=await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze();
        check(result.violations.length===0,JSON.stringify(result.violations.map(v=>({id:v.id,nodes:v.nodes.map(n=>n.target)}))));
      }
      await page.getByRole('button',{name:resource==='categories'?'Add category':'Add supplier',exact:true}).click();
      await page.getByLabel(/^Name/).fill(resource==='categories'?'Browser category':'Browser supplier');
      if(resource==='suppliers')await page.getByLabel(/^Contact email/).fill('browser@example.com');
      await page.getByRole('button',{name:'Save',exact:true}).click();await page.locator('[data-editor]').waitFor({state:'hidden'});
      await search(page,'Browser');check(await page.locator('tbody tr').count()===1,'created record visible');
      await page.reload();await page.locator('[data-loading]').waitFor({state:'hidden'});await search(page,'Browser');
      check(await page.locator('tbody tr').count()===1,'persisted after reload');
      await page.getByRole('button',{name:'Edit',exact:true}).click();await page.getByLabel(/^Name/).fill(resource==='categories'?'Browser edited category':'Browser edited supplier');
      await page.getByRole('button',{name:'Save',exact:true}).click();await page.locator('[data-editor]').waitFor({state:'hidden'});await search(page,'Browser edited');
      check((await page.locator('tbody').textContent()).includes('Browser edited'),'edit persisted');
      await page.getByRole('button',{name:'Delete',exact:true}).click();await page.locator('#lookup-delete').waitFor({state:'visible'});
      check(await page.locator('#lookup-delete [data-close-dialog]').evaluate(el=>el===document.activeElement),'dialog focus');
      await page.locator('#lookup-delete .bs-button--danger').click();await page.locator('#lookup-delete').waitFor({state:'hidden'});await page.locator('[data-empty]').waitFor({state:'visible'});
      check(await page.locator('tbody tr').count()===0,'delete and empty state');
    }
    await page.goto(`${origin}/categories`);await page.locator('[data-loading]').waitFor({state:'hidden'});
    await search(page,'Edited Office');await page.getByRole('button',{name:'Delete',exact:true}).click();await page.locator('#lookup-delete .bs-button--danger').click();
    await page.locator('[data-message]').filter({hasText:'Reassign'}).waitFor();check(await page.locator('tbody tr').count()===1,'reference refusal retains row');
    await page.getByRole('button',{name:'Add category',exact:true}).click();await page.getByLabel(/^Name/).fill('Edited Office');await page.getByRole('button',{name:'Save',exact:true}).click();
    await page.locator('[data-form-errors]').waitFor({state:'visible'});check((await page.locator('[data-form-errors]').textContent()).includes('already exists'),'duplicate form error');
    check(await page.locator('[data-form-errors]').evaluate(el=>el===document.activeElement),'form error focus');
    await page.getByRole('button',{name:'Cancel',exact:true}).click();
    await page.route('**/api/v1/categories?**',async route=>{await new Promise(r=>setTimeout(r,500));await route.continue();});
    await page.getByRole('button',{name:'Refresh',exact:true}).click();check(await page.locator('[data-loading]').isVisible(),'actual loading state');await page.locator('[data-loading]').waitFor({state:'hidden'});await page.unrouteAll();
    await page.route('**/api/v1/categories?**',route=>route.abort());await page.getByRole('button',{name:'Refresh',exact:true}).click();await page.locator('[data-error]').waitFor({state:'visible'});check(await page.locator('[data-error]').isVisible(),'network error state');await page.unrouteAll();
    await page.getByRole('link',{name:'Try again',exact:true}).click();await page.locator('[data-loading]').waitFor({state:'hidden'});check(await page.locator('[data-results]').isVisible(),'error retry');
    await search(page,'Page');await page.getByRole('button',{name:'Next',exact:true}).click();await page.locator('[data-page-label]').filter({hasText:'Page 2'}).waitFor();check(await page.locator('tbody tr').count()>0,'pagination control');
    check(errors.length===0,'no browser exceptions');await context.close();
    for(const role of ['staff','viewer']) {
      const limited=await browser.newContext(), p=await limited.newPage();await signin(p,role);await p.goto(`${origin}/categories`);await p.locator('[data-loading]').waitFor({state:'hidden'});
      check(await p.locator('[data-add]').count()===0 && await p.locator('[data-editor]').count()===0,'read-only UI');
      if(role==='viewer') {check(await p.getByRole('link',{name:'Suppliers',exact:true}).count()===0,'Viewer supplier navigation omitted');const response=await p.goto(`${origin}/suppliers`);check(response.status()===403,'Viewer direct supplier page denied');}
      else {await p.goto(`${origin}/suppliers`);await p.locator('[data-loading]').waitFor({state:'hidden'});check((await p.locator('tbody').textContent()).includes('Renamed Supplier'),'Staff supplier page');}
      await limited.close();
    }
    console.log(`PASS: ${checks} real Chrome lookup assertions; six accessibility scans at 360/768/1440px.`);
  }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
