const fs=require('fs'),{chromium}=require('playwright'),H=require('./business-helpers.cjs')();
const phase=process.env.UI_REVIEW_PHASE||'after',dir=`docs/evidence/workspace-ui/${phase}`;
(async()=>{fs.mkdirSync(dir,{recursive:true});const b=await chromium.launch({channel:'chrome',headless:true}),ctx=await b.newContext(),p=await ctx.newPage();
try{await H.login(p);const pages=['dashboard','products','products/new','products/1/edit','movements','history','categories','suppliers','reports/inventory','reports/transactions','account','users','audit-events','analytics','stock-counts','help'];
 const reviews=[];
 for(const route of pages){await p.setViewportSize({width:1440,height:1000});await p.goto(H.origin+'/'+route);if(await p.locator('[data-loading]').count())await p.locator('[data-loading]').waitFor({state:'hidden'});await p.waitForLoadState('networkidle');await p.screenshot({path:dir+'/'+route.replaceAll('/','-')+'-1440.png',fullPage:true});reviews.push({page:route,desktop_overflow:await p.evaluate(()=>document.documentElement.scrollWidth>innerWidth)});}
 for(const route of ['analytics','help','stock-counts'])for(const width of [360,768]){await p.setViewportSize({width,height:1000});await p.goto(H.origin+'/'+route);if(await p.locator('[data-loading]').count())await p.locator('[data-loading]').waitFor({state:'hidden'});await p.waitForLoadState('networkidle');await p.screenshot({path:dir+'/'+route+'-'+width+'.png',fullPage:true});}
 for(const id of [1,2,3,4]){await p.goto(H.origin+'/stock-counts?count_id='+id);await p.locator('[data-count-summary]').getByText(/version/).waitFor();await p.screenshot({path:dir+'/count-'+id+'-768.png',fullPage:true});}
 await p.goto(H.origin+'/');await p.screenshot({path:dir+'/home-768.png',fullPage:true});
 const anon=await b.newContext(),login=await anon.newPage();await login.setViewportSize({width:1440,height:1000});await login.goto(H.origin+'/login');await login.screenshot({path:dir+'/login-1440.png',fullPage:true});await anon.close();
 fs.writeFileSync(dir+'/page-review.json',JSON.stringify(reviews,null,2));
 if(phase==='after'){await require('./workspace-ui-checks.cjs')({b,p,ctx,H,dir});}
 console.log('PASS: '+phase+' workspace UI screenshots; '+H.count()+' browser/accessibility assertions.');
}finally{await b.close();}})().catch(e=>{console.error(e);process.exit(1);});
