(() => {
 'use strict';const form=document.querySelector('[data-profile-form]');if(!form)return;
 const api=new BizStockApi.ApiClient(),message=form.querySelector('[data-profile-message]');let busy=false;
 form.addEventListener('submit',async event=>{event.preventDefault();if(busy)return;busy=true;message.hidden=true;form.querySelectorAll('input,button').forEach(el=>el.disabled=true);form.setAttribute('aria-busy','true');
  try{const result=await api.request('/account/profile',{method:'PATCH',body:{full_name:form.elements.full_name.value}});const name=result.data.full_name;form.elements.full_name.value=name;document.querySelectorAll('[data-own-name]').forEach(el=>el.textContent=name);document.querySelectorAll('[data-avatar-initial]').forEach(el=>el.textContent=Array.from(name)[0]||'');BizStockUI.showMessage(message,'Personal details saved.');}
  catch(error){BizStockUI.showMessage(message,error.message+(error.requestId?' Reference: '+error.requestId:''),true);}
  finally{busy=false;form.setAttribute('aria-busy','false');form.querySelectorAll('input,button').forEach(el=>el.disabled=false);}
 });
})();
