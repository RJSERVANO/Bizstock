const {chromium}=require('playwright');
const fs=require('fs');const H=require('./business-helpers.cjs')();
(async()=>{const browser=await chromium.launch({channel:'chrome'});const page=await browser.newPage();const phase=process.env.CAPTURE_PHASE||'before',dir=`docs/visual/usability/${phase}`;fs.mkdirSync(dir,{recursive:true});
for(const width of [360,768,1440]){await page.setViewportSize({width,height:960});await page.goto(`${H.origin}/login`);await page.screenshot({path:`${dir}/login-${width}.png`,fullPage:true});}
await H.login(page);for(const width of [360,768,1440]){await page.setViewportSize({width,height:960});for(const route of ['account','history','movements','dashboard']){await page.goto(`${H.origin}/${route}`);await page.waitForTimeout(400);await page.screenshot({path:`${dir}/${route}-${width}.png`,fullPage:true});}}
await browser.close();console.log(`PASS: 15 ${phase} rendered screenshots.`);})().catch(e=>{console.error(e);process.exitCode=1;});
