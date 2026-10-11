/* Development fixtures only; later screens replace this local dataset with the shared API client. */
(() => {
  'use strict';
  const root = document.querySelector('[data-inventory]');
  if (!root || !root.querySelector('[data-product-row]')) return;
  const rows = [...root.querySelectorAll('[data-product-row]')];
  const body = root.querySelector('tbody');
  const search = root.querySelector('#product-search'), category = root.querySelector('#category-filter'), status = root.querySelector('#status-filter');
  const prev = root.querySelector('[data-page-prev]'), next = root.querySelector('[data-page-next]');
  const pageSize = Number(root.dataset.pageSize);
  let page = 1, sortKey = '', direction = 1;
  const initialStatus = new URLSearchParams(window.location.search).get('status');
  if (['low', 'out', 'in'].includes(initialStatus)) status.value = initialStatus;
  function render() {
    const term = search.value.trim().toLocaleLowerCase();
    const matching = rows.filter(row => (row.dataset.name + ' ' + row.dataset.sku).toLocaleLowerCase().includes(term)
      && (!category.value || row.dataset.category === category.value)
      && (!status.value || row.dataset.status === status.value || (status.value === 'low' && row.dataset.status === 'out')));
    if (sortKey) matching.sort((a, b) => direction * (sortKey === 'quantity' ? Number(a.dataset.quantity) - Number(b.dataset.quantity) : a.dataset.name.localeCompare(b.dataset.name)));
    const pages = Math.max(1, Math.ceil(matching.length / pageSize)); page = Math.min(page, pages);
    rows.forEach(row => { row.hidden = true; row.querySelector('details')?.removeAttribute('open'); });
    matching.forEach((row, index) => { body.append(row); row.hidden = index < (page - 1) * pageSize || index >= page * pageSize; });
    root.querySelector('[data-no-results]').hidden = matching.length > 0;
    root.querySelector('.table-scroll').hidden = matching.length === 0;
    prev.disabled = page === 1; next.disabled = page === pages;
    root.querySelector('[data-page-label]').textContent = `Page ${page} of ${pages}`;
    root.querySelector('[data-table-count]').textContent = matching.length ? `Showing ${(page - 1) * pageSize + 1}–${Math.min(page * pageSize, matching.length)} of ${matching.length} products` : '0 matching products';
  }
  [search, category, status].forEach(input => input.addEventListener(input === search ? 'input' : 'change', () => { page = 1; render(); }));
  prev.addEventListener('click', () => { page--; render(); }); next.addEventListener('click', () => { page++; render(); });
  root.querySelectorAll('[data-sort]').forEach(button => button.addEventListener('click', () => {
    direction = sortKey === button.dataset.sort ? -direction : 1; sortKey = button.dataset.sort; page = 1;
    root.querySelectorAll('[data-sort]').forEach(other => { other.closest('th').setAttribute('aria-sort', other === button ? (direction === 1 ? 'ascending' : 'descending') : 'none'); other.querySelector('[data-sort-symbol]').textContent = other === button ? (direction === 1 ? '↑' : '↓') : '↕'; });
    render(); window.BizStockUI.announce(`Sorted by ${sortKey}, ${direction === 1 ? 'ascending' : 'descending'}.`);
  }));
  root.querySelector('[data-reset-filters]').addEventListener('click', () => { search.value = ''; category.value = ''; status.value = ''; page = 1; render(); search.focus(); });
  render();
})();
