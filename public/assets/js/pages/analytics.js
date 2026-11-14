(() => {
 'use strict';
 const B=BizStockBusiness,main=document.querySelector('main'),form=main.querySelector('[data-search]');
 const metadata=main.querySelector('[data-analytics-metadata]'),warning=main.querySelector('[data-analytics-warning]');
 const viewNames=Object.fromEntries([...form.elements.group.options].map(o=>[o.value,o.textContent]));
 function shortcut(){
  if(!form.elements.period.value)return;
  const parts=new Intl.DateTimeFormat('en-CA',{timeZone:'Asia/Manila',year:'numeric',month:'2-digit',day:'2-digit'}).formatToParts(new Date());
  const date=Object.fromEntries(parts.map(p=>[p.type,p.value]));
  const end=date.year+'-'+date.month+'-'+date.day,start=new Date(end+'T00:00:00Z');
  start.setUTCDate(start.getUTCDate()-Number(form.elements.period.value)+1);
  form.elements.date_from.value=start.toISOString().slice(0,10);form.elements.date_to.value=end;
 }
 form.elements.period.addEventListener('change',shortcut);
 for(const name of ['date_from','date_to']){form.elements[name].required=true;form.elements[name].addEventListener('input',()=>{form.elements.period.value='';});}
 shortcut();
 const list=B.list({
  endpoint:'/analytics',items:r=>r.data.items,
  onLoading:()=>{metadata.hidden=true;warning.hidden=true;},
  render:(tr,row,requested)=>{
   const group=requested.get('group')||'top',unit=B.units[row.unit_code]||'Unspecified';
   const name=row.product_name?row.sku+' · '+row.product_name:group==='unit'?unit:row.group_name+' (#'+row.group_id+')';
   [name,unit,row.units_in??'—',row.units_out??'—',row.net_units??'—'].forEach(v=>B.cell(tr,v));
  },
  onLoaded:(r,requested)=>{
   const filters=r.data.filters,label=viewNames[filters.group];
   // Do not overwrite an edit made while the previous request was in flight.
   if(list.params().toString()===requested.toString()){
    form.elements.date_from.value=filters.date_from;form.elements.date_to.value=filters.date_to;
   }
   main.querySelector('[data-analysis-title]').textContent=label;
   main.querySelector('[data-analytics-context]').textContent=filters.date_from+' through '+filters.date_to+' · Manila · Snapshot '+B.date(r.data.snapshot_at);
   metadata.hidden=false;
   warning.hidden=!r.data.unclassified_events;
   warning.textContent=r.data.unclassified_events?String(r.data.unclassified_events)+' ambiguous historical '+(r.data.unclassified_events===1?'event is':'events are')+' excluded. Results may be incomplete; affected products are not labeled as having no movement.':'';
   const empty=main.querySelector('[data-empty]');
   empty.querySelector('h3').textContent=filters.group==='inactive'?'No products match this view':'No qualifying movements in this period';
   empty.querySelector('p').textContent='Try a different period or analysis view.'+(r.data.unclassified_events?' Some historical events were excluded; see the warning above.':'');
   main.querySelector('[data-results] caption').textContent=label+' · quantities by captured unit';
  }
 });
 list.load();
})();
