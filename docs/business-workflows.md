# Live inventory workflows through Stage 12

Local acceptance uses the verified XAMPP MariaDB 10.4.32 engine on loopback. Production remains aimed at MySQL 8.4; its actual SQL/HTTP/concurrency verification and production HTTPS are **pending**. No deployment occurred. Stages 13–16 remain outside this task.

## Catalogue, opening stock and archive

`/products`, `/products/new` and `/products/{id}/edit` use database-backed `/api/v1/products` operations. Admin manages metadata; Staff reads full product details; Viewer receives only ID, SKU, name, category, quantity, threshold, status and active flag. Supplier filters and price sorting are forbidden to Viewer before SQL. Detail responses use the same positive allowlist. There is no browser role switch or production fixture source.

SKUs are trimmed and uppercase ASCII (`A-Z`, digits, dot, underscore, hyphen; 64 characters), unique including archived records, and immutable after any movement. Names need not be unique. Category/supplier are mandatory existing references. Price is a decimal string with exactly two places (`0.00`–`99999999.99`); threshold/opening quantity are bounded nonnegative integers. Optional location/description are bounded, nullable metadata. Ordinary PATCH rejects quantity/opening quantity. Metadata edits and archive require `expected_version`; movements also increment this version. Stale edits return 409 without overwrite.

Creation locks the current authorized actor, reserves a global actor/key `product.create` operation, inserts at zero, calls the single stock writer for optional opening IN, audits safe metadata and stores the original response, all in one transaction. No nested commits. Same key/normalized body replays the original result; changed body or operation kind returns 409. Opening movement captures the initial price. Audit failures roll back creation, opening balance/history and key reservation together.

Archive locks the same product row as stock writes, requires active/zero stock and preserves product/history/FKs. Both concurrent archive/IN orderings are tested: either archive wins and IN fails, or IN wins and archive fails. Archived products cannot receive movements or reversals. Metadata reassignment remains possible on archived records; they still block deletion of referenced category/supplier. There is no unarchive endpoint in this scope.

## Movement, reversal and history

`/movements` and `/history` are Admin/Staff pages; Viewer is denied server-side. Normal IN/OUT uses positive integer quantities and optional notes. Actor, timestamp and current price come from the server. Admin full reversal requires original ID and reason and derives opposite type, original product/full quantity/original captured price. One reversal per original; no reversal of a reversal, negative result or archived product. Make a fresh ordinary movement for a corrected entry afterward.

The shared UI reviews an operation before submission, blocks double confirmation and retains its key/body in memory after uncertain transport/server failure. Inputs stay frozen for retry. Navigation/closing warns about unsaved operations. A successful replay returns its original commit-time balance; the UI reloads current availability afterward. No automatic client write retry, browser credential storage or simulated success. After definite conflicts the user can review a fresh intent; current availability refreshes. Reloading/closing loses in-memory keys: resolve uncertain operations in the open form before leaving; inspect history rather than blindly repeating them elsewhere.

History is append-only through application APIs. No PATCH/DELETE handler or edit/delete control exists. Original/reversal links and captured price are visible in immutable detail. Product/type/category/actor/date filters, stable sorting and bounded pagination are shared with the history report. Archived products/inactive actors remain joined; category/name/actor labels reflect current metadata, while quantity/type/price/time/correction links are historical. Actor IDs are used as filters until Stage 13's user management exists. See [stock integrity](stock-integrity.md) for locking, three bounded transient retries and reconciliation.

## Dashboard freshness and scope

`/dashboard` uses `/api/v1/dashboard`: one read-only repeatable-read consistent snapshot for active count, wide decimal total units, inclusive low-stock count (`quantity <= threshold`), up to 100 alert rows and latest 10 movements for Admin/Staff. Viewer has no recent movement field or hidden pricing/supplier fields. Alert rows use availability-only projection for all roles. Counts cover all active products; links lead to matching full catalogue filters. `stock_status=low_stock` means positive low stock; `low_stock=true` includes zero and matches the alert count. Zero and low stock use distinct text/badges.

Every visit/BFCache restoration, manual refresh and in-page stock-change event refreshes data; no polling. Mutations navigate to or reload authoritative data. Snapshot times are UTC in API and Asia/Manila in UI. Errors hide old results. Empty active inventory is an explicit state. Counts/rows remain consistent across a concurrently committed movement.

## Reports, CSV and print

`/reports/inventory` and `/reports/transactions`, and their `/api/v1` counterparts, are Admin/Staff only. Viewer is denied before query parsing or export. ReportService reuses the same ProductRepository/HistoryRepository filters for JSON and CSV inside a read-only consistent snapshot. Inventory is **current active stock**, not historical inventory for a date; non-active scope is rejected. Product/category/supplier/search/status filters apply. Historical movements include archived products/inactive actors and product/category/type/actor/date filters.

Inclusive selected dates are Asia/Manila calendar dates, converted to UTC `>= start` and `< next-day end`; both bounds must be ordered and span at most 366 inclusive days. A single date bound is allowed. Transaction reference value is the wide exact decimal sum of **quantity × captured unit_price across all matching rows**, including originals and reversals. It is gross reference movement value, not net value, profit, revenue or accounting valuation. Product price changes never alter captured historical prices. Quantities are integers and API money/aggregate values are strings.

JSON/screen pages default to 25, maximum 100; page maximum 10,000. CSV exports **all matching pages** in the same stable order, at most **10,000 rows and 16 MiB**. Over-limit returns JSON 422 `EXPORT_TOO_LARGE`, never a truncated download. An export takes a fresh snapshot, so concurrent changes can differ from a previously loaded screen; `X-Snapshot-At` identifies the export time. Apply changed filters before export. Print shows the current paginated page, applied filters, scope, time and reference summary; navigation/forms/actions are hidden.

CSV is UTF-8, comma-delimited with CRLF records, doubled quotes and empty nullable fields. Formula-leading text (including leading whitespace, `=`, `+`, `-`, `@`, tab/CR/LF) is prefixed with an apostrophe. This deliberate neutralization is the only text-value difference from JSON. Exact decimal/numeric columns remain numeric text. No hashes, passwords, operation keys or session data are exported. Headers include attachment with fixed safe filename, no-store, nosniff and request ID.

Inventory columns: product_id, sku, product_name, category_id, category_name, supplier_id, supplier_name, location, description, quantity, minimum_threshold, stock_status, price, is_active, version, created_at, updated_at.

Transaction columns: transaction_id, product_id, sku, product_name, user_id, actor_name, type, quantity, unit_price, transaction_date, correction_of_transaction_id, reversed_by_transaction_id, notes. UTC timestamps preserve exact API values; UI displays Manila time. There is no misleading financial footer or extra summary row in CSV.

## Verification

Run `composer test:business` with the selected PHP runtime. Tests create/drop only their own random migrated databases, run separate PHP workers for real races and Chrome against those disposable databases, and reconcile afterward. Never point these tests at production. `composer test:schema`, `test:auth`, `test:lookups`, `test:database`, `check` and OpenAPI/client checks provide earlier-stage regression evidence. Exact commands/counts and pending targets are recorded in [progress](progress.md); release obligations remain in [gaps](gaps.md).

The [pre-Stage-13 review](stages-8-12-review.md) checks requirements directly against code and independently rerun tests. Live quantity headers now apply server sorting with announced direction; numeric cells share table alignment. Searching/clearing products or changing reversal selection invalidates delayed responses and obsolete originals before review. Existing data/credentials and the stable API remain preserved. MySQL 8.4 and production HTTPS verification remain pending.
