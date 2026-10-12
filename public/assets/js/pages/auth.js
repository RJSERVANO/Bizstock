(() => {
  'use strict';
  const form = document.querySelector('[data-auth-form]');
  if (!form) return;
  const api = new window.BizStockApi.ApiClient();
  const base = document.querySelector('meta[name="app-base-path"]').content;
  const errors = form.querySelector('[data-form-errors]');
  const status = form.querySelector('[data-form-status]');
  const logout = document.querySelector('[data-auth-logout] button');
  let pending = false;
  let blockedUntil = 0;
  let countdown;
  const timer = document.createElement('div');
  timer.hidden = true;
  timer.className = 'auth-countdown';
  const timerText = document.createElement('p');
  timerText.setAttribute('role', 'timer');
  timerText.setAttribute('aria-live', 'off');
  const meter = document.createElement('progress');
  meter.max = 300;
  meter.setAttribute('aria-label', 'Sign-in wait time remaining');
  timer.append(timerText, meter);
  status.after(timer);
  const updateCountdown = () => {
    const remaining = Math.max(0, Math.ceil((blockedUntil - Date.now()) / 1000));
    timerText.textContent = `Try again in ${Math.floor(remaining / 60)}:${String(remaining % 60).padStart(2, '0')}`;
    meter.value = remaining;
    form.querySelector('[type="submit"]').disabled = pending || remaining > 0;
    if (!remaining) {
      clearInterval(countdown); timer.hidden = true;
      status.textContent = 'You can try signing in again.'; status.hidden = false;
    }
  };
  const startCountdown = seconds => {
    blockedUntil = Date.now() + seconds * 1000;
    meter.max = Math.max(300, seconds);
    timer.hidden = false; clearInterval(countdown);
    updateCountdown(); countdown = setInterval(updateCountdown, 1000);
  };
  const showError = error => { const text=`${error.message}${error.requestId ? ` Reference: ${error.requestId}` : ''}`; if(window.BizStockUI) BizStockUI.showMessage(errors,text,true); else { errors.textContent=text;errors.hidden=false;errors.focus(); } };
  const busy = value => { pending = value; [...form.querySelectorAll('button'),...(logout?[logout]:[])].forEach(button => { button.disabled = value; button.setAttribute('aria-busy', String(value)); }); };
  const illustration=document.querySelector('[data-login-illustration]');if(illustration){const fallback=()=>{illustration.hidden=true;document.querySelector('[data-login-fallback]').hidden=false;};illustration.addEventListener('error',fallback);if(illustration.complete&&!illustration.naturalWidth)fallback();}
  const guidance={Admin:'Manage inventory, accounts and corrections.',Staff:'Record stock IN/OUT and read reports.',Viewer:'View product availability and low-stock alerts.'};
  const roleButtons=[...form.querySelectorAll('[data-role-hint]')];
  function selectRole(role,focus=false){if(!Object.hasOwn(guidance,role))return;roleButtons.forEach(button=>button.setAttribute('aria-pressed',String(button.dataset.roleHint===role)));document.getElementById('role-guidance').textContent=guidance[role]+' Your account determines access.';if(focus)form.elements.username.focus();}
  roleButtons.forEach(button=>button.addEventListener('click',()=>selectRole(button.dataset.roleHint,true)));
  const selected=roleButtons.find(button=>button.getAttribute('aria-pressed')==='true');if(selected)selectRole(selected.dataset.roleHint);
  // The public login stays put for generic wrong-password 401; account errors require reauthentication.
  window.addEventListener('bizstock:session-expired', () => { if (form.dataset.authForm === 'account') location.assign(`${base}/login`); });
  form.addEventListener('submit', async event => {
    event.preventDefault(); if (pending || Date.now() < blockedUntil) return;
    errors.hidden = true; status.hidden = true;
    const values = Object.fromEntries(new FormData(form));
    if (form.dataset.authForm === 'account') {
      if (values.new_password !== values.confirm_password) { showError(new Error('The new passwords do not match.')); return; }
      const bytes = new TextEncoder().encode(values.new_password).length;
      if (bytes < 12 || bytes > 72 || values.new_password.includes('\0')) { showError(new Error('Use a password of 12–72 UTF-8 bytes without NUL.')); return; }
      delete values.confirm_password;
    }
    busy(true);
    try {
      const login = form.dataset.authForm === 'login';
      const result = await api.request(login ? '/auth/login' : '/account/password', { method: login ? 'POST' : 'PATCH', body: values });
      api.clearSession(); form.reset();
      if (login) location.assign(`${base}/${result.data.must_change_password ? 'account' : 'dashboard'}`);
      else { if(document.querySelector('[data-forced-notice]')) { location.assign(`${base}/account`); return; } status.textContent = 'Password changed. Other sessions have been signed out.'; status.hidden = false; status.setAttribute('tabindex', '-1'); status.focus(); }
    } catch (error) { showError(error); if (error.code === 'RATE_LIMITED' && error.retryAfter > 0) startCountdown(error.retryAfter); }
    finally { busy(false); if (Date.now() < blockedUntil) updateCountdown(); }
  });
  logout?.addEventListener('click', async () => {
    if (pending) return; errors.hidden = true; busy(true);
    try { await api.request('/auth/logout', { method: 'POST', body: {} }); api.clearSession(); location.assign(`${base}/login`); }
    catch (error) { showError(error); }
    finally { busy(false); }
  });
})();
