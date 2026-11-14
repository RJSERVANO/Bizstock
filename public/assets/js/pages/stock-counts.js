(() => {
 'use strict';
 const B=BizStockBusiness,main=document.querySelector('main'),editor=main.querySelector('[data-count-editor]'),form=main.querySelector('[data-count-line]'),error=main.querySelector('[data-count-error]');
 const createPanel=main.querySelector('[data-count-create]'),createForm=main.querySelector('[data-count-create-form]'),createError=main.querySelector('[data-create-error]'),newButton=main.querySelector('[data-new-count]'),cancelCreate=main.querySelector('[data-cancel-create]');
 let count=null,product=null,busy=false,createIntent=null,approvalIntent=null,selection=0,createTrigger=newButton;
 const statusLabel=value=>value[0].toUpperCase()+value.slice(1);
 const badge=value=>B.tag(value,{draft:'neutral',submitted:'low',approved:'in',cancelled:'neutral'}[value]);
 function openCreate(trigger=newButton){createTrigger=trigger;createPanel.hidden=false;newButton.setAttribute('aria-expanded','true');createForm.querySelector('#count-note').focus();}
 function closeCreate(focus=true){if(busy||createIntent)return;createPanel.hidden=true;newButton.setAttribute('aria-expanded','false');if(focus)(createTrigger.getClientRects().length?createTrigger:newButton).focus();}
 newButton.addEventListener('click',()=>createPanel.hidden?openCreate():closeCreate());
 cancelCreate.addEventListener('click',()=>closeCreate());
 createPanel.addEventListener('keydown',e=>{if(e.key==='Escape'&&!busy&&!createIntent){e.preventDefault();closeCreate();}});
 const emptyAction=B.button('New count',event=>openCreate(event.currentTarget));emptyAction.className='bs-button bs-button--primary';
 // B.button callbacks receive the event through addEventListener.
 main.querySelector('[data-empty] .data-state').append(emptyAction);
 const resetEmpty=B.button('Reset filters',()=>main.querySelector('[data-reset-filters]').click());resetEmpty.className='bs-button bs-button--secondary';main.querySelector('[data-empty] .data-state').append(resetEmpty);
 const list=B.list({endpoint:'/stock-counts',render:(tr,row)=>{
  B.cell(tr,'#'+row.count_id);B.cell(tr,'').append(badge(row.status));B.cell(tr,'Team member #'+row.created_by);B.cell(tr,B.date(row.created_at));
  B.cell(tr,row.approved_at?'Approved '+B.date(row.approved_at)+' · Team member #'+row.approved_by:row.status==='submitted'?'Awaiting Admin review':row.status==='cancelled'?'Closed without approval':'Not submitted');
  B.cell(tr,'').append(B.button('Open count #'+row.count_id,()=>window.bizstockRequestLeave(form,()=>load(row.count_id))));
 },onLoaded:(r,requested)=>{
  const filtered=Boolean(requested.get('status')),empty=main.querySelector('[data-empty]');
  empty.querySelector('h3').textContent=filtered?'No counts match this filter':'No physical counts yet';
  empty.querySelector('p').textContent=filtered?'Choose another state or reset filters to see all counts.':'Start a count to compare physical quantities with recorded stock. Adjustments post only after Admin approval.';
  emptyAction.hidden=filtered;resetEmpty.hidden=!filtered;
 }});
 const fail=e=>{B.show(error,B.error(e));};
 function decision(){return {draft:'Save each counted quantity, then submit for Admin review. A reason is required for a nonzero discrepancy. No stock changes until approval.',submitted:'Only Admin can approve. All adjustments post together, with no movement for zero discrepancies. If stock or eligibility changed, return to draft and physically recount; editing quantity alone does not refresh the baseline.',approved:'Approved count: linked adjustments are recorded in History. This count cannot be edited or reopened.',cancelled:'Cancelled count: no approval was posted. This historical count cannot be edited or reopened.'}[count.status];}
 async function load(id){
  if(busy)return;selection++;count=null;product=null;editor.dataset.countId=String(id);editor.hidden=false;form.hidden=true;error.hidden=true;
  main.querySelector('[data-count-transitions]').replaceChildren();main.querySelector('[data-count-lines]').replaceChildren();main.querySelector('[data-count-summary]').textContent='Loading count #'+id+'…';main.querySelector('[data-count-decision]').textContent='';setBusy(true);
  try{
   const r=await B.api.request('/stock-counts/'+id);count=r.data;approvalIntent=null;form.reset();form.hidden=count.status!=='draft';form.dispatchEvent(new Event('bizstock:form-saved'));
   main.querySelector('[data-count-availability]').textContent='Choose a product to capture current stock revision.';
   main.querySelector('#count-heading').textContent='Count #'+count.count_id;
   const summary=main.querySelector('[data-count-summary]');summary.replaceChildren(badge(count.status),document.createTextNode(' · version '+count.version+' · '+(count.note||'No note')));
   main.querySelector('[data-count-decision]').textContent=decision();
   const rows=main.querySelector('[data-count-lines]');rows.replaceChildren();
   for(const line of count.lines){
    const tr=document.createElement('tr');
    for(const [index,value] of [line.sku_snapshot+' · '+line.product_name_snapshot+' · '+(B.units[line.unit_code]||'Unspecified'),line.expected_quantity,line.counted_quantity,line.discrepancy,line.reason||'None'].entries()){
     const td=B.cell(tr,value);if(index>=1&&index<=3)td.classList.add('numeric');
    }
    const td=B.cell(tr,'');if(line.adjustment_transaction_id){const a=document.createElement('a');a.href=B.base+'/history?movement_id='+line.adjustment_transaction_id;a.textContent='Movement #'+line.adjustment_transaction_id;td.append(a);}else td.textContent='No adjustment';rows.append(tr);
   }
   if(!count.lines.length){const tr=document.createElement('tr'),td=B.cell(tr,'No count lines yet. Find a product below to enter its physical quantity.');td.colSpan=6;rows.append(tr);}
   const actions=main.querySelector('[data-count-transitions]');actions.replaceChildren();
   for(const [action,label] of count.status==='draft'?[['submit','Submit for review'],['cancel','Cancel count']]:count.status==='submitted'?[['return_to_draft','Return to draft for recount'],['cancel','Cancel count'],...(main.dataset.role==='Admin'?[['approve','Approve all adjustments']]:[])]:[]){
    const button=B.button(label,()=>transition(action));button.className='bs-button bs-button--'+(action==='approve'||action==='submit'?'primary':action==='cancel'?'danger':'secondary');actions.append(button);
   }
   main.querySelector('#count-heading').focus();
  }catch(e){main.querySelector('[data-count-summary]').textContent='Count #'+id+' could not be loaded.';fail(e);}finally{setBusy(false);}
 }
 function setBusy(value){busy=value;editor.setAttribute('aria-busy',String(value));editor.querySelectorAll('button,input,select').forEach(el=>el.disabled=value);}
 async function transition(action){
  if(busy||!count)return;
  if(action==='submit'&&form.checkValidity()&&new FormData(form).get('counted_quantity')){B.show(error,'Save or discard the current line before submitting.');return;}
  setBusy(true);const body=approvalIntent?.body||{expected_version:count.version};if(action==='approve'&&!approvalIntent)approvalIntent={key:B.key(),body};
  try{await B.api.request('/stock-counts/'+count.count_id+'/'+action,{method:'POST',body,...(action==='approve'?{idempotencyKey:approvalIntent.key}:{})});approvalIntent=null;setBusy(false);await load(count.count_id);await list.load();}
  catch(e){fail(e);if(action==='approve'&&B.uncertain(e))error.append(document.createTextNode(' Retry Approve all adjustments unchanged; the same key is retained.'));else approvalIntent=null;}
  finally{setBusy(false);if(approvalIntent){editor.querySelectorAll('button').forEach(el=>el.disabled=!el.textContent.includes('Approve all'));form.querySelectorAll('input,select,button').forEach(el=>el.disabled=true);}}
 }
 async function createDraft(){
  if(busy)return;const note=createForm.querySelector('#count-note');if(!createIntent)createIntent={key:B.key(),body:{note:note.value.trim()||null}};
  busy=true;note.disabled=true;cancelCreate.disabled=true;newButton.disabled=true;createForm.querySelector('[data-create-count]').disabled=true;createError.hidden=true;
  try{const r=await B.api.request('/stock-counts',{method:'POST',body:createIntent.body,idempotencyKey:createIntent.key});createIntent=null;busy=false;createForm.reset();closeCreate(false);await load(r.data.count_id);await list.load();}
  catch(e){B.show(createError,B.error(e));if(!B.uncertain(e))createIntent=null;else createError.append(document.createTextNode(' Retry Create draft count unchanged; the same key is retained.'));}
  finally{busy=false;note.disabled=!!createIntent;cancelCreate.disabled=!!createIntent;newButton.disabled=!!createIntent;createForm.querySelector('[data-create-count]').disabled=false;}
 }
 createForm.addEventListener('submit',event=>{event.preventDefault();window.bizstockRequestLeave(form,createDraft);});
 main.querySelector('[data-reload-count]').addEventListener('click',()=>window.bizstockRequestLeave(form,()=>load(Number(editor.dataset.countId))));
 main.querySelector('[data-find-count-product]').addEventListener('click',async()=>{
  const select=form.querySelector('#count-product'),gen=++selection;product=null;
  try{const r=await B.api.request('/products?q='+encodeURIComponent(form.querySelector('#count-product-search').value.trim())+'&per_page=50');if(gen!==selection)return;select.replaceChildren(new Option('Choose a product',''));for(const p of r.data)select.add(new Option(p.sku+' · '+p.product_name,p.product_id));main.querySelector('[data-count-availability]').textContent=r.meta.total>50?'First 50 matches; refine your search.':'Choose a product.';}catch(e){fail(e);}
 });
 form.querySelector('#count-product-search').addEventListener('input',()=>{selection++;product=null;form.querySelector('#count-product').value='';});
 form.querySelector('#count-product').addEventListener('change',async()=>{
  const gen=++selection;product=null;
  try{const r=await B.api.request('/products/'+form.querySelector('#count-product').value);if(gen!==selection)return;product=r.data;main.querySelector('[data-count-availability]').textContent='Current '+product.quantity+' '+(B.units[product.unit_code]||'Unspecified')+' · stock revision '+product.stock_revision+'. Existing line baselines stay unchanged unless explicit recount is selected.';}catch(e){fail(e);}
 });
 form.addEventListener('submit',async event=>{
  event.preventDefault();if(busy||!product)return;setBusy(true);
  try{const r=await B.api.request('/stock-counts/'+count.count_id+'/lines/'+product.product_id,{method:'PATCH',body:{expected_version:count.version,expected_stock_revision:product.stock_revision,counted_quantity:Number(form.querySelector('#counted_quantity').value),reason:form.querySelector('#count-reason').value.trim()||null,recount:form.querySelector('#count-recount').checked}});form.dispatchEvent(new Event('bizstock:form-saved'));setBusy(false);await load(r.data.count_id);await list.load();}catch(e){fail(e);}finally{setBusy(false);}
 });
 list.load();const id=new URLSearchParams(location.search).get('count_id');if(id&&/^[1-9][0-9]{0,9}$/.test(id))load(id);
})();
