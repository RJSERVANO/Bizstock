/* Shared accessible controls. No API, auth, stock writes or browser persistence. */
(() => {
  'use strict';
  document.querySelectorAll('nav[aria-label="Main"] a[href]').forEach(link => { if(new URL(link.href).pathname === location.pathname) link.setAttribute('aria-current','page'); });
  const focusable = root => [...root.querySelectorAll('a[href], button:not(:disabled), input:not(:disabled), select:not(:disabled), textarea:not(:disabled), summary, [tabindex="0"]')].filter(el => el.getClientRects().length);
  const announce = message => { document.getElementById('ui-announcement').textContent = message; };
  const sidebar = document.getElementById('sidebar');
  sidebar?.querySelector('.brand')?.setAttribute('aria-label','BizStock dashboard');
  const toggle=document.querySelector('[data-sidebar-toggle]');
  sidebar?.querySelectorAll('.nav-link').forEach(link=>{const label=link.textContent.trim();link.setAttribute('aria-label',label);link.dataset.tooltip=label;const span=document.createElement('span');span.className='nav-text';[...link.childNodes].filter(n=>n.nodeType===3).forEach(n=>span.append(n));link.append(span);});
  const rail=value=>{document.documentElement.classList.toggle('sidebar-compact',value);toggle?.setAttribute('aria-expanded',String(!value));toggle?.setAttribute('aria-label',value?'Expand sidebar':'Collapse sidebar');};
  try{rail(localStorage.getItem('bizstock.sidebar')==='compact');}catch{}
  toggle?.addEventListener('click',()=>{const value=!document.documentElement.classList.contains('sidebar-compact');rail(value);try{localStorage.setItem('bizstock.sidebar',value?'compact':'full');}catch{}});
  sidebar?.querySelectorAll('.nav-link').forEach(link=>{link.addEventListener('focus',()=>link.classList.remove('tooltip-dismissed'));link.addEventListener('pointerleave',()=>link.classList.remove('tooltip-dismissed'));});
  document.addEventListener('keydown',event=>{if(event.key==='Escape'&&document.documentElement.classList.contains('sidebar-compact'))sidebar?.querySelectorAll('.nav-link').forEach(link=>link.classList.add('tooltip-dismissed'));});
  const opener = document.querySelector('[data-drawer-open]');
  if(sidebar){const tip=document.createElement('div');tip.id='navigation-tooltip';tip.className='nav-tooltip';tip.setAttribute('role','tooltip');tip.hidden=true;document.body.append(tip);const hide=()=>{tip.hidden=true;sidebar.querySelectorAll('[aria-describedby="navigation-tooltip"]').forEach(link=>link.removeAttribute('aria-describedby'));};sidebar.querySelectorAll('.nav-link').forEach(link=>{const show=()=>{if(!document.documentElement.classList.contains('sidebar-compact')||innerWidth<=900)return;hide();tip.textContent=link.dataset.tooltip;tip.hidden=false;const r=link.getBoundingClientRect();tip.style.left=(r.right+10)+'px';tip.style.top=Math.max(8,Math.min(innerHeight-tip.offsetHeight-8,r.top))+'px';link.setAttribute('aria-describedby',tip.id);};link.addEventListener('focus',show);link.addEventListener('pointerenter',show);link.addEventListener('blur',hide);link.addEventListener('pointerleave',hide);});sidebar.querySelector('nav').addEventListener('scroll',()=>{hide();const active=document.activeElement;if(active?.matches('#sidebar .nav-link'))active.dispatchEvent(new Event('focus'));});window.addEventListener('resize',hide);toggle?.addEventListener('click',hide);document.addEventListener('keydown',e=>{if(e.key==='Escape')hide();});}
  document.querySelector('.skip-link')?.addEventListener('click',()=>document.getElementById('main-content')?.focus());
  const workspace = document.getElementById('workspace');
  const backdrop = document.querySelector('[data-drawer-backdrop]');
  function closeDrawer() {
    if (!sidebar?.classList.contains('is-open')) return;
    sidebar.classList.remove('is-open'); sidebar.removeAttribute('role'); sidebar.removeAttribute('aria-modal');
    backdrop.hidden = true; workspace.inert = false; document.body.classList.remove('drawer-open');
    opener.setAttribute('aria-expanded', 'false'); (opener.getClientRects().length?opener:toggle)?.focus();
  }
  opener?.addEventListener('click', () => {
    sidebar.classList.add('is-open'); sidebar.setAttribute('role', 'dialog'); sidebar.setAttribute('aria-modal', 'true');
    backdrop.hidden = false; workspace.inert = true; document.body.classList.add('drawer-open');
    opener.setAttribute('aria-expanded', 'true'); sidebar.querySelector('[data-drawer-close]').focus();
  });
  document.querySelector('[data-drawer-close]')?.addEventListener('click', closeDrawer);
  backdrop?.addEventListener('click', closeDrawer);
  window.matchMedia('(min-width: 901px)').addEventListener('change', event => { if (event.matches) closeDrawer(); });
  document.addEventListener('keydown', event => {
    if (!sidebar?.classList.contains('is-open')) return;
    if (event.key === 'Escape') { event.preventDefault(); closeDrawer(); }
    if (event.key === 'Tab') {
      const items = focusable(sidebar), first = items[0], last = items.at(-1);
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    }
  });
  const returnFocus = new WeakMap();
  const openDialog = (id, trigger = document.activeElement) => {
    const dialog = document.getElementById(id);
    if (!dialog || dialog.open) return;
    returnFocus.set(dialog, trigger); dialog.showModal();
    dialog.querySelector('[data-close-dialog]')?.focus();
  };
  document.querySelectorAll('dialog').forEach(dialog => {
    dialog.querySelector('[data-close-dialog]')?.addEventListener('click', () => dialog.close());
    dialog.querySelector('.dialog-actions button')?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => returnFocus.get(dialog)?.focus());
  });
  document.addEventListener('click', event => {
    const trigger = event.target.closest('[data-open-dialog]');
    if (trigger) openDialog(trigger.dataset.openDialog, trigger);
  });
  const validate = form => {
    let errors = 0;
    const summary = form.querySelector('[data-form-errors]');
    form.querySelectorAll('input, select, textarea').forEach(field => {
      const error = document.getElementById(field.id + '-error');
      const invalid = !field.checkValidity() || (field.required && !field.value.trim());
      field.setAttribute('aria-invalid', String(invalid));
      if (error) { error.hidden = !invalid; error.textContent = invalid ? (field.validity.valueMissing || !field.value.trim() ? 'This field is required.' : 'Enter a valid value within the allowed format or range.') : ''; }
      if (invalid) errors++;
    });
    summary.hidden = !errors; summary.className = 'notice notice--error';
    if (errors) { summary.textContent = `Check ${errors} highlighted ${errors === 1 ? 'field' : 'fields'} before continuing.`; summary.focus(); }
    return !errors;
  };
  document.querySelectorAll('[data-preview-form]').forEach(form => {
    form.addEventListener('submit', event => {
      event.preventDefault();
      if (!validate(form)) return;
      const kind = form.dataset.previewForm;
      const id = kind === 'product' ? 'product-review' : kind === 'login' ? 'login-review' : 'gallery-review';
      if (kind === 'product') {
        document.querySelector('#product-review [data-dialog-summary]').textContent = `${form.querySelector('#product-name').value.trim()} · SKU ${form.querySelector('#sku').value.trim().toUpperCase()}`;
      }
      openDialog(id, form.querySelector('[type="submit"]'));
    });
  });
  const unsavedForm = document.querySelector('[data-unsaved-form]');
  if (unsavedForm) {
    const snapshot=()=>JSON.stringify([...new FormData(unsavedForm)]);let baseline=snapshot(),pendingLeave=null;
    const dirty=()=>!unsavedForm.closest('[hidden]')&&snapshot()!==baseline;
    unsavedForm.addEventListener('bizstock:form-saved', () => { baseline=snapshot(); });
    window.addEventListener('beforeunload', event => { if (dirty()) { event.preventDefault(); event.returnValue = ''; } });
    const dialog = document.getElementById('unsaved-review');
    const actions = dialog.querySelector('.dialog-actions');
    actions.querySelector('button').textContent = 'Keep editing';
    const leave = document.createElement('button'); leave.type = 'button'; leave.className = 'bs-button bs-button--danger'; leave.textContent = 'Discard changes';
    leave.addEventListener('click', () => { baseline=snapshot();const action=pendingLeave;pendingLeave=null;dialog.close();action?.(); }); actions.append(leave);
    window.bizstockRequestLeave=(form,action,trigger=document.activeElement)=>{if(form!==unsavedForm||!dirty()){action();return;}pendingLeave=action;openDialog('unsaved-review',trigger);};
    document.addEventListener('click', event => {
      const link = event.target.closest('a[href]');
      if (dirty() && link && !link.hasAttribute('data-form-return') && !link.getAttribute('href').startsWith('#')) { event.preventDefault();window.bizstockRequestLeave(unsavedForm,()=>{unsavedForm.querySelectorAll('input[type="password"]').forEach(input=>input.value='');window.location.assign(link.href);},link); }
    });
  }
  function showMessage(node, text, isError = false) {
    const value=String(text),match=value.match(/\s*Reference:\s*([A-Za-z0-9-]+)/);
    node.replaceChildren(document.createTextNode(match?value.replace(match[0],''):value));
    node.setAttribute('role',isError||!!match||node.matches('[data-form-errors],[data-reset-error]')?'alert':'status');
    if(match){const details=document.createElement('details'),summary=document.createElement('summary'),code=document.createElement('code');details.className='diagnostic-reference';summary.textContent='Support reference';code.textContent=match[1];details.append(summary,code);node.append(details);}
    node.hidden=false;node.focus();
  }
  document.querySelector('[data-shell-logout]')?.addEventListener('click',async event=>{
    const button=event.currentTarget;button.disabled=true;
    try{await new BizStockApi.ApiClient().request('/auth/logout',{method:'POST',body:{}});location.assign(`${document.querySelector('meta[name="app-base-path"]').content}/login`);}
    catch(error){showMessage(document.querySelector('[data-shell-error]'),error.message,true);button.disabled=false;}
  });
  document.querySelector('.account-menu')?.addEventListener('keydown',event=>{if(event.key==='Escape'){event.currentTarget.open=false;event.currentTarget.querySelector('summary').focus();}});
  // All existing controls remain in the form; advanced controls use native disclosure.
  document.querySelectorAll('[data-search]').forEach(form=>{
    form.classList.add('filter-panel');if(form.classList.contains('history-toolbar')||(document.querySelector('main').dataset.screen==='reports'&&form.elements.type))form.classList.add('filter-panel--complex');const common=new Set(['q','category_id','stock_status','is_active','type','date_from','date_to','entity_type']);
    const advanced=document.createElement('details'),summary=document.createElement('summary'),fields=document.createElement('div');
    advanced.className='advanced-filters';summary.textContent='More filters and sorting';fields.className='advanced-fields';advanced.append(summary,fields);
    [...form.querySelectorAll(':scope > .field')].forEach(field=>{const control=field.querySelector('input,select');if(control&&!common.has(control.name))fields.append(field);});
    if(fields.children.length)form.append(advanced);const actions=document.createElement('div');actions.className='filter-actions';[...form.children].filter(el=>el.matches('button,[data-refresh],.filter-actions')||(!el.matches('.field,details')&&el.querySelector('button'))).forEach(el=>actions.append(el));if(!form.querySelector('[data-reset-filters]')&&!actions.querySelector('[data-reset-filters]')){const reset=document.createElement('button');reset.type='button';reset.className='bs-button bs-button--secondary';reset.dataset.resetFilters='';reset.textContent='Reset filters';actions.append(reset);}if(actions.children.length)form.insertBefore(actions,advanced.parentNode?advanced:null);
    const parameters=new URLSearchParams(location.search);
    if([...fields.querySelectorAll('input,select')].some(control=>parameters.has(control.name)))advanced.open=true;
  });
  document.querySelectorAll('[data-password-toggle]').forEach(button=>{
    const input=document.getElementById(button.getAttribute('aria-controls'));
    const hide=()=>{input.type='password';button.textContent='Show';button.setAttribute('aria-pressed','false');button.setAttribute('aria-label','Show '+button.dataset.passwordLabel);};
    button.addEventListener('click',()=>{const reveal=input.type==='password';input.type=reveal?'text':'password';button.textContent=reveal?'Hide':'Show';button.setAttribute('aria-pressed',String(reveal));button.setAttribute('aria-label',(reveal?'Hide ':'Show ')+button.dataset.passwordLabel);});
    input.form?.addEventListener('reset',hide);
  });
  window.BizStockUI = Object.freeze({ announce, openDialog, showMessage });
})();

