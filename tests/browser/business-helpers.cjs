const assert=require('assert/strict');const {AxeBuilder}=require('@axe-core/playwright');
module.exports=()=>{let checks=0;const origin=process.env.BUSINESS_ORIGIN;return{
 origin,check:(v,m)=>{assert(v,m);checks++;},count:()=>checks,
 login:async(page,user='test1')=>{await page.goto(`${origin}/login`);await page.getByLabel(/^Username/).fill(user);await page.getByLabel(/^Password/).fill(process.env.BUSINESS_PASSWORD);await page.getByRole('button',{name:'Sign in',exact:true}).click();await page.waitForURL(`${origin}/dashboard`);await page.goto(`${origin}/account`);},
 api:async(page,path,body,key)=>{const csrf=(await(await page.request.get(`${origin}/api/v1/auth/csrf`)).json()).data.csrf_token;const response=body?await page.request.post(`${origin}/api/v1${path}`,{data:body,headers:{'X-CSRF-Token':csrf,'Idempotency-Key':key}}):await page.request.get(`${origin}/api/v1${path}`);return{status:response.status(),json:await response.json()};},
 axe:async(page)=>{const result=await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze();assert.deepEqual(result.violations.map(v=>({id:v.id,nodes:v.nodes.map(n=>n.target)})),[]);checks++;},
};};
