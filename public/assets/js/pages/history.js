(() => {
 'use strict';const B=BizStockBusiness,main=document.querySelector('main'),form=main.querySelector('[data-search]');
 async function view(id){try{const row=(await B.api.request('/transactions/'+id)).data;B.detail(row);const root=document.querySelector('#detail-review [data-dialog-summary]');for(const [label,key] of [['Original','correction_of_transaction_id'],['Reversal','reversed_by_transaction_id']])if(row[key])root.append(B.button('View '+label+' #'+row[key],()=>view(row[key])));}catch(e){B.show(main.querySelector('[data-message]'),B.error(e));}}
 const list=B.list({endpoint:'/transactions',render:(tr,row)=>{
  B.cell(tr,row.transaction_id);B.cell(tr,B.date(row.transaction_date));B.cell(tr,row.sku+' \u00b7 '+row.product_name).classList.add('history-product');
  const badge=document.createElement('span');badge.className='movement-badge movement-badge--'+row.type.toLowerCase();badge.textContent=row.type;B.cell(tr,'').append(badge);
  B.cell(tr,row.quantity);B.cell(tr,B.money(row.unit_price));B.cell(tr,row.actor_name).classList.add('history-actor');
  const links=B.cell(tr,'');if(row.correction_of_transaction_id)links.append(B.button('Reverses #'+row.correction_of_transaction_id,()=>view(row.correction_of_transaction_id)));else if(row.reversed_by_transaction_id)links.append(B.button('Reversed by #'+row.reversed_by_transaction_id,()=>view(row.reversed_by_transaction_id)));else links.textContent='Original';
  const td=B.cell(tr,'');td.append(B.button('Details',()=>view(row.transaction_id)));if(main.dataset.role==='Admin'&&!row.correction_of_transaction_id&&!row.reversed_by_transaction_id){const link=document.createElement('a');link.className='bs-button bs-button--ghost';link.textContent='Reverse';link.href=B.base+'/movements?reversal_id='+row.transaction_id;td.append(link);}
 }});
 (async()=>{await B.options(form.elements.category_id,'categories','category_name');const q=new URLSearchParams(location.search);for(const [k,v] of q)if(form.elements[k])form.elements[k].value=v;await list.load();if(q.has('movement_id'))await view(q.get('movement_id'));})().catch(e=>B.failure(main,e));
})();
