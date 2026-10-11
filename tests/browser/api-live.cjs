const { chromium }=require('playwright');
const { spawn }=require('child_process');const net=require('net');const path=require('path');const assert=require('assert/strict');
(async()=>{
 const port=await new Promise(resolve=>{const socket=net.createServer();socket.listen(0,'127.0.0.1',()=>{const port=socket.address().port;socket.close(()=>resolve(port));});});
 const server=spawn(process.env.BIZSTOCK_PHP||path.resolve('.tools/php/php.exe'),['-d','display_errors=0','-S',`127.0.0.1:${port}`,'-t','public','bin/router.php'],{env:{...process.env,APP_ENV:'local',APP_BASE_PATH:'/bizstock'},stdio:'ignore',windowsHide:true});let browser;
 try{
  for(let i=0;i<50;i++){try{const response=await fetch(`http://127.0.0.1:${port}/bizstock/api/v1/health`);if(response.ok)break;}catch{}await new Promise(resolve=>setTimeout(resolve,100));}
  browser=await chromium.launch({channel:process.env.BIZSTOCK_BROWSER||'chrome',headless:true});const page=await browser.newPage();
  const errors=[];page.on('pageerror',error=>errors.push(error.message));await page.goto(`http://127.0.0.1:${port}/bizstock/preview/dashboard`);
  const result=await page.evaluate(async()=>{
   const api=new window.BizStockApi.ApiClient();const health=await api.request('/health');let planned,query,write;
   try{await api.request('/products');}catch(error){planned=error.status;}
   try{await api.request('/health?unknown=1');}catch(error){query=[error.status,error.code,error.requestId];}
   try{await api.request('/products',{method:'POST',body:{},idempotencyKey:'one-logical-request'});}catch(error){write=error.status;}
   return {health,planned,query,write,base:api.root};
  });assert.equal(result.health.data.status,'ok');assert.match(result.health.requestId,/^[a-f0-9]{32}$/);assert.equal(result.base,'/bizstock/api/v1');assert.equal(result.planned,401);assert.equal(result.query[0],422);assert.equal(result.query[1],'VALIDATION_ERROR');assert.match(result.query[2],/^[a-f0-9]{32}$/);assert.equal(result.write,401);assert.deepEqual(errors,[]);
  console.log('PASS: real Chrome API client health/base path, correlated validation errors, actual CSRF bootstrap and unauthenticated route refusal; no page errors.');
 }finally{if(browser)await browser.close();server.kill();}
})().catch(error=>{console.error(error);process.exit(1);});
