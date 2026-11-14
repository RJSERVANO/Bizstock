const fs=require('fs');
module.exports=async({b,p,H,dir})=>{
 let scans=0;
 const check=H.check,axe=async page=>{await H.axe(page);scans++;};
 const ready=async(page=p)=>{if(await page.locator('[data-loading]').count())await page.locator('[data-loading]').waitFor({state:'hidden'});await page.waitForLoadState('networkidle');};
 const shot=async(name,page=p)=>page.screenshot({path:dir+'/'+name+'.png',fullPage:true});
 for(const route of ['analytics','help','stock-counts'])for(const width of [360,768,1440]){
  await p.setViewportSize({width,height:1000});await p.goto(H.origin+'/'+route);await ready();
  check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),route+' fits '+width);await axe(p);
 }
 await p.goto(H.origin+'/analytics');await ready();
 check(await p.title()==='Movement analytics · BizStock','consistent analytics title');
 check(await p.locator('#date_from').inputValue()!==''&&await p.locator('#date_to').inputValue()!=='','shortcut displays applied dates');
 await p.getByText('How these figures are calculated',{exact:true}).focus();await p.keyboard.press('Enter');
 check(await p.locator('.methodology').getAttribute('open')!==null,'calculation disclosure keyboard');
 check(await p.locator('.methodology summary').evaluate(e=>getComputedStyle(e).outlineStyle!=='none'),'visible keyboard focus');
 await p.locator('#date_from').fill('2026-11-01');await p.locator('#date_to').fill('2026-11-14');
 check(await p.locator('#period').inputValue()==='','date editing marks custom');
 for(const group of ['product','category','unit','inactive','top']){
  await p.locator('#group').selectOption(group);await p.getByRole('button',{name:'Apply filters'}).click();await ready();
  check(await p.locator('[data-results]').isVisible(),group+' populated result');
  check((await p.locator('[data-analysis-title]').textContent())===(await p.locator('#group option:checked').textContent()),'applied analysis label');
 }
 check(await p.locator('[data-results] td.numeric').count()>0,'quantity alignment');
 await p.locator('#date_from').fill('2030-01-01');await p.locator('#date_to').fill('2030-01-02');await p.getByRole('button',{name:'Apply filters'}).click();await ready();
 check(await p.getByRole('heading',{name:'No qualifying movements in this period'}).isVisible(),'specific movement empty state');await shot('analytics-empty-1440');
 await p.locator('[data-reset-filters]').click();await ready();
 check(await p.locator('#period').inputValue()==='7'&&await p.locator('#date_from').inputValue()!=='','reset synchronizes default period');
 await p.locator('#period').selectOption('30');await p.getByRole('button',{name:'Apply filters'}).click();await ready();check(await p.locator('#period').inputValue()==='30','30-day shortcut');
 await p.route('**/api/v1/analytics?**',async route=>{const response=await route.fetch(),json=await response.json();json.data.unclassified_events=2;json.data.items=[];json.meta.total=0;json.meta.total_pages=0;await route.fulfill({response,json});});
 await p.locator('[data-refresh]').click();await ready();check(await p.locator('[data-analytics-warning]').isVisible(),'visible exclusions even empty');check((await p.locator('[data-empty]').textContent()).includes('historical events were excluded'),'exclusion-specific empty hint');await shot('analytics-warning-empty');await axe(p);await p.unroute('**/api/v1/analytics?**');
 let release;await p.route('**/api/v1/analytics?**',async route=>{await new Promise(resolve=>release=resolve);await route.continue();});
 await p.locator('[data-refresh]').click();await p.locator('[data-loading]').waitFor();check(await p.locator('[data-analytics-metadata]').isHidden(),'old metadata hidden while loading');release();await ready();await p.unroute('**/api/v1/analytics?**');
 await p.route('**/api/v1/analytics?**',route=>route.abort('failed'));await p.locator('[data-refresh]').click();await p.locator('[data-error]').waitFor();check(true,'recoverable analytics error');await shot('analytics-error');await p.unroute('**/api/v1/analytics?**');await p.locator('[data-refresh]').click();await ready();check(await p.locator('[data-results]').isVisible(),'refresh recovers analytics');
 await p.goto(H.origin+'/stock-counts');await ready();
 const newCount=p.getByRole('button',{name:'New count',exact:true});await newCount.focus();await p.keyboard.press('Enter');
 check(await p.locator('#count-note').evaluate(e=>e===document.activeElement),'creation opens with note focus');
 await p.keyboard.press('Escape');check(await newCount.evaluate(e=>e===document.activeElement),'creation cancel restores focus');
 await newCount.click();await p.locator('#count-note').fill('Keyboard count note');
 await p.route('**/api/v1/stock-counts',async route=>{if(route.request().method()==='POST')return route.abort('failed');return route.continue();});
 await p.getByRole('button',{name:'Create draft count',exact:true}).click();await p.locator('[data-create-error]').waitFor();
 check(await p.locator('#count-note').inputValue()==='Keyboard count note','create failure retains note');check(await p.locator('#count-note').isDisabled(),'uncertain create freezes payload');await shot('count-create-retry');
 await p.unroute('**/api/v1/stock-counts');await p.getByRole('button',{name:'Create draft count',exact:true}).click();
 await p.locator('[data-count-summary]').getByText(/draft/).waitFor();check(await p.locator('#count-heading').evaluate(e=>e===document.activeElement),'creation focuses new count');check(await p.locator('[data-count-create]').isHidden(),'creation panel closes after success');
 await p.locator('#status').selectOption('approved');await p.getByRole('button',{name:'Search',exact:true}).click();await ready();check(await p.locator('[data-results] tbody tr').count()===1,'status filter');
 await p.locator('[data-reset-filters]').click();await ready();check(await p.locator('[data-results] tbody tr').count()===5,'count reset');
 await p.goto(H.origin+'/stock-counts?count_id=1');await p.locator('[data-count-summary]').getByText(/draft/).waitFor();
 await p.locator('#count-product-search').fill('UI-001');await p.locator('[data-find-count-product]').click();await p.locator('#count-product option').nth(1).waitFor({state:'attached'});await p.locator('#count-product').selectOption('1');await p.locator('[data-count-availability]').getByText(/Current/).waitFor();
 await p.locator('#counted_quantity').fill('17');await p.locator('#count-reason').fill('Retain this recount explanation');
 await p.route('**/api/v1/stock-counts/1/lines/1',route=>route.abort('failed'));await p.getByRole('button',{name:'Save count line'}).click();await p.locator('[data-count-error]').waitFor();
 check(await p.locator('#counted_quantity').inputValue()==='17'&&await p.locator('#count-reason').inputValue()==='Retain this recount explanation','line save error preserves inputs');await shot('count-line-recoverable-error');await p.unroute('**/api/v1/stock-counts/1/lines/1');
 await p.locator('[data-reload-count]').click();await p.getByRole('button',{name:'Discard changes',exact:true}).click();await p.locator('[data-count-availability]').getByText('Choose a product to capture current stock revision.',{exact:true}).waitFor();check(true,'unsaved count reload requires explicit discard');
 for(const [id,status] of [[1,'draft'],[2,'submitted'],[3,'approved'],[4,'cancelled']]){
  await p.goto(H.origin+'/stock-counts?count_id='+id);await p.locator('[data-count-summary]').getByText(new RegExp(status)).waitFor();
  check(await p.locator('[data-count-line]').isVisible()===(status==='draft'),status+' edit eligibility');
  check((await p.locator('[data-count-decision]').textContent()).length>30,status+' decision guidance');
  for(const width of [360,768,1440]){await p.setViewportSize({width,height:1000});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),status+' responsive '+width);await shot('count-'+status+'-'+width);}
  await axe(p);
 }
 await p.route('**/api/v1/stock-counts?**',async route=>{const response=await route.fetch(),json=await response.json();json.data=[];json.meta.total=0;json.meta.total_pages=0;await route.fulfill({response,json});});
 await p.goto(H.origin+'/stock-counts');await ready();check(await p.getByRole('heading',{name:'No physical counts yet'}).isVisible(),'initial counts empty state');check(await p.locator('[data-empty]').getByRole('button',{name:'New count'}).isVisible(),'empty create action');await shot('counts-empty');
 await p.locator('#status').selectOption('approved');await p.getByRole('button',{name:'Search',exact:true}).click();await ready();check(await p.getByRole('heading',{name:'No counts match this filter'}).isVisible(),'filtered counts empty state');check(await p.locator('[data-empty]').getByRole('button',{name:'Reset filters'}).isVisible(),'empty reset action');await shot('counts-filter-empty');await p.unroute('**/api/v1/stock-counts?**');
 await p.route('**/api/v1/stock-counts?**',route=>route.abort('failed'));await p.locator('[data-refresh]').click();await p.locator('[data-error]').waitFor();check(true,'count list distinct recoverable error');await p.unroute('**/api/v1/stock-counts?**');await p.locator('[data-refresh]').click();await ready();check(await p.locator('[data-results]').isVisible(),'count list refresh recovery');
 for(let index=0;index<21;index++){const result=await H.api(p,'/stock-counts',{note:'Disposable pagination fixture '+index},'ui-pagination-'+index);if(result.status!==201)throw Error('Disposable pagination setup failed');}
 await p.goto(H.origin+'/stock-counts');await ready();check(await p.locator('[data-results] tbody tr').count()===25,'count first page bounded');
 await p.locator('[data-next]').click();await ready();check(await p.locator('[data-page-label]').textContent()==='Page 2 of 2 · 26 records','count next page metadata');check(await p.locator('[data-results] tbody tr').count()===1,'count final page');
 await p.locator('[data-previous]').click();await ready();check(await p.locator('[data-results] tbody tr').count()===25,'count previous page');
 for(const [user,role] of [['test1','Admin'],['test2','Staff'],['test5','Viewer']]){
  const context=await b.newContext(),page=await context.newPage();await H.login(page,user);await page.goto(H.origin+'/help');
  check(await page.locator('.guide-section .guide-section').count()===0,'no nested Help cards');
  check(await page.locator('#help-admin').count()===(role==='Admin'?1:0),'role-specific administrative help');
  check(await page.locator('#help-counts').count()===(role==='Viewer'?0:1),'role-specific count help');
  for(const link of await page.locator('.help-topics a').all()){const href=await link.getAttribute('href');check(await page.locator(href).count()===1,'topic target exists');}
  await page.locator('.help-topics a').first().focus();await page.keyboard.press('Enter');check(new URL(page.url()).hash==='#help-find','keyboard topic anchor');
  await page.screenshot({path:dir+'/help-'+role.toLowerCase()+'.png',fullPage:true});await axe(page);
  for(const route of ['/analytics','/stock-counts','/users','/audit-events']){
   const response=await page.request.get(H.origin+route,{maxRedirects:0});check(response.status()===(role==='Viewer'||(role==='Staff'&&['/users','/audit-events'].includes(route))?403:200),'direct page restriction '+role+route);
  }
  for(const route of ['/analytics','/stock-counts','/users','/audit-events']){const response=await page.request.get(H.origin+'/api/v1'+route);check(response.status()===(role==='Viewer'||(role==='Staff'&&['/users','/audit-events'].includes(route))?403:200),'direct API restriction '+role+route);}
  await context.close();
 }
 const widePages=['dashboard','products','products/new','products/1/edit','movements','history','categories','suppliers','reports/inventory','reports/transactions','account','users','audit-events'];
 for(const route of widePages){await p.goto(H.origin+'/'+route);await ready();for(const width of [360,768,1440]){await p.setViewportSize({width,height:1000});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),route+' shared consumer '+width);await shot(route.replaceAll('/','-')+'-'+width);}await axe(p);}
 // Inspect public/auth layouts without changing installed credentials or submitting a new password.
 const auth=await b.newContext(),authPage=await auth.newPage();
 for(const route of ['/','/login']){await authPage.goto(H.origin+route);for(const width of [360,768,1440]){await authPage.setViewportSize({width,height:1000});check(await authPage.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'public/auth layout '+route+' '+width);await shot((route==='/'?'home-public':'login')+'-'+width,authPage);}await axe(authPage);}
 await authPage.getByLabel(/^Username/).fill('test4');await authPage.getByLabel(/^Password/).fill(process.env.BUSINESS_PASSWORD);await authPage.getByRole('button',{name:'Sign in',exact:true}).click();await authPage.waitForURL(H.origin+'/account');
 check(await authPage.locator('[data-forced-notice]').isVisible(),'forced password guidance');for(const width of [360,768,1440]){await authPage.setViewportSize({width,height:1000});check(await authPage.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'forced password layout '+width);await shot('forced-password-'+width,authPage);}await axe(authPage);await auth.close();
 // Browser zoom cannot be inferred from CSS zoom or deviceScaleFactor. Report it unverified here.
 fs.mkdirSync('docs/testing',{recursive:true});fs.writeFileSync('docs/testing/workspace-ui-results.json',JSON.stringify({browser:'Chrome headless',assertions:H.count(),clean_axe_scans:scans,widths:[360,768,1440],fixture:'randomized disposable MariaDB; installed records not used',native_browser_zoom:'Not performed',manual_screen_reader:'Not performed'},null,2)+'\n');
};
