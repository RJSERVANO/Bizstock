# Audit Phases 3–5: implementation and operator upgrade

Implemented and verified on disposable local MariaDB fixtures on 2026-11-14. **Current installed operation: 005-007 applied with verified backup/restore and preservation; application running after the owner confirmed newer sign-in activity and final checks passed.** See the [dated execution record](operations/2026-11-14-installed-phase345-upgrade.md) for the temporary stop, accepted authentication activity and successful continuation. The implementation checkpoint and procedure below retain historical context; statements that installation remains 004 describe that earlier checkpoint. No production target was used, deployed or pushed.

## Delivered scope and compatibility

### Phase 3

- Users, Categories and Suppliers have dedicated `edit_version` revisions. Updated forms send the loaded `expected_edit_version`; stale writes return 409 `STALE_EDIT`, retain ordinary entered values and offer explicit reload/review. Password fields still clear according to the existing security policy. The security singleton/current actor locks, last-Admin protection and atomic audits remain. Own profile/password/photo writers advance the relevant user edit revision too. Login telemetry does not invalidate a metadata editor.
- Compatibility transition: `/api/v1` still accepts older edits without `expected_edit_version`; these retain legacy last-writer behavior. No cutoff/deprecation date is invented. Removing that compatibility requires a separately approved/versioned contract. All current first-party edit/deactivate/reset forms send revisions. Legacy callers are explicitly less protected against stale editors.
- `/api/v1/history/actors` is bounded/paginated Admin/Staff lookup of movement actors only, including inactive historical actors. It returns only `user_id` and current `full_name`; Staff never gets `/users` access. Suggestions do not enumerate all accounts.
- Ordinary movements accept controlled direction-compatible reasons, optional `reference_number` (100 characters), `party_text` (150), and existing notes. Updated ordinary forms require a reason. IN: purchase receipt/customer return/other receipt. OUT: sale-release/supplier return/damage-write-off/internal use/other release. Damage removes usable stock; no quarantine balance. Returns are ordinary movements, not mistake reversals.
- Legacy ordinary payloads may omit context; absent keys stay absent in canonical fingerprints. Historical reasons remain NULL/“Unspecified.” Creation/opening/reversal semantics and stored response JSON are retained. New system kinds distinguish opening, ordinary, reversal and count adjustment without accepting those kinds from public movement bodies.
- Explicit Admin restore requires product expected version, product lock, reference validation and atomic audit. ID/SKU/image/history are retained; stock archive eligibility is unchanged.
- Nullable successful-login time is written inside the successful authentication transaction, after password/account recheck and before issuing a new authenticated session. Failed credentials do not change it. Tracking-write failure rolls back and fails closed with no authenticated session; it is not silently ignored. Users/Account label Manila time and unknown dates “Not recorded.”
- Every Admin Users edit, including name-only changes, still revokes target sessions. Own-profile name edits still keep sessions. Individual sessions were not implemented.

### Phase 4

- Integer units: Unspecified, pieces, boxes, whole kilograms, whole litres, whole metres, pairs and sets. No fractions/conversions or simultaneous pack/base units. Old products default to Unspecified; old movement unit context remains NULL. Legacy create payloads default to Unspecified without changing their request fingerprint. New creation may specify a unit.
- Admin explicitly classifies an unspecified product via `/products/{id}/unit` with expected version and audit. Established units cannot be changed casually, even at zero. Classification does not relabel old movements. New normal movements capture the locked product's unit; reversals preserve the original movement's captured unit, including unknown.
- `stock_revision` advances with every shared stock append and relevant archive/restore/unit eligibility mutation. Metadata edits unrelated to stock do not masquerade as stock changes.
- Dashboard shows active quantities grouped by unit, including Unspecified. Legacy `total_units` and gross `activity` remain in the API for compatibility, but the old mixed-unit total/gross chart is not presented as meaningful operational activity. The UI uses unit-aware operational summaries with the existing 7/30-day shortcuts and a link to bounded custom-date analytics.
- `/analytics` supplies top outgoing products (net OUT per product), product/current-category summaries, captured-unit totals and no-operational-movement products. Custom dates must both be supplied, inclusive Manila days, maximum 366. Quantities/aggregates use exact integer/DECIMAL arithmetic; no accounting claim.
- Reversals offset the **original direction at the reversal timestamp**: OUT 5 on Nov 1 and its reversal on Nov 8 contribute OUT 5 and OUT −5 to their respective periods, with zero combined OUT. Opening stock, its reversals and physical-count adjustments/their reversals are excluded. No movement means no qualifying event, not merely a zero net sum; products created after period end are excluded. Category is current assignment, never invented movement-time category/supplier attribution.
- Historical classification limit: trusted product-create operation links identify old openings, and original legacy OUT is operational. An old IN without a recorded kind or trusted opening link is ambiguous; notes/request-key wording alone are not used to invent a classification. Such events are excluded and counted as `unclassified_events`, with a warning that results are incomplete. Products with ambiguous period activity are conservatively excluded from “no movement.” Resolving that history requires reliable evidence and separately reviewed classification; no ledger row is backfilled.
- Product/category summaries keep unknown and known captured unit groups separate. Top ranking is per product, never a total across products; if a product's qualifying history mixes known/unknown contexts, its ranking's unit label is Unspecified rather than inventing old units. History retains the individual contexts.
- Existing CSV columns/order, formula protection and 10,000-row/16-MiB limits remain. Explicit `format=detailed_json` downloads `bizstock.report.v2`: the standard API envelope contains machine-readable report, effective filters, snapshot, timezone/timestamp/unit meaning, scope and all matching items. Ignored page/page-size are omitted from filter context. No CSV metadata rows were added. Screen/current-page print shows unit context; all-matching detailed export is separately labeled. Full-report printing remains deferred.

### Phase 5

- `/stock-counts` list/detail/discrepancy entry and review: Staff/Admin can create/enter/submit, explicitly return submitted counts to draft, or cancel draft/submitted counts. Only Admin approves; Viewer is denied at page/API level. Approved/cancelled records cannot be silently edited or reopened.
- At most 100 lines per count. Each retains product ID, SKU/name snapshot, unit, expected quantity/revision, counted quantity, reason and adjustment link; discrepancy is derived from those recorded values. New entries/recounts verify the displayed stock revision under lock. Normal draft line edits retain the existing baseline. Nonzero discrepancy requires a reason.
- Approval rechecks the current actor, locks the count and all products in ascending ID order, and validates every baseline before posting anything. Changed stock, even if it returned to the same quantity, changed unit/eligibility or archive invalidates approval. No approval-time rebasing or partially approved count.
- The shared stock writer appends positive-quantity adjustment movements; zero discrepancies produce none. State, all adjustment links, audit and durable actor-scoped operation replay commit together. A lost response retries the unchanged approval key/payload. Another key/approver cannot approve it twice. History links adjustments back to the count. Cancelling a count never deletes history or reverses posted stock.

## Migrations and access paths

Code entry points: [StockMovementService](../src/Services/StockMovementService.php), [StockCountService](../src/Services/StockCountService.php), [StockCountRepository](../src/Repositories/StockCountRepository.php), [StockCountController](../src/Http/Controllers/StockCountController.php), [AnalyticsService](../src/Services/AnalyticsService.php), [AnalyticsRepository](../src/Repositories/AnalyticsRepository.php), [HistoryActorRepository](../src/Repositories/HistoryActorRepository.php), existing Users/lookup/auth/product services and repositories, shared/page JavaScript/templates, [OpenAPI](openapi.yaml) and the read-only [schema check](../bin/check-schema.php). Focused fixtures: [Phase 3](../tests/integration/phase3.php), [Phase 4](../tests/integration/phase4.php), [Phase 5](../tests/integration/phase5.php).

| Migration | Additions / historical handling |
|---|---|
| [005_edit_and_movement_context.sql](../database/migrations/005_edit_and_movement_context.sql) | Three edit counters default 1; nullable login time/context; binary controlled kind/reason checks; reason/date index. No historical movement rewrite. |
| [006_integer_units.sql](../database/migrations/006_integer_units.sql) | Product unit defaults Unspecified; stock revision defaults 1; transaction unit remains NULL historically; controlled-unit checks and unit/active index. |
| [007_physical_counts.sql](../database/migrations/007_physical_counts.sql) | Empty header/line tables with RESTRICT references, positive revisions, nonnegative quantities, required discrepancy reason, exact state/unit checks, unique adjustment link and supporting indexes. No counts seeded. |

Old migration files/checksums are unchanged. Each boundary is tested against populated disposable prior schemas; final fresh install/rerun is tested too. SQL follows the shared InnoDB/utf8mb4/ascii-bin/CHECK policy. Actual reason/date EXPLAIN demonstrates index eligibility; count-line reads use their deterministic primary path. Actor lookup reuses the existing transaction actor index; exact ID updates need no revision-only index. Tiny-fixture plans are not a volume/performance benchmark. MySQL 8.4 execution remains unverified.

Runtime privileges additionally require schema-scoped SELECT and INSERT/UPDATE on `stock_counts` and `stock_count_lines`, retaining the existing lock-read privilege policy. Do not grant UPDATE/DELETE on transaction history or DELETE on audits. The native disposable backup/restore/grant rehearsal includes populated count records and the least-privilege count workflow.

## Exact local upgrade procedure

1. Stop the BizStock PHP web server and keep all writers stopped. Leave the recovered local MariaDB server running. Preserve the current source checkout and existing `.env`; do not reset/reseed or replace credentials. Review the three SQL files above and the output of the read-only schema check.
2. From `C:\Users\reina\Bizstock`, create a private, operator-accessible backup while no application/image writes can occur:

```powershell
Set-Location -LiteralPath 'C:\Users\reina\Bizstock'
$selectedPhp = Join-Path $PWD '.tools\php\php.exe'
& $selectedPhp bin/check-schema.php
# Exit 2 is expected BEFORE upgrade: 005, 006 and 007 are pending.
$upgradeDir = Join-Path $PWD ('var\backups\pre-phase345-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $upgradeDir -ErrorAction Stop | Out-Null
& $selectedPhp bin/backup-database.php 'C:\xampp\mysql\bin\mysqldump.exe' (Join-Path $upgradeDir 'database.sql')
if ($LASTEXITCODE -ne 0) { throw 'Backup failed; do not migrate.' }
Copy-Item -LiteralPath '.env' -Destination (Join-Path $upgradeDir '.env') -ErrorAction Stop
Copy-Item -LiteralPath 'var\development-credentials.json' -Destination $upgradeDir -ErrorAction Stop
Copy-Item -LiteralPath 'var\uploads' -Destination (Join-Path $upgradeDir 'uploads') -Recurse -ErrorAction Stop
& $selectedPhp bin/reconcile-stock.php
if ($LASTEXITCODE -ne 0) { throw 'Review stock discrepancies before upgrade.' }
```

Keep this directory outside `public/`, private and untracked. The initial-credential file is archival, not necessarily the current login password. If that optional file is absent, omit only that copy after confirming why; do not create replacement accounts. The SQL dump contains current password hashes, migration history and all business records; the `.env`/uploaded files require coordinated filesystem backup. Test restoration into a separate empty target, never over existing activity.

3. With writers still stopped and the intended **local** `.env` reviewed, run the existing runner manually:

```powershell
& $selectedPhp bin/migrate.php
if ($LASTEXITCODE -ne 0) { throw 'Keep writers stopped and review committed migration state.' }
& $selectedPhp bin/check-schema.php
if ($LASTEXITCODE -ne 0) { throw 'Schema is not ready; do not reopen the app.' }
& $selectedPhp bin/check-database.php
if ($LASTEXITCODE -ne 0) { throw 'Database policy check failed.' }
& $selectedPhp bin/reconcile-stock.php
if ($LASTEXITCODE -ne 0) { throw 'Stock reconciliation failed.' }
& $selectedPhp -d display_errors=0 -S 127.0.0.1:8080 -t public bin/router.php
```

The operator principal needs DDL privileges; a restricted web principal must not be elevated permanently. A safe second runner invocation reports all seven already applied. Sign in with existing privately chosen credentials and inspect existing products/History/reports/role restrictions. Do not generate installed demo movements/counts just for smoke testing. Forced-change behavior remains in effect where already required; no reset was performed for this implementation.

## Rollback without discarding later records

- DDL commits implicitly. A failed migration remains `running` with completed-statement progress and blocks blind rerun. Keep writers stopped, retain the database/backup/logs/original SQL and review the actual state for a forward repair. Do not mark it applied by hand without verification, edit its recorded checksum, drop new tables, reset or reseed.
- After upgrade, keep the new stock/eligibility revision and unit guards in every writer even if new screens are disabled. A pre-Phase-3 writer cannot safely continue stock writes and later resume pending counts because it does not advance these revisions.
- A compatible maintenance release may disable new entry/approval/analytics while retaining read access to existing counts, units, context, movements and audits. Approved adjustments stay in the ledger; corrections use authorized compensating records, not history edits. Old application code is read-only recovery at most, not a safe write rollback.
- Never restore the pre-upgrade database over subsequent stock, account, count, image or audit activity. Restore only into a separate empty inspection target; reconcile and forward-recover any required records with a reviewed plan.

## Executed verification and limits

Selected PHP 8.5.11 / Composer; real loopback MariaDB 10.4.32. All mutation tests used randomized disposable schemas/accounts/files. No installed-account login/reset, migration, seed, repair or deployment was run. [Progress](progress.md) records final commands/counts. Composer strict/check, focused phases, stock/auth/permissions/catalogue/users/lookups/History/reports/Audit, schema, native backup/restore/grants and locked clean install were executed. API client/OpenAPI and JavaScript checks accompany those results.

Phase 3: 40 DB/HTTP/harness and 10 Chrome assertions. Phase 4: 24 DB/HTTP/harness and eight Chrome assertions. Phase 5: 40 DB/HTTP/harness and 17 Chrome assertions, including draft/submitted/approved accessibility, retained stale baseline, explicit return/recount and lost-response replay. Native backup/restore/grants passes 25 assertions including populated count tables/adjustment links. Browser review uses actual Chrome at 360/768/1440; private captures include count draft/approved and analytics. Manual screen-reader/native-menu zoom/participant acceptance and representative-volume load tests were not executed. MySQL 8.4 and production behavior are independently unverified.

Private before/after evidence compares all ten installed table hashes (including account credential hashes, audit and four applied migration rows), the original migration files, `.env`, private initial-credential file and uploaded files. New migration files are additions, not applied rows; the original protected set must match. Logs/test captures and other ignored delivery files are not an independently snapshotted set. The current read-only schema check explicitly reports 005–007 pending; installed feature operation is not claimed.

## Phase 6 — assessment only

- **Purchase orders:** useful only with a real procurement need. Requires supplier/order-line identity and captured units/prices, partial receipts, over-receipt/cancellation/correction rules, state revisions and atomic receipt/IN idempotency. Reasons/reference text alone are not purchase orders.
- **Stock requests/reservations:** begin with approval authorizing release only if needed. Reservations need a separate allocation model, expiry/cancellation and available-versus-on-hand concurrency rules across every OUT/count operation. Do not hide reservations in this balance.
- **Individual sessions:** dedicated registry, revocation/expiry checks and privacy-safe device metadata are prerequisites; current global session-version revocation remains.
- **Bulk metadata editing:** dedicated edit revisions now provide a prerequisite. Still needs bounded scope, per-row previews/audits and an explicit all-or-nothing versus partial-conflict decision. Never bulk-edit quantity.
- **Fractions/conversions:** require new decimal/base-unit semantics, rounding, price/threshold/count/export contracts and careful historical migration. Established integer units must not be silently reinterpreted.
- **Import/global search/saved presets:** separately justify value, permission-safe scope, input/duplicate handling and data ownership. None was implemented.
