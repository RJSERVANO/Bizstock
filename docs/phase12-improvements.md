# Authorized Phases 1–2: reliability and discovery

Scope: the owner's implementation request dated 2026-11-14. This is a follow-up to the function audit, not blueprint Stages 1–2. No migration, account reset, seed, installed-data mutation, stock-writing change or deployment is included. Existing uncommitted startup entries in progress/gaps were retained.

## Implemented

- Reports retry the selected inventory/transaction report in place, preserving form filters; fallback links target the registered report route. CSV columns/limits/safety and current-page printing are unchanged.
- Dashboard describes business-wide authorized recent movements. Initial load failure shows no invented results; failed refresh retains marked stale results and the previous snapshot. Inclusive threshold alerts remain quantity <= threshold, exclusive low-stock status remains 0 < quantity <= threshold, and out of stock remains zero. Calculations did not change.
- Product list state uses approved URL query fields and existing server validators. The shared list restores page/page-size, records successful applied filters/sort/page in the URL and recovers to the last available page. Editing uses a server-validated exact local Products destination; external, malformed, duplicate, unknown and invalid parameters fall back safely. Save, Back, Cancel, native browser Back and reload preserve approved state.
- Movement confirmation includes current availability, requested IN/OUT and expected balance. The preview explicitly does not reserve stock. Payloads, server rechecks, operation keys, concurrency and immutable reversals remain unchanged.
- Reference-price hints and product details explain current versus captured prices without accounting claims. Users explains existing reset, role, deactivation and session effects. Own-profile name behavior remains distinct.
- Authenticated role-specific Help at `/help` adapts the existing user guide. Viewer help contains availability/account instructions; business workflows are rendered only for permitted roles. No future workflow is advertised as implemented.
- Composed product details reuse the existing product/image APIs. Admin/Staff may load five recent movements and follow permitted actions. Viewer does not request History and receives the existing availability-only projection.
- Categories/Suppliers opt into active/archived product counts via `include_products=true`. Counts and links use current assignments with the same explicit destination scope; no receipt-supplier inference is made. Default API projections remain unchanged. Lookup editors reuse shared dirty-form protection for switching, Cancel and navigation.
- Users adds role filtering alongside search/status/sorting/pagination and displays existing creation time in Manila. No last-login/session storage was added.
- Audit adds exact bounded action filtering and a nullable actor display name on each authorized page of results. The client no longer enumerates all users on load. Changed-field tables escape values, exclude sensitive keys and retain legacy JSON rendering. The comparison scroll region is named and keyboard-focusable.

## Principal implementation files

- [URL validation](../src/Support/ProductListState.php), [page controller](../src/Http/Controllers/BusinessPageController.php), [routes](../src/Http/Application.php).
- [Shared list and detail rendering](../public/assets/js/business.js), [shared components](../public/assets/js/components.js), and affected scripts in `public/assets/js/pages/`.
- [Lookup counts](../src/Repositories/LookupRepository.php), [lookup projection](../src/Services/LookupService.php), [Users query](../src/Repositories/UserRepository.php), [Audit query](../src/Repositories/AuditLogRepository.php), and their existing controllers.
- Existing live templates/shared dialog, [Help](../templates/live/help.php), and existing CSS tokens/components.
- [Contract generator](../bin/build-openapi.cjs) and [OpenAPI](openapi.yaml): additive lookup counts, user role/action filters and paginated actor names; legacy audit JSON shapes documented. No CSV contract change.
- [Focused integration fixture](../tests/integration/phase12.php) and [Chrome checks](../tests/browser/phase12.cjs), available through `composer test:phase12`.

## Verification executed for this change

Selected runtime: PHP 8.5.11. Real database target: explicitly local-only MariaDB 10.4.32. All write checks used disposable fixture databases/accounts and isolated files; the installed database was used only for read-only preservation/reconciliation. No installation migration was run.

| Check | Actual result |
|---|---|
| Composer `validate --strict` | PASS |
| Composer `check` | PASS: 139 PHP syntax, 139 foundation, 14 engine-policy, 64 API assertions |
| Focused Phase 1–2 suite | PASS: 38 DB/HTTP/harness and 68 Chrome/accessibility assertions; eight clean axe scans |
| Products (`stage9.php`) | PASS: 43 DB and 21 Chrome assertions |
| Movements (`stage10.php`) | PASS: four DB and 28 Chrome assertions |
| Dashboard (`stage11.php`) | PASS: 13 DB and 16 Chrome assertions |
| Reports (`stage12.php`) | PASS: 43 DB/export and 30 Chrome assertions |
| Users/Audit (`stage13.php`) | PASS: 37 DB/API and 21 Chrome assertions; seven axe scans |
| Lookups (`stage7.php`) | PASS: 84 DB/API and 36 Chrome assertions; six axe scans |
| Permissions (`permissions.php`) | PASS: 145 real HTTP/MariaDB and 81 Chrome assertions; nine axe scans |
| Authentication (`stage6.php`) | PASS: 336 DB/security and 32 Chrome assertions; seven axe scans |
| Shared API client, OpenAPI, release-document checks | PASS; 39 implemented operations/671 resolved references |

The focused suite covers filtered report failure/retry, initial dashboard failure and stale retained results, threshold/status parity, filtered later-page save/cancel/Back/native Back/reload, invalid returns/query state, empty-page recovery after archive, IN/OUT previews, insufficient stock and a competing OUT between preview/confirmation, lookup Keep editing/discard/navigation, combined Users filters/page, count/destination parity, audit action validation/escaping/legacy values, no eager user-directory request, and Viewer no restricted detail fetch. Existing regressions retain lost-response/idempotency, role, CSV, reversal and session checks.

Changed lists/Help and product/audit detail dialogs were captured at 360/768/1440. There are 24 private fixture screenshots under `.tools/phase12-*.png`; mobile Users, product details and audit comparison were visually inspected. Automated overflow and axe checks are evidence of the executed checks, not comprehensive manual screen-reader acceptance.

Intermediate failures were resolved: test assertions expecting an obsolete hidden mobile SKU summary now check the visible name-cell stock summary; lookup Cancel tests now require the authorized discard confirmation; dashboard tests expect marked stale results; exact navigation expectations include Help; inventory URL expectations allow persisted query state. The expanded modal axe check exposed a real unfocusable comparison scroll region, fixed before the final pass. Early harness selector/context errors were corrected and rerun. No failed run is counted as a pass.

## Preservation evidence

A private read-only pre-change snapshot hashes all ten installed application tables (including accounts/audit/migration records), `.env`, the private initial-credential file, migration SQL and uploaded files. The comparison after fixture tests matches all ten tables and ten protected files, and the independent stock reconciliation remains three products with zero discrepancies. Credentials/hashes/row contents were not printed. Normal test logs and test-only screenshots are outside that protected set. The previously authorized password reset predates this baseline; no account/password operation occurred in this implementation.

## Separate assessments — not implemented

**Optimistic concurrency.** Products already use `expected_version`; Users uses the singleton security lock/current actor lock and invalidates sessions, but does not compare an editor's target baseline. Lookup edits also lack a stale-editor token. Locks serialize writes without detecting that the second editor started with old values. Recommended future design: additive dedicated row-version columns on Users/categories/suppliers, incremented by every relevant writer, with an optional expected-version or conditional-request contract introduced compatibly. Return 409 `STALE_EDIT` with reload guidance. Users `updated_at` can support a transitional opaque baseline token but is not a dedicated monotonic revision; do not repurpose authentication `session_version`. Categories currently lack an update timestamp. No migration or conflict behavior was introduced here.

**Name-only session invalidation.** Own display-name updates already keep sessions. Admin Users edits currently revoke all prior target sessions even if only the name changes; this behavior is preserved and explained. A later narrowly scoped policy could keep sessions for name-only edits while retaining revocation for password/role/status/security changes. Decide username-change policy separately and test self-edit, concurrent reset/deactivation and current-session refresh before changing it.

**History actor lookup.** Staff currently gets name suggestions from the loaded history page and may enter another actor ID. Do not broaden `/users` access. A future bounded read endpoint can return only actor ID/current display name for users with recorded movements, permit Admin/Staff, include inactive historical actors, apply literal bounded search/pagination and deny Viewer. It can query existing users/transactions without schema changes; deciding whether to snapshot historical display names is a separate schema/business decision. This endpoint was not added.

## Limits and rollback

MySQL 8.4, manual assistive-technology/native browser-menu zoom and participant testing were not executed. Actual MySQL acceptance remains separate from MariaDB. No hosting work or later-phase workflow was started. All existing release gaps retain their unverified obligations.

Rollback the Phase 1–2 application/contract files as a compatible set if needed. There is no schema/data rollback: retain every stock movement, audit event, account edit and upload created during later normal use. Old clients can continue using default lookup projections; newly introduced filters/Help should be disabled together with their UI. Do not restore the pre-change database over records created afterward.
