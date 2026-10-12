(() => {
  'use strict';
  const main = document.querySelector('[data-lookups]'); if (!main) return;
  const resource = main.dataset.lookups, admin = main.dataset.canManage === 'true';
  const idField = resource === 'categories' ? 'category_id' : 'supplier_id';
  const nameField = resource === 'categories' ? 'category_name' : 'supplier_name';
  const api = new window.BizStockApi.ApiClient(), base = document.querySelector('meta[name="app-base-path"]').content;
  const search = main.querySelector('[data-search]'), editor = main.querySelector('[data-editor]'), form = main.querySelector('[data-edit-form]');
  const message = main.querySelector('[data-message]'), errors = form?.querySelector('[data-form-errors]');
  const dialog = document.getElementById('lookup-delete');
  let page = 1, pages = 0, editId = null, deleting = null, pending = false, generation = 0, controller;
  const show = (target, text) => BizStockUI.showMessage(target,text);
  const errorText = error => `${error.message}${Object.values(error.details || {}).flat().length ? ` ${Object.values(error.details).flat().join(' ')}` : ''}${error.requestId ? ` Reference: ${error.requestId}` : ''}`;
  const state = value => ['loading','empty','error','results','pagination'].forEach(key => { main.querySelector(`[data-${key}]`).hidden = key !== value; });
  window.addEventListener('bizstock:session-expired', () => location.assign(`${base}/login`));
  const handleRestricted = error => { if (error.code === 'PASSWORD_CHANGE_REQUIRED') location.assign(`${base}/account`); };
  function edit(row = null) {
    if (pending) return; editId = row?.[idField] || null; form.reset(); errors.hidden = true;
    form.elements[nameField].value = row?.[nameField] || '';
    if (resource === 'suppliers') form.elements.contact_email.value = row?.contact_email || '';
    editor.hidden = false; editor.querySelector('h2').textContent = `${row ? 'Edit' : 'Add'} ${resource === 'categories' ? 'category' : 'supplier'}`; form.elements[nameField].focus();
  }
  function setBusy(value) {
    pending = value; main.querySelectorAll('button, input, select').forEach(el => { el.disabled = value; });
    form?.querySelector('[type="submit"]').setAttribute('aria-busy', String(value));
    dialog?.querySelectorAll('button').forEach(button => { button.disabled = value; });
  }
  const button = (label, action) => { const el = document.createElement('button'); el.type = 'button'; el.className = 'bs-button bs-button--ghost'; el.textContent = label; el.addEventListener('click', action); return el; };
  async function load() {
    if(message.getAttribute('role')==='alert')message.hidden=true;
    const current = ++generation; controller?.abort(); controller = new AbortController(); state('loading');
    try {
      const params = new URLSearchParams({ q: search.elements.q.value.trim(), sort: search.elements.sort.value, page: String(page), per_page: '25' });
      const result = await api.request(`/${resource}?${params}`, { signal: controller.signal });
      if (current !== generation) return;
      pages = result.meta.total_pages;
      if (page > Math.max(1, pages)) { page = Math.max(1, pages); return load(); }
      const tbody = main.querySelector('tbody'); tbody.replaceChildren();
      result.data.forEach(row => {
        const tr = document.createElement('tr'), cell = document.createElement('td'); cell.textContent = row[nameField]; tr.append(cell);
        if (resource === 'suppliers') { const email = document.createElement('td'); email.textContent = row.contact_email || '—'; tr.append(email); }
        if (admin) { const actions = document.createElement('td'), group = document.createElement('div'); group.className = 'lookup-actions'; group.append(button('Edit', () => edit(row)), button('Delete', event => {
          if (pending) return; deleting = row; dialog.querySelector('[data-dialog-summary]').textContent = row[nameField]; dialog.dataset.returnId = String(row[idField]);
          dialog._trigger = event.currentTarget; dialog.showModal(); dialog.querySelector('[data-close-dialog]').focus();
        })); actions.append(group); tr.append(actions); } tbody.append(tr);
      });
      state(result.data.length ? 'results' : 'empty');
      main.querySelector('[data-pagination]').hidden = !result.data.length;
      main.querySelector('[data-page-label]').textContent = `Page ${page} of ${pages} · ${result.meta.total} ${result.meta.total===1?'record':'records'}`;
      main.querySelector('[data-previous]').disabled = page <= 1; main.querySelector('[data-next]').disabled = page >= pages;
    } catch (error) { if (current !== generation || error.code === 'ABORTED') return; handleRestricted(error); state('error'); show(message, errorText(error)); }
  }
  search.querySelector('[data-reset-filters]').addEventListener('click',()=>{if(pending)return;search.reset();page=1;load();});search.addEventListener('submit', event => { event.preventDefault(); if (pending) return; page = 1; message.hidden = true; load(); });
  main.querySelector('[data-refresh] button').addEventListener('click', () => { if (!pending) { message.hidden = true; load(); } });
  main.querySelector('[data-previous]').addEventListener('click', () => { if (!pending && page > 1) { page--; load(); } });
  main.querySelector('[data-next]').addEventListener('click', () => { if (!pending && page < pages) { page++; load(); } });
  if (admin) {
    main.querySelector('[data-add] button').addEventListener('click', () => edit());
    main.querySelector('[data-cancel] button').addEventListener('click', () => { if (!pending) { editor.hidden = true; main.querySelector('[data-add] button').focus(); } });
    form.addEventListener('submit', async event => {
      event.preventDefault(); if (pending) return; errors.hidden = true;
      const body = Object.fromEntries(new FormData(form)); if ('contact_email' in body && !body.contact_email.trim()) body.contact_email = null;
      setBusy(true);
      try { await api.request(`/${resource}${editId ? `/${editId}` : ''}`, { method: editId ? 'PATCH' : 'POST', body }); editor.hidden = true; show(message, 'Record saved.'); await load(); }
      catch (error) { handleRestricted(error); show(errors, errorText(error)); }
      finally { setBusy(false); main.querySelector('[data-previous]').disabled = page <= 1; main.querySelector('[data-next]').disabled = page >= pages; }
    });
    dialog.addEventListener('cancel', event => { if (pending) event.preventDefault(); });
    dialog.addEventListener('close', () => { if (dialog._trigger?.isConnected) dialog._trigger.focus(); else main.querySelector('[data-add] button').focus(); });
    dialog.querySelector('.bs-button--danger').addEventListener('click', async () => {
      if (pending || !deleting) return; setBusy(true);
      try { await api.request(`/${resource}/${deleting[idField]}`, { method: 'DELETE' }); dialog.close(); show(message, 'Record deleted.'); await load(); }
      catch (error) { handleRestricted(error); dialog.close(); show(message, errorText(error)); }
      finally { setBusy(false); main.querySelector('[data-previous]').disabled = page <= 1; main.querySelector('[data-next]').disabled = page >= pages; }
    });
  }
  load();
})();
