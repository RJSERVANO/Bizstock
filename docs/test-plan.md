# Test evidence and requirement audit through Stage 15

## Usability and explicit role matrix (2026-10-12)

REQ-03/04/14/18/19/23/26-28: [actual acceptance and installed-account limitations](usability-review.md). New test:usability covers 90 MariaDB/HTTP + 99 Chrome/20 axe; test:permissions covers 145 MariaDB/HTTP + 81 Chrome/nine axe. Actual API/browser evidence records authenticated roles, direct statuses, positive response fields, exact nav/actions and conflicting hints. Both suites reconcile before/after on isolated 10.4.32 databases. Main final reconciliation is BLOCKED by original instance Aria/system-plugin failure, not counted as a pass. MySQL/HTTPS/hosted image/participants remain untested. See progress for complete affected-run counts/commands.

Public homepage extension, 2026-10-12: [actual test matrix](public-homepage.md) supplements REQ-02/03/26/27/28/31/33. New `composer test:homepage` passes 28 HTTP/MariaDB/harness and 68 Chrome assertions/18 clean axe scans. Root and nested-base foundation HTTP checks pass. Auth/images/users/full inventory/shared UI regressions pass with documented commands/counts. All tests use isolated data. Private images/credentials are preserved, stock reconciles; the initial snapshot differs because of concurrent installed app edits, explicitly documented rather than claimed unchanged. MySQL 8.4, production HTTPS, hosted storage and participant/manual AT/hosted CI remain pending.

2026-10-12 live UI refinement: `php tests/integration/ui-refinement.php` PASS 152 assertions/111 clean axe scans, with matched 36 before screenshots and 111 current base/state/login captures at 360/768/1440. Supplemental command `php tests/integration/ui-refinement.php supplement` verifies a real empty dashboard, explicit loading/error injection, real opening-stock availability, no-write confirmation and the real account menu. Auth, lookups, users and cross-role system suites were rerun; final business suite passes all 159 DB/harness and 116 Chrome assertions/18 axe scans. Composer validation/check passes 121 PHP syntax, 139 foundation, 14 policy and 64 API; OpenAPI/client/doc and all JS syntax checks pass. Installed stock/ten-table/credential preservation passed before/after. See [refinement details](ui-refinement.md); production/participant obligations stay pending.

Final supplemental result: 36 assertions/15 clean axe scans/15 screenshots. Authentication final result: 336 DB/security and 32 Chrome/seven axe scans, now explicitly verifying restricted forced-change screen and subsequent shared shell. Seven authentication screenshots bring total after captures to 133. Repeated runs are not added to assertion totals. Complete business rerun and final focused movement rerun pass after the confirmation styling/copy refinements.

Authentication wait follow-up: `php tests/integration/stage6.php` now PASS **336** local MariaDB assertions and **30** Chrome assertions/seven clean axe scans. Added five-minute Retry-After bound, legacy fifteen-minute window shortening without clearing attempts, visible countdown/disabled submit/accessibility/expiry and no automatic retry checks. The browser's short countdown expiry uses an explicit intercepted 429; real database checks separately prove production handler throttling. Client/OpenAPI/release-doc checks and Composer strict validation/check pass (118 syntax, 139 foundation, 14 policy, 64 API). Installed stock reconciliation remains three products/zero drift; MySQL/HTTPS remain pending. Earlier totals below describe the Stage 15 checkpoint.

2026-10-11. Local target: verified XAMPP MariaDB 10.4.32, PHP 8.5.11, actual Chrome with locked Playwright/axe. Test scripts create migrated isolated databases/accounts/processes and clean them. Existing installation is hash-compared and reconciled before/after. Local results do not prove MySQL or production HTTPS. Never substitute SQLite or synthetic success for a target test.

## Commands and actual results

Invoke PHP with `.tools/php/php.exe` and Composer PHAR `C:/ProgramData/ComposerSetup/bin/composer.phar` locally.

| Command | Actual local result |
|---|---|
| `composer validate --strict`; `composer check` | PASS manifest, 118 PHP syntax, 139 foundation, 14 engine-policy and 64 API assertions |
| `composer test:users` | PASS 37 database/API/CLI assertions and final 21 Chrome assertions/seven clean axe scans; canonical duplicates, role/CSRF denial, safe reset/forced change/revocation, atomic audit failure, concurrent final Admin, stdin recovery, real create/edit/reset/deactivate/reactivate/reload and audit screens |
| `composer test:auth` | PASS 334 security assertions and 25 Chrome/six axe scans; route-role matrix updated to actual Stage 13 handlers without mutating existing accounts |
| `composer test:system` | PASS 11 DB/API and 180 browser assertions, 63 clean axe scans/105 screenshots, 360/540/768/1024/1440px; owner creates parents/product/opening, Staff OUT, Viewer equality alert, Admin CSV, all permitted live screens, long names, drawer/keyboard/focus/dialog checks |
| `composer test:business` | PASS 49 stock, 43 products, four movement, 13 dashboard, 43 report and seven full-flow DB/harness checks; 116 Chrome assertions and 18 axe scans. Actual independent-process oversell/retry/reversal/archive/deadlock tests; lost-response product/movement effects, original prices/Manila boundaries/CSV and complete cross-role flow |
| `composer test:schema` | PASS 112 fresh/rerun/upgrade/constraint/bootstrap/seed/reconciliation assertions, unchanged applied migrations |
| `composer test:lookups` | PASS 84 DB/API and 35 Chrome assertions/six axe scans, reload/reference/authorization/audit rollback |
| `composer test:backup` | PASS 22 native MariaDB dump/restore/grant checks, exact ten-table restoration including password hashes, preserved source, safe migration rerun, balance and least-privilege append/reversal/user/audit access; history UPDATE/audit DELETE forbidden |
| `composer test:install` | PASS eight clean copied-release assertions: offline locked --no-dev install, verified assets, empty database ten-table migration, safe rerun/unchanged lock, zero demo users, installed health dispatch and reconciliation; private package/database cleaned |
| `node tests/openapi.cjs`; `node tests/api-client.cjs` | PASS all 32 implemented operation contracts/566 references and transport/CSRF/session/key/CSV/error checks; real complete-flow schemas checked against repository validator subset |
| `node tests/release-docs.cjs` | PASS 16 stage flags (1-15 checked, 16 unstarted), 17 structured gap rows, all 33 requirement mappings, 112 local links, unchanged blueprint and authored manual CI definition; no claim of CI execution |
| `php bin/reconcile-stock.php` before/after; private baseline comparison | PASS three installed products, zero drift, 17/10/0; all ten installed table hashes/private credential hash unchanged |

Counts include fixture/login/harness assertions, not that many distinct scenarios; repeated runs are not added to totals. Initial new-browser test failures were test harness setup/required-label matching and corrected before acceptance. Initial restore rerun assertion wrongly expected an empty result instead of three `already applied` entries; corrected against MigrationRunner's actual contract. A Windows cleanup warning in the copied-package test was repaired by releasing iterator handles before directory removal; final install run has no warning. These failures are not counted as passes.

## Requirement mapping

| Requirement | Evidence / release status |
|---|---|
| REQ-01 stack/runtime/isolation | foundation 139 + clean package install; actual production host pending |
| REQ-02 sessions/CSRF/login | auth 334/Chrome; production cookie/TLS pending |
| REQ-03 role/field policy | auth matrix, users 37, products/dashboard/reports/flow direct API projections; MySQL pending |
| REQ-04 user/last Admin | users 37: sentinel-serialized competing removal, unique identity/validation; MySQL pending |
| REQ-05 reset/recovery | actual reset/forced-change/revocation and stdin CLI recovery, auth regression |
| REQ-06 lookups | lookups 84 + real system owner-created parents/reference denial |
| REQ-07 product identity | products 43, canonical uniqueness/races/immutable SKU, database constraints |
| REQ-08 atomic opening | products/stock/flow, audit failure rollback and lost-response replay |
| REQ-09 stale product edits | products parallel editors/version conflict |
| REQ-10 archives | both actual lock race orders, zero-only archive and retained history |
| REQ-11 stock integrity | stock 49 independent OUT/rollback/overflow/deadlock checks |
| REQ-12 retries | stock/product concurrent replay/changed payload; browser lost response |
| REQ-13 full reversal | stock 49 original price/quantity/reason/unique link/insufficient/archive and direct Staff rejection |
| REQ-14 immutable history | original row comparison, API PATCH/DELETE 405, inactive/archived joins and runtime grant denial |
| REQ-15 audit | lookup/product/account/password/recovery atomic audit and forced failure rollback, safe Admin log fields |
| REQ-16 reconciliation | schema drift refusal/read-only checks, per-suite reconciliation and installed before/after |
| REQ-17 catalogue | products browser reload/forms/filters/sorts/archive, system long-name/mobile views |
| REQ-18 movement screen | actual lost response/double confirmation/stale stock and delayed selection/original invalidation |
| REQ-19 dashboard | dashboard 13 snapshots/equality/zero/archive/wide totals/freshness and Viewer fields |
| REQ-20 reports | reports 43 and browser/filter/download/print/archived history |
| REQ-21 time/decimal | real UTC/Manila microsecond boundaries, captured-price exact aggregates |
| REQ-22 CSV | full-column/filter parity, formula/quote/multiline defenses and row/byte caps |
| REQ-23 contracts | OpenAPI 32 implemented/566 references, validation/client/flow schema subset; exhaustive independent conformance validator pending |
| REQ-24 abuse/error bounds | foundation/API/engine-policy/real prepared-query checks, unknown fields and direct denials |
| REQ-25 schema/upgrade | schema 112, native restore/exact tables and clean-package fresh/rerun; MySQL pending |
| REQ-26 shared UI | actual live components/client, 105 screenshot captures, visual spacing/SKU/table repair; no live fixtures/dead planned APIs |
| REQ-27 accessibility | 63 system + seven user axe scans, five widths, selected keyboard/focus/dialog checks; complete manual assistive-technology/WCAG conformance pending |
| REQ-28 states/buttons | business/auth/lookup state/error/retry tests, pending/uncertain intents and user pager-bound repair |
| REQ-29 integration/CI | actual complete flows and isolated DB/browser regressions; CI workflow authored, execution/MySQL runner pending |
| REQ-30 participants | scripts/metrics/improvement log prepared; **actual participant sessions pending**, no feedback invented |
| REQ-31 runbook | deployment/user guide, backup 22/install eight, additive upgrade; hosting/production restore/monitoring pending |
| REQ-32 deploy | **Not performed**, Stage 16 not authorized in this task; hosting/domain/TLS/database/deployment credentials missing |
| REQ-33 gap/evidence discipline | progress/gaps updated per included stage; existing data/credentials preserved; no release blocker silently marked closed |

## Remaining release gates

MySQL 8.4 constraints/SQL/concurrency/native clients/grants/full browser execution and CI are **NOT VERIFIED**. Production HTTPS/cookie flags/public-root/host configuration, production backups/monitoring/restore and release cutover are **NOT VERIFIED**. Real owner/Staff/Viewer feedback and broader manual assistive-technology assessment are **pending**. Full WCAG 2.2 AA conformance is not inferred from axe. CI is an authored workflow, not an executed result. No production credentials, deployment or purchase occurred; stop after Stage 15.

## Authorized sidebar/private image acceptance
Commands: selected PHP composer test:images (54 real MariaDB/HTTP assertions, 50 Chrome assertions/18 clean axe scans); test:schema (112), test:auth (336 + 32 Chrome/seven axe), test:users (37 + 21 Chrome/seven axe); native stage15 (22); install (8); ui-refinement (152/111 axe); composer validate --strict/check; node tests/openapi.cjs (38 ops/653 refs), tests/api-client.cjs and tests/release-docs.cjs. Actual fixture screenshots at 360/768/1440: [gallery](visual/images/index.html). Generated test photos and isolated databases/namespaces are removed. Main private baseline comparison includes pre-existing columns/rows, applied migration records and credential file; additive null columns/new migration are expected changes. Main stock before/after: three products, zero discrepancies. [Design/limits/login/pending targets](private-images.md).

Final complete business suite: PASS 159 database/harness and 117 Chrome assertions/18 axe scans. Movement regression dispatches a late close event against an open review and verifies its intent stays locked; stock service is unchanged. Export integration waits for the applied filters to finish before requesting CSV; no loading/export guard weakened.
