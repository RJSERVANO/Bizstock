(() => {
 'use strict';const B=BizStockBusiness;
 const labels={'user.created':'Account created','user.updated':'Account updated','user.deactivated':'Account deactivated','user.password_reset':'Password reset','admin.emergency_recovery':'Administrator account recovered','account.photo_changed':'Profile photo changed','product.image_changed':'Product image changed','account.profile_updated':'Profile details changed','account.password_changed':'Password changed','development.seed':'Initial inventory added','product.created':'Product created','product.updated':'Product updated','product.archived':'Product archived','category.created':'Category created','category.updated':'Category updated','category.deleted':'Category deleted','supplier.created':'Supplier created','supplier.updated':'Supplier updated','supplier.deleted':'Supplier deleted'};
 const list=B.list({endpoint:'/audit-events',render:(tr,r)=>{const entity=r.after?.product_name||r.before?.product_name||r.after?.full_name||r.before?.full_name||r.after?.category_name||r.before?.category_name||r.after?.supplier_name||r.before?.supplier_name||`${r.entity_type==='user'?'Account':r.entity_type.charAt(0).toUpperCase()+r.entity_type.slice(1)}${r.entity_id?' #'+r.entity_id:''}`;
  [r.event_id,B.date(r.occurred_at),r.actor_name||(r.actor_id?`Team member #${r.actor_id}`:'System'),labels[r.action]||r.action.replaceAll(/[._]/g,' ').replace(/^./,c=>c.toUpperCase()),entity].forEach(v=>B.cell(tr,v));B.cell(tr,'').append(B.button('Details',()=>B.auditDetail(r)));
 }});
 list.load();
})();
