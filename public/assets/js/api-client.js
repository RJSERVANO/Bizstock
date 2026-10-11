(() => {
  'use strict';
  // Independent forms share the server session, so a rotated token invalidates every client's cache.
  let sessionEpoch=0;
  class ApiError extends Error {
    constructor(code, message, status = 0, details = {}, requestId = null) {
      super(message); this.name = 'ApiError'; Object.assign(this, { code, status, details, requestId });
    }
  }
  class ApiClient {
    constructor(basePath = document.querySelector('meta[name="app-base-path"]')?.content || '') {
      if (basePath && !/^\/(?:[A-Za-z0-9_-]+\/)*[A-Za-z0-9_-]+$/.test(basePath)) throw new ApiError('CLIENT_ERROR', 'Invalid application base path.');
      this.root = `${basePath}/api/v1`; this.csrf = null; this.csrfPending = null; this.sessionGeneration = 0; this.sessionEpoch=sessionEpoch;
    }
    clearSession() { this.csrf = null; this.sessionGeneration++; this.sessionEpoch=++sessionEpoch; }
    async csrfToken() {
      if(this.sessionEpoch!==sessionEpoch){this.csrf=null;this.sessionGeneration++;this.sessionEpoch=sessionEpoch;}
      if (this.csrf) return this.csrf;
      const generation = this.sessionGeneration;
      if (!this.csrfPending) this.csrfPending = this.request('/auth/csrf').then(result => {
        if (generation !== this.sessionGeneration) throw new ApiError('SESSION_EXPIRED', 'Session changed; request a fresh token.');
        if (typeof result.data?.csrf_token !== 'string' || !result.data.csrf_token) throw new ApiError('INVALID_RESPONSE', 'Invalid CSRF response.');
        return (this.csrf = result.data.csrf_token);
      }).finally(() => { this.csrfPending = null; });
      return this.csrfPending;
    }
    async request(path, { method = 'GET', body, idempotencyKey, signal, format = 'json' } = {}) {
      method = method.toUpperCase();
      if (!['GET', 'HEAD', 'POST', 'PATCH', 'DELETE'].includes(method) || typeof path !== 'string' || !path.startsWith('/') || path.startsWith('//') || /[\\#\x00-\x20]/.test(path)) throw new ApiError('CLIENT_ERROR', 'Invalid API request.');
      const url = new URL(this.root + path, location.origin);
      if (url.origin !== location.origin || !url.pathname.startsWith(this.root + '/')) throw new ApiError('CLIENT_ERROR', 'Invalid API request path.');
      if (!['json', 'csv'].includes(format) || (format === 'csv' && method !== 'GET')) throw new ApiError('CLIENT_ERROR', 'Invalid response format.');
      if (['GET', 'HEAD'].includes(method) && body !== undefined) throw new ApiError('CLIENT_ERROR', 'Read requests cannot carry a body.');
      if (idempotencyKey !== undefined && !/^[A-Za-z0-9._:-]{1,128}$/.test(idempotencyKey)) throw new ApiError('CLIENT_ERROR', 'Invalid operation key.');
      const headers = { Accept: format === 'csv' ? 'text/csv' : 'application/json' };
      if (idempotencyKey !== undefined) headers['Idempotency-Key'] = idempotencyKey;
      if (!['GET', 'HEAD'].includes(method)) headers['X-CSRF-Token'] = await this.csrfToken();
      let encoded;
      if (body !== undefined) {
        if(typeof FormData !== 'undefined' && body instanceof FormData) encoded=body;
        else {
        headers['Content-Type'] = 'application/json';
        try { encoded = JSON.stringify(body); } catch { throw new ApiError('CLIENT_ERROR', 'Request body cannot be serialized.'); }
        }
      }
      let response;
      try { response = await fetch(url, { method, headers, body: encoded, credentials: 'same-origin', cache: 'no-store', redirect: 'error', signal }); }
      catch (error) { throw new ApiError(error.name === 'AbortError' ? 'ABORTED' : 'NETWORK_ERROR', error.name === 'AbortError' ? 'Request cancelled.' : 'Unable to reach the server.'); }
      const requestId = response.headers.get('X-Request-ID');
      const contentType = response.headers.get('Content-Type') || '';
      if (response.status === 401) { this.clearSession(); window.dispatchEvent(new CustomEvent('bizstock:session-expired', { detail: { requestId } })); }
      if (method === 'HEAD' && !response.ok) throw new ApiError('HTTP_ERROR', 'Request failed.', response.status, {}, requestId);
      if (response.ok && (method === 'HEAD' || response.status === 204)) return { data: null, meta: {}, requestId };
      if (response.ok && format === 'csv' && /^text\/csv\b/i.test(contentType)) return { data: await response.blob(), meta: {}, requestId, disposition: response.headers.get('Content-Disposition') };
      if (!/^application\/json\b/i.test(contentType)) throw new ApiError('INVALID_RESPONSE', 'Unexpected server response.', response.status, {}, requestId);
      let payload;
      try { payload = await response.json(); } catch { throw new ApiError('INVALID_RESPONSE', 'Invalid JSON response.', response.status, {}, requestId); }
      if (!response.ok) {
        if (!payload?.error || typeof payload.error.code !== 'string' || typeof payload.error.message !== 'string') throw new ApiError('INVALID_RESPONSE', 'Invalid error response.', response.status, {}, requestId);
        if (payload.error.code === 'CSRF_INVALID') this.clearSession();
        const error = new ApiError(payload.error.code, payload.error.message, response.status, payload.error.details || {}, payload.request_id || requestId);
        const retry = response.headers.get('Retry-After');
        if (/^\d+$/.test(retry || '') && Number.isSafeInteger(Number(retry))) error.retryAfter = Number(retry);
        throw error;
      }
      if (!payload || !Object.hasOwn(payload, 'data') || !payload.meta || typeof payload.meta !== 'object' || Array.isArray(payload.meta)) throw new ApiError('INVALID_RESPONSE', 'Invalid success response.', response.status, {}, requestId);
      return { ...payload, requestId };
    }
  }
  window.BizStockApi = { ApiClient, ApiError };
})();
