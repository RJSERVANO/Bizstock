const fs=require('fs'),assert=require('assert/strict');
// JSON syntax is a strict YAML 1.2 subset; no optional YAML parser dependency needed.
const api=JSON.parse(fs.readFileSync('docs/openapi.yaml','utf8'));
assert.equal(api.openapi,'3.0.3');
for(const code of ['IDEMPOTENCY_CONFLICT','REVERSAL_CONFLICT','STALE_EDIT','INSUFFICIENT_STOCK'])assert(api.components.responses.Error409.description.includes(code),`Undocumented implemented conflict ${code}`);
assert(api.components.responses.Error422.description.includes('EXPORT_TOO_LARGE'));
let operations=0,implemented=0,references=0;
const resolve=reference=>reference.slice(2).split('/').reduce((value,key)=>value?.[key],api);
function walk(value){if(!value||typeof value!=='object')return;if(value.$ref){assert(value.$ref.startsWith('#/'));assert(resolve(value.$ref),'Unresolved reference '+value.$ref);references++;}for(const child of Object.values(value))walk(child);}
walk(api);const names=new Set();
for(const [path,item] of Object.entries(api.paths))for(const [method,op] of Object.entries(item)){
 operations++;assert(!names.has(op.operationId));names.add(op.operationId);assert(op.responses);assert(op['x-roles'].length);
 assert(['implemented','planned'].includes(op['x-implementation-status']));
 if(op['x-implementation-status']==='implemented'){implemented++;assert(['/health','/auth/csrf','/auth/login','/auth/logout','/auth/me','/account/password','/account/profile','/categories','/categories/{id}','/suppliers','/suppliers/{id}','/transactions','/transactions/{id}','/products','/products/{id}','/dashboard','/reports/inventory','/reports/transactions','/users','/users/{id}','/users/{id}/password-reset','/audit-events','/account/photo','/products/{id}/image'].includes(path));if(path==='/health'||path==='/auth/csrf')assert.deepEqual(op.security,[]);}else{
  assert(op.responses['404']);assert(op.summary.startsWith('Planned:'));
  if(['post','patch','delete'].includes(method))assert(op.security.some(s=>s.cookieSession&&s.csrfToken),'Write missing cookie/CSRF requirement');
 }
 if(path.includes('{id}'))assert(op.parameters.some(p=>p.$ref==='#/components/parameters/Id'));
}
assert.equal(implemented,39);assert.equal(operations,39);
for(const route of ['/auth/csrf','/auth/login','/auth/logout','/auth/me','/account/password','/dashboard','/products','/products/{id}','/categories','/suppliers','/transactions','/transactions/{id}','/reports/inventory','/reports/transactions','/users','/users/{id}','/users/{id}/password-reset','/audit-events'])assert(api.paths[route]);
for(const field of ['price','supplier_id','supplier_name','location','description','user_id'])assert(!api.components.schemas.ProductAvailability.properties[field]);
for(const route of ['/products','/transactions'])assert(api.paths[route].post.parameters.some(p=>p.$ref==='#/components/parameters/Key'));
for(const route of ['/reports/inventory','/reports/transactions'])assert(api.paths[route].get.responses['200'].content['text/csv']);
assert(!api.components.schemas.ProductPatch.properties.quantity);assert(!api.components.schemas.MovementCreate.properties.user_id);assert(!api.components.schemas.MovementCreate.properties.unit_price);
console.log(`PASS: ${operations} OpenAPI operation contracts, ${implemented} implemented operations, ${references} resolved references; permissions/CSRF/idempotency/CSV/field guards.`);

assert(!api.components.schemas.ProductDetail.required.includes('has_image'),'Legacy committed creation replay remains compatible');
