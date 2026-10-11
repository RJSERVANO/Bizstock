# BizStock requirements

## Public homepage and role access amendment (2026-10-12)

Public / remains readable for anonymous and authenticated visitors and contains only illustrative sample data/artwork. Centrally validated APP_BUSINESS_NAME and optional verified APP_CONTACT_EMAIL determine business/contact text, with administrator guidance when empty. No invented evidence, signup or fake contact submission. One /login uses each person's own credentials; role cards are hints only. Normal login goes to /dashboard; forced password change precedes inventory access. Shared alignment and distinct outline icons preserve existing locally remembered rail, accessible tooltips, drawer focus and private avatars. APP_BASE_PATH applies to all links/assets. This supplements REQ-02/03/26/27/28/31/33 without changing API permissions or stock semantics. [Actual local checks and remaining targets](public-homepage.md).

Stage 1 specification, 2026-10-11. Source: [complete blueprint](BizStock-Codex-Build-Blueprint.md), including its master instruction. Acceptance criteria below are future verification obligations, not claims of implemented behavior. Stage numbers identify implementation owners; Stage 14 verifies the combined release.

## Scope and permissions

One business, one database; responsive authenticated inventory website. Backend routes and response serializers enforce this matrix independently of navigation.

| Capability | Admin | Staff | Viewer |
|---|---|---|---|
| Product availability/name/category/current quantity | Read | Read | Read |
| Dashboard inventory counts and low-stock list | Read | Read | Read |
| Prices and supplier details | Read/manage | Read | Hidden |
| Products/categories/suppliers | Manage | Read | Availability fields only; no supplier access |
| Record IN/OUT | Yes | Yes | No |
| Reverse movement / audit log | Yes | No | No |
| All business history and inventory/transaction reports | Yes | Yes | No |
| Users and roles | Manage | No | No |
| Own profile photo (newly authorized) | Manage own | Manage own | Manage own |
| Product image (newly authorized) | Read/manage | Read | Read |

Viewer product responses use a positive field allowlist: product ID, SKU, name, category ID/name, quantity, threshold, stock status, active state and an optional image-presence flag. Description, location, price, supplier identifiers/details, actors and movement history are excluded. Category reads expose only category ID/name. No Viewer reports or exports. Password hashes are never returned to any role.

## Requirements and acceptance mapping

| ID | Requirement and measurable acceptance criterion | Owner stages |
|---|---|---|
| REQ-01 | Modular monolith uses PHP/PDO/MySQL/InnoDB, HTML/CSS/vanilla JS/Bootstrap and Composer; clean setup renders with only public/ exposed; actual supported runtime versions and MySQL versus MariaDB are recorded. | 2,15 |
| REQ-02 | Same-origin cookie login/logout/me/CSRF and account password change work; hash/verify passwords, regenerate session ID, enforce inactivity/absolute expiry, persistent rate limits, Secure/HttpOnly/SameSite production cookies, CSRF even on login/logout, output escaping and authenticated no-cache responses. Wrong password, expiry, invalid CSRF and inactive accounts fail safely. No public signup or browser token storage. | 6 |
| REQ-03 | Every matrix cell passes direct API authorization and response-field checks; current active status, role and session_version checked on each protected request; Staff can read all business history, Viewer cannot retrieve prices/suppliers/history even through filters or errors. | 5,6,7,9,11,12,13 |
| REQ-04 | Admin creates/edits/deactivates users with unique usernames and strong passwords; two concurrent changes cannot remove the last active Admin; historic actor references survive. | 4,13 |
| REQ-05 | Admin reset and CLI emergency-owner recovery invalidate prior sessions, force next-login password change and audit safely. Forced-reset sessions permit password change and logout only; initial password delivery is manual, no unauthorized email. | 6,13 |
| REQ-06 | Admin manages categories/suppliers; category names unique, bounded names, nullable valid supplier email. Referenced deletion fails until reassignment, including archived references; no cascading history deletion. | 4,7 |
| REQ-07 | Products have required category/primary supplier, unique normalized SKU, name, optional description/location, nonnegative decimal price/threshold, quantity and active/version fields. Name may repeat; SKU cannot change after any movement. Invalid values/FKs/duplicates fail. | 4,9 |
| REQ-08 | Product creation starts at zero; opening IN and product/audit/idempotency records commit together or all roll back. Replaying creation returns the same result without another product/opening movement. Ordinary PATCH rejects quantity. | 8,9 |
| REQ-09 | Metadata update requires expected version; competing stale edits return 409; successful changes increment version and insert atomic safe audit events. | 9 |
| REQ-10 | Archive is soft deletion at zero only; archive and movement share product locks so either serialization order remains safe. Archived products reject all new movements; historical rows remain readable. | 8,9 |
| REQ-11 | One service atomically records positive integer IN/OUT and updates stock; rejects insufficient stock, inactive actor/product, invalid type and overflow; actor/price/time are server-derived. Two connections cannot oversell; failure rolls back; no second quantity trigger. | 8 |
| REQ-12 | Actor-scoped operation keys, canonical payload fingerprint and durable replay result protect movements and product creation; same key/different payload returns 409; concurrent identical retries change stock once; deadlock retries bounded. | 4,8,9,10 |
| REQ-13 | Admin-only full reversal creates immutable opposite-type/same-product/same-quantity movement at original unit_price with required reason and unique original link. Repeat reversal, insufficient balance and archived product fail; both rows persist. Fresh corrected movement is separate. | 4,8,10 |
| REQ-14 | Append-only movements have no edit/delete route/UI. History supports bounded product/type/actor/date filters, immutable detail, price snapshot and correction links, retaining inactive actors/archived products. | 8,10 |
| REQ-15 | Append-only audit events record actor/action/entity, safe before/after, UTC timestamp and request ID for metadata, archives, account/role changes and resets. Audit failure rolls back domain write; secrets never stored; Admin-only paginated viewing. | 4,7,9,13 |
| REQ-16 | Read-only reconciliation compares every cached product quantity to SUM(IN)-SUM(OUT), including zero-movement/archived products; drift reports discrepancy and nonzero exit; no automatic repair. Seed balances reconcile. | 4,8 |
| REQ-17 | Product list/detail persist across reload, paginate/search/sort/filter category/status/active state; supplier filter available only to permitted roles. UI uses grouped forms, relational selectors, separate quantity/threshold, unsaved-change warning and clear errors. | 9 |
| REQ-18 | Single-product movement screen shows searchable selector, IN/OUT, available quantity, note/reference, confirmation and Admin correction mode. Pending buttons disable; uncertain network retry retains key; authoritative stock refreshes on success/conflict. Double click/reconnect/stale stock checks pass. | 10 |
| REQ-19 | Dashboard active product count, active total units and active low-stock list/count agree with inventory; low means quantity <= threshold including zero, archived excluded. Recent movements role-restricted; refreshed after mutation/revisit/manual refresh with timestamp. | 11 |
| REQ-20 | Admin/Staff inventory report describes current active stock and snapshot time; history includes archived products/inactive users. JSON/screen/CSV share filters; transaction reference values use captured price, never revenue/profit/accounting valuation. Print view works. | 12 |
| REQ-21 | PHP currency, integer quantities and decimal JSON strings consistent; DB timestamps UTC, display/report dates Asia/Manila. Inclusive date selection becomes UTC half-open range; boundary tests pass; decimal arithmetic avoids floats. | 2,5,8,12 |
| REQ-22 | CSV quotes/escapes correctly, neutralizes formula injection in text, respects permission/filter parity and bounds or streams large exports with documented limits. Viewer export denied. | 12 |
| REQ-23 | Versioned /api/v1 routes follow blueprint section 6, success {data,meta}, structured errors with request_id, documented status codes, pagination/sort/filter allowlists, decimal/timestamp schemas and cookie/CSRF/idempotency semantics. CSV has download headers; OpenAPI matches implemented endpoints. | 5 and each feature stage |
| REQ-24 | Prepared SQL, allowlisted fields/order, bounded payloads/pages/numerics, validation and generic internal errors prevent SQL/API abuse; malformed/direct unauthorized requests tested. Minimal health exposes no secrets; readiness is restricted. | 2,5,6,8,15 |
| REQ-25 | InnoDB/utf8mb4, named checks/FKs/uniques, RESTRICT references and access-pattern indexes enforce schema; constraints verified on actual engine. Tracked additive migrations rerun safely and upgrade disposable existing data; applied migrations unchanged; secure CLI Admin bootstrap and development-only consistent seeds. | 4 |
| REQ-26 | Shared tokens/components/client follow blueprint section 4 screen compositions and palette/type/spacing/status/motion values; neutral sidebar, compact dashboard, inventory/form/history/reports/users/lookups/audit/login/account. No dead controls, demo role switch, invented metrics or production fixtures/localStorage persistence. | 3 and feature stages |
| REQ-27 | WCAG 2.2 AA target: keyboard, visible/unobscured focus, dialog/drawer focus, contrast, labels, announced errors/status, targets and reduced motion. Screens at 360/768/1440 and intermediate widths retain actions without whole-page clipping; long tables/names tested. | 3,14 |
| REQ-28 | Every screen covers loading/empty/error/populated/permission-limited states; shared button variants cover normal/hover/focus/disabled/busy. Stage 3 screenshots of shell/login/dashboard/inventory/form reviewed and material defects fixed before expansion; final drift review documented. | 3,14 |
| REQ-29 | Locked dependencies, env documentation and real-database CI support syntax/unit/integration/browser checks. End-to-end Admin setup/opening stock → Staff IN/OUT → Viewer availability → alerts/history/export passes, including concurrency/rollback/retry/price/fresh-install checks. Requirement IDs map to actual evidence. | 2,14,15 |
| REQ-30 | User-testing scripts cover owner/staff/viewer tasks with time/errors/completion and improvement log; actual sessions reported honestly, pending feedback never fabricated. | 14 |
| REQ-31 | Runbook/user guide document selected host or unresolved choice, HTTPS/public document root, env/privileges/session storage/writable paths/protected logs, backups/retention/restore/monitoring/dependency updates and migration-safe rollback. Disposable clean install and backup restore rehearsed. | 15 |
| REQ-32 | Only authorized selected target deployed; release/checks/runtime/backup verified first, secure secrets/owner bootstrap, no demo data. Real role/product/stock/report/denied endpoint/persistence/health/cookie/internal-file checks recorded with URL/commit/migrations and cleanup policy; critical failure uses rollback. | 16 |
| REQ-33 | Audit gap register after every stage and release; all required blockers close with regression evidence. Improvements preserve data and /api/v1 or document version change; migrations/schema/services/validation/contracts/UI/tests updated together. Only requested stage executed. | 1–16 |

## MVP exclusions

POS/payment integration; purchase orders; manufacturing/bill of materials; camera capture; automated reordering; barcode scanning; multiple warehouses/businesses; forecasting; email alerts. Also excluded: batch movement cart, recipient directory, third-party API tokens and accounting/revenue/historical-valuation features. Optional compact inventory cards, real-query charts, polling, self-hosted Inter and development Docker Compose do not replace required behavior or block MVP.

## Newly authorized scope
Own profile photo: all authenticated unrestricted roles; product image: authenticated reads, Admin mutations only; no arbitrary-user profile reads. Actual image validation, private re-encoding/storage/controlled serving, CSRF, safe replacement/removal, initials/placeholders and role-consistent table/cards are required. Accessible locally remembered desktop rail and existing mobile drawer are required. This supersedes only photo upload in the earlier exclusion; camera capture remains excluded. [Acceptance evidence](private-images.md).
