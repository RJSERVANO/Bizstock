# BizStock progress

## Public homepage, role sign-in and navigation (2026-10-12)

Implemented public / with original navy/teal branding, honest feature/workflow/About/contact content, labeled sample preview and locally optimized/disclosed generated stockroom artwork. Central validated public name/contact configuration defaults to administrator guidance. Public page works signed in without exposing private identity/inventory/uploads; current/forced/revoked session CTA verified. Single login has shared role guidance and Back to home; actual API forced-change flag selects Account or Dashboard. Shared sidebar grid fixes collapsed alignment (all centers x=40px), distinguishes profile/group/clipboard-history icons and preserves preference, tooltips and mobile focus. Existing uploads/table/cards/migration 004 reused; no new migration/API/stock policy.

PASS new homepage: 28 HTTP/MariaDB/harness checks, 68 Chrome assertions, 18 clean axe scans and 8 before/19 after captures at 360/768/1440. PASS affected auth 336 DB/security + 32 Chrome/7 axe; images 54 DB/HTTP + 50 Chrome/18 axe; inventory 159 DB/harness + 117 Chrome/18 axe; users 37 DB/API + 21 Chrome/7 axe; shared UI 152 assertions/111 axe. Composer strict/check PASS: 129 PHP syntax, 139 foundation, 14 engine-policy, 64 API; OpenAPI 38 operations/653 references, client and release-doc checks PASS. Full results recorded in [consolidated evidence](public-homepage.md).

Main stock reconciles three products/zero drift. Concurrent live product/photo edits explain differences from the initial 01:16 table snapshot and are preserved; final comparison uses the later 01:29 snapshot. Private credential file and two installed uploaded files match; no installed-account password/reset test was performed. Active dev_admin/dev_staff/dev_viewer and root/login HTTP 200 verified. [Access/recovery and screenshots](public-homepage.md). MySQL 8.4, production HTTPS, hosted storage/backup, actual participants/manual AT and hosted CI remain pending; no deployment or Stage 16 work.

## Sidebar and private image extension (2026-10-12)

Implemented accessible, locally remembered desktop full/rail navigation with named links, focus/hover tooltips and Escape dismissal; retained mobile trapped/dismissible drawer. Added secure own-profile and Admin product-image uploads/replacement/removal, consistent avatars/default initials, optional inventory thumbnails/placeholders, larger details image and role-consistent card view using the same real page. Shared components/client/services and existing auth/CSRF/atomic actor locks reused. Four additive migrations/ten tables; previous migration files unchanged; blueprint scope amendment explicitly records owner authorization.

Local MariaDB + actual HTTP: PASS 54 image/database assertions including all three image formats, malformed/SVG/executable/oversize/dimensions, CSRF/role/forced-change denial, controlled reads/public-path denial, safe removal/replacement, concurrent independent-server replacements and audit-failure rollback/staged-file cleanup; before/after reconciliation. Chrome: PASS 50 assertions and 18 clean axe scans, upload persistence/default avatars, keyboard rail/preference/tooltips/drawer, thumbnails/details/cards and Staff/Viewer restrictions; actual Account/edit/table/cards at 360/768/1440. [Design, local login and responsive gallery](private-images.md).

Affected regressions: schema 112; authentication 336 DB/security + 32 Chrome/seven axe; users 37 DB/API + 21 Chrome/seven axe; native backup/restore/grants 22; clean package install 8; shared UI review 152 assertions/111 clean axe scans. Composer strict/check: 126 PHP syntax, 139 foundation, 14 policy and 64 API assertions. OpenAPI: 38 implemented operations/653 resolved references; shared client and release-document checks pass. Final complete inventory suite PASS: 159 database/harness assertions and 117 Chrome assertions/18 axe scans, including concurrent stock updates, uncertain retries, immutable reversals, archive races, alerts, CSV accuracy and full creation-to-export flow.

Private pre-migration backup created. Installed migration and safe rerun pass. Existing-column row hashes across all nine data tables, original migration records and initial-credential file hashes match; three installed products reconcile with zero discrepancies. Confirmed active dev_admin/Admin, dev_staff/Staff and dev_viewer/Viewer and shared /login returns 200. No passwords printed, accounts reset, credentials rotated or deployment performed.

Intermediate failures repaired: PHP 8.5 removed obsolete GD destroy calls; brand accessible name in rail; file-picker mobile width; view-toggle transition contrast; test-only selectors now target Supplier combobox; schema upgrade/projection assertions account for intentional additive columns/presence flags. Repeated movement timing failures exposed a delayed native dialog-close event clearing a newly opened review: the UI now ignores obsolete close events while a dialog is open, with a meaningful regression. The export test now waits for newly applied filters to finish loading rather than matching the unchanged old row count; the server loading guard is preserved. Final complete inventory suite passes. MySQL 8.4, production HTTPS, hosted GD/ACL/image-files backup rehearsal, actual participants/manual AT and hosted CI remain pending. Stage 16 not started.


## Live UI refinement (2026-10-12)

Final supplemental acceptance: 36 assertions/15 clean axe scans, real empty dashboard and availability, no-write confirmation and account-menu keyboard/focus/real sign-out. Authentication final: 336 DB/security and 32 Chrome/seven axe scans. There are 133 after captures including supplementary/authentication states. Final main-table/credential hash comparison and three-product reconciliation pass after all tests.

Implemented the owner's current-screenshot refinement through shared tokens/components/templates: navy grouped sidebar and compact account menu; stronger typography/cards; compact common filters and expandable advanced controls; tables/actions/pagination; padded product/movement forms and availability/review panels; unrestricted Account in the shared shell, restricted forced-change screen retained; dedicated Users editor; authorized name searches, business wording and accessible alerts with optional support references. Asset content hashes prevent stale styles/scripts. No business API contract, role policy, migration, stock writer, credentials or five-minute limit weakened.

Actual browser baseline: 36 screenshots and computed-style evidence before changes. Final refinement review: PASS 152 assertions, 111 clean axe scans and 111 base/state/login screenshots at 360/768/1440. Supplemental empty-dashboard/availability/confirmation screenshots and authentication error/forced-change/countdown captures accompany them. Cross-role system review: PASS 180 assertions/63 clean axe scans/105 private screenshots across five widths. Real empty queries are distinct from explicit browser loading/error injection. [Changes and evidence](ui-refinement.md), [before/after gallery](visual/refinement/index.html).

Final complete business suite PASS 159 DB/harness and 116 Chrome assertions/18 axe scans, including uncertain-network replay, concurrent writes, reversal, archive and export accuracy. Administration PASS 37 DB/API/CLI and 21 Chrome/seven axe; lookup management PASS 84 DB/API and 35 Chrome/six axe; authentication PASS 336 DB/security and browser/countdown/shell checks. Composer strict validation/check PASS (121 PHP syntax, 139 foundation, 14 policy, 64 API). OpenAPI/client, release-document and JavaScript checks pass. Main reconciliation and private hashes before/after PASS: three products/zero drift, 17/10/0, all ten tables and credential file unchanged.

Intermediate failures were fixed and rerun: forced-change confirmation after entering the shell, name-choice/test selectors, and stale assertions for deliberately replaced uncertainty wording. Current evidence supersedes previous styling, not earlier business decisions. MySQL 8.4, production HTTPS, actual participants/manual assistive-technology acceptance and hosted CI remain pending. Stage 16 not started; no deployment.

## Five-minute sign-in wait update

Owner requested reducing the authentication rate-limit window to five minutes and showing its remaining time. Login/password-change account and peer buckets now use 300-second windows, retaining attempt counts and shortening legacy windows from their original start time. The shared client exposes numeric Retry-After; login/account forms show a countdown and labeled progress bar, block repeated submits until expiry and announce manual retry availability. No automatic login retry or password-policy change. Existing accounts, credentials and inventory preserved. Current isolated MariaDB authentication and Chrome countdown checks pass; production MySQL/HTTPS remain pending.

Last updated 2026-10-11 (Asia/Manila). Stages 1-15 implemented and locally checked. Stage 14 automated review/scripts and Stage 15 preparation are complete locally; actual participants, MySQL 8.4, production HTTPS, CI execution and hosting remain pending. Existing data/credentials preserved; Stage 16 not started, no deployment. Earlier entries are historical. See [test evidence](test-plan.md) and [deployment preparation](deployment.md).

## Stage checklist

- [x] 1. Requirements, architecture, decisions, gap audit — specification documents reviewed and structural checks passed.
- [x] 2. Repository and runnable foundation — verified PHP runtime, locked clean install, configuration, routing, assets, health and isolation.
- [x] 3. Shared design system and shell — shared UI, labeled development fixtures, browser/accessibility/screenshot review passed.
- [x] 4. Migrations and consistent seeds — actual MariaDB fresh/rerun/data-preserving upgrade/constraints/bootstrap/seeds/reconciliation verified; MySQL pending.
- [x] 5. API foundation and contracts — shared router/validation/envelopes/client and OpenAPI; only health live, planned routes 404.
- [x] 6. Login, sessions and authorization — actual MariaDB/HTTP/browser checks passed; MySQL and production TLS verification pending.
- [x] 7. Categories and suppliers — real Admin CRUD/audit, Staff reads and Viewer restrictions, persistence/validation/reference/browser checks pass on local MariaDB.
- [x] 8. Atomic stock service and reconciliation — reviewed 49 MariaDB/domain/concurrency/HTTP assertions; actual deadlock/timeout, actor revocation and archive races verified.
- [x] 9. Product management — final 43 database/HTTP assertions and 21 Chrome assertions, including live header sorting and mobile quantity summary and three clean form accessibility scans; real archive races verified.
- [x] 10. Stock movement UI and history — reviewed 27 Chrome assertions (six clean axe scans), lost-response retry/double-click/stale-stock/full-reversal and delayed-selection checks; four database assertions pass.
- [x] 11. Live dashboard and low-stock alerts — 13 database assertions plus 16 browser assertions and three clean axe scans; consistent snapshot, alert-filter parity and restricted Viewer responses verified.
- [x] 12. Reports and CSV exports — reviewed 43 database/export assertions, 30 Chrome assertions and six clean axe scans; full-column CSV parity, combined filters and complete cross-role workflow verified.
- [x] 13. Admin user management and audit/recovery - 37 local DB/API and final 21 Chrome assertions; seven clean axe scans; auth regression passed.
- [x] 14. Integration/accessibility review and participant scripts - local automated evidence complete; actual participants/manual assistive-technology acceptance pending.
- [x] 15. Deployment preparation and disposable restore rehearsal - 22 native backup/restore/grant and eight clean package checks; host/MySQL/HTTPS/CI execution pending.
- [ ] 16. Authorized real-target deployment and smoke verification.

## Stage 1 outputs and verification

- requirements.md: REQ-01–REQ-33, each with measurable acceptance and owning stages; exact permission matrix, response restrictions and MVP exclusions.
- decisions.md: DEC-01–DEC-16, stack/business/security/identity/retry/time/price decisions and permitted supporting-table rationale.
- architecture.md: boundaries/layout, planned five domain plus five supporting tables, FK/check/index intentions, transaction ownership, locking/retry/reversal/last-Admin plans and verification strategy.
- gaps.md: GAP-01–GAP-17 with feature, severity, expected behavior, fix, stage, required regression evidence and honest open status; all ten mandatory closure rules covered.

Commands run from C:\Users\reina\Bizstock:

1. `Get-ChildItem -Force -Recurse -Path . | Select-Object FullName` — initially no entries; workspace empty.
2. `git status --short` — reported “not a git repository”; no commit or Git initialization performed in specification stage.
3. Direct `Test-Path`/`Get-Content` checks for AGENTS.md in workspace and C:\, C:\Users\, C:\Users\reina\ — none found. Broad parent listing was access denied; direct applicable-path checks succeeded and workspace traversal found no nested instructions.
4. `Get-Content -Encoding UTF8` of source blueprint across ranges 0..174, 175..290, 290..440 — all 441 lines read, including master instruction and Stages 1–16.
5. `Get-FileHash` source and docs copy — identical SHA-256; source preserved unchanged.
6. PowerShell document validation — required files nonempty, IDs unique/sequential, every requirement row has acceptance/stage mapping, decision requirement references resolve, all gap rows have seven fields and open status, checklist has all 16 stages with only Stage 1 checked, relative Markdown links resolve, blueprint hash matches, workspace contains only six Markdown documents. Passed; exact executable check is recorded in verification output from this session.
7. Manual consistency review — role matrix/Viewer field exclusion, RESTRICT history, original-price full reversal, zero-only archive locks, caller-owned opening transaction, actor-scoped replay and last-Admin serialization agree across outputs. No SQL or runtime behavior claimed verified.

## Stage 2 outputs and verified results

Implemented AGENTS.md, README.md, .gitignore, .env.example, composer.json/composer.lock, configuration/PDO factory, HTTP application/controller/response/error handling, public front controller/rewrite rules, minimal template, development scripts, pinned asset installation and tests/foundation.php. Git initialized without commit. Updated decisions/architecture and GAP-09/GAP-10 progress; all 17 release gaps remain open. No Stage 3 design-system or business feature added.

Runtime detection: XAMPP PHP 8.2.12, MariaDB 10.4.32 client, Apache 2.4.58, Composer 2.9.8. No responding server at 127.0.0.1:3306; Docker unavailable on PATH. Selected PHP 8.5.x and MySQL 8.4.x LTS. Downloaded official portable PHP 8.5.11 into ignored .tools/php, verified ZIP SHA-256 `0ea96e0d2b9b737a6036f05cf4e95c49313faa6d0f27bd97edb2742503f0c043`, enabled required extensions and tested. No XAMPP/global runtime changed.

Verification commands below use `& .tools/php/php.exe C:/ProgramData/ComposerSetup/bin/composer.phar` as the Composer invocation so the old system PHP is not used:

| Command / check | Actual result |
|---|---|
| `php -v`, `php -m`, `composer --version`, XAMPP `mysql.exe --version`, `httpd.exe -v` | Detected versions above; portable PHP -v = 8.5.11 |
| `git init` | Initialized empty repository; no commit |
| `composer update --no-interaction --prefer-dist` | Created lock and installed seven packages; final lock includes phpdotenv 5.7.0 and Bootstrap 5.3.8; no security advisories reported |
| `composer setup` / `composer assets` | Configuration/private directories ready; Bootstrap CSS/JS matched official SHA-384 hashes and license copied |
| `composer validate --strict` | PASS, no warnings after manifest repair |
| `composer check:runtime` / `composer check-platform-reqs` | PASS on PHP 8.5.11 with required extensions |
| `composer check` | PASS: 17 PHP files syntax checked; 84 foundation assertions including real HTTP root, /bizstock and nested base paths |
| `composer check:database` | FAIL (expected missing target prerequisite); sanitized diagnostic, no credentials emitted; no MySQL connected |
| Clean install copy: `git ls-files --cached --others --exclude-standard`, copy into `.tools/fresh-stage2`, `composer --working-dir=.tools/fresh-stage2 install --no-interaction --prefer-dist` | PASS: 32 repository source/document files copied without .env/vendor/assets/tools; seven locked packages installed into new vendor directory |
| Clean copy `setup`, `assets`, `validate --strict`, `check` | PASS: fresh configuration/assets, valid manifest, 17 linted PHP files and same 84 HTTP/config assertions |
| Setup rerun with .env hash comparison; PowerShell progress/gap/link audit | PASS: private .env unchanged; all 16 stages present with only 1/2 checked, all 17 gaps open with required fields, relative documentation links resolve |
| `git check-ignore .env vendor/autoload.php .tools/php/php.exe var/logs/app.log public/assets/vendor/bootstrap.min.css` | All five private/generated paths ignored; .env.example remains included |
| `Get-FileHash docs/BizStock-Codex-Build-Blueprint.md` | Original SHA-256 still `84C8857AB8F09A8E269266DF12CA82F8103661B5F113C18058FEEFBBB86B1967` |

HTTP evidence covers GET/HEAD health, 405 for POST with Allow header, 404 for unknown/later-stage routes, minimal no-secret JSON, request IDs, generated base URLs, root/subdirectory asset delivery, redirects/prefix boundaries and denial of .env/.git/source/config/vendor/docs/log paths and traversal variants. Invalid bootstrap configuration and internal error responses remain generic; private logs contain request ID and safe exception metadata without secret message.

Repairs during verification: Composer strict validation warned on exact manifest constraints, resolved by compatible manifest constraints with exact lockfile pins; initial CSS test assumed one space in upstream version comment, corrected to whitespace-tolerant match. Both checks now pass. Sandbox network denied dependency download initially; authorized retry succeeded. Elevated Composer emitted Git ownership warnings from the different execution user; normal workspace Git works and install succeeded, without changing global safe.directory.

## Blockers and next-stage prerequisites

No remaining blocker for Stage 2 page/install/routing acceptance. Actual MySQL 8.4 target/database credentials and DB connection/constraints remain unverified; needed before Stage 4 database acceptance. Apache root/Alias examples are documented but not live-installed, and existing XAMPP PHP/MariaDB cannot be treated as the target. Selected production host/credentials remain pending for Stages 15/16. No migrations, database-backed integration tests, business endpoints, user tests or deployment have run.

## Stage 3 outputs and verification

Implemented public/assets/css/{tokens,components,layout}.css, public/assets/js/components.js and pages/preview.js; reusable PHP Ui and eight component templates; app layout/sidebar/mobile drawer; login/dashboard/inventory/grouped create/edit product form and component gallery. Fixed Viewer fixture contexts are server-filtered; no role-switching widget. PreviewController uses allowlisted screen/state/mode and routes are unavailable in production. Added locked browser tools/test runner, 70 screenshots and results JSON under docs/visual/stage3, docs/design-system.md, docs/visual-review.md and docs/database-compatibility.md. Updated README, architecture, decisions and this gap audit. No business API/schema/authentication/persistence added.

| Command / verification | Actual result |
|---|---|
| `& .tools/php/php.exe C:/ProgramData/ComposerSetup/bin/composer.phar validate --strict` | PASS |
| Same Composer invocation with `check` | PASS: 33 PHP files linted; 118 HTTP/config/foundation assertions, including fixture routes at root/subdirectories and denied production fixtures |
| `node --check public/assets/js/components.js`, `node --check public/assets/js/pages/preview.js`, `node --check tests/browser/review.cjs` | PASS |
| `npm.cmd --prefix .tools/ui-checks install --save-exact playwright @axe-core/playwright --cache .tools/npm-cache` | Initial sandbox network failure; authorized retry installed tools; exact manifests/lock copied under tests/browser |
| `npm.cmd --prefix tests/browser ci --cache .tools/npm-cache --offline --ignore-scripts --no-audit` | PASS; four locked browser-test packages installed, no frontend build introduced |
| `node tests/browser/review.cjs` | Final PASS: 386 assertions, 66 screen/state/viewport axe scans with zero detected violations, plus clean drawer/dialog scans; 70 screenshots; Chrome 155.0.8059.40 |
| Actual keyboard/interaction checks | Drawer focus trap/Escape/background inertness/return focus; dialog Escape/return focus; login/product error summaries; unsaved keep/discard; table filtering/sorting/pagination/keyboard scrolling; button hover/focus/disabled/busy; reduced motion all passed |
| Responsive and contrast checks | No page horizontal clipping at 360/768/1440 and 480/900/1024px; text pairs 6.37–6.80:1; mobile quantities/thresholds visible beside names; SKU nowrap |
| Manual screenshot review | Main screens across requested sizes and representative states/gallery/drawer reviewed. SKU wrapping/mobile stock discoverability repaired, final screenshots regenerated. See visual-review.md for exact scope/limitations |
| `git check-ignore tests/browser/node_modules/playwright/package.json` | Browser installations ignored; lockfiles/test source and screenshots remain repository artifacts |

REQ-26/REQ-27/REQ-28 Stage 3 obligations are mapped to visual-review.md and its actual evidence. This does not close GAP-16 for future feature screens or replace Stage 14 testing. All 17 release gaps remain open; database verification remains pending and no database check was claimed passed in this stage.

## Before Stage 4: database compatibility report

Current code enforces MySQL 8.4 in PdoFactory. UI is database-independent and the proposed domain model can be adapted to installed MariaDB 10.4.32; it is not supported as shipped or verified. Required changes: explicit engine/version policy, compatible collations/JSON/constraint syntax, engine-aware error/introspection/upgrade/backup behavior and real engine-specific integrity/concurrency tests. MariaDB 10.4 maintenance ended June 2024, so supported production runtime selection remains unresolved if using that local version. See [database-compatibility.md](database-compatibility.md) with primary sources and precise plan. PdoFactory has not been relaxed or changed in Stage 3.

No remaining material Stage 3 blocker. Database connection/schema/constraints remain **pending**; actual server version must be established separately from installed client version. Production host/PHP/Apache and later release gates remain unverified. Stage 4 has not started. Next requested stage would be Stage 4 after documenting its engine choice; stop after Stage 3.

## Compatibility interlude before Stage 4 (current)

- [x] Explicit MariaDB 10.4.32 local support, actual engine probes, separate pending MySQL evidence and setup/gap updates. Stage 4 remains unchecked.

Implemented explicit DB_TARGET policy, DatabasePlatform engine/session/collation/JSON/error support, CanonicalJson, shared-factory detection and database checker updates. Added isolated XAMPP local initialization/start/stop/account setup and policy/real-database tests. Updated .env.example, Composer scripts, README, AGENTS, architecture, decisions, compatibility support matrix/evidence and GAP-09/GAP-10. API/auth/stock design and business routes are unchanged; no domain schema/migrations/seeds added.

Commands use portable PHP 8.5.11 and C:/ProgramData/ComposerSetup/bin/composer.phar:

| Command / check | Actual result |
|---|---|
| php bin/init-local-mariadb.php; PowerShell local-mariadb.ps1 start; php bin/configure-local-mariadb.php | Installed XAMPP engine started on loopback 13307, private random account and empty local database configured; existing XAMPP data/config/services untouched |
| composer validate --strict | PASS |
| composer check | PASS: 40 PHP files linted, 121 actual HTTP/config/foundation assertions and 14 engine/environment/canonical JSON policy assertions |
| composer check:database | PASS on actual 10.4.32-MariaDB; local scope, UTC/session policy verified; no schema readiness claim |
| composer test:database | PASS: 40 real MariaDB connection/constraint/transaction assertions, including exact roles/types, JSON/decimal/Unicode, bounds, FK/unique checks, rollback and independent-process locking/competing OUT/timeout |
| local-mariadb.ps1 stop then start; repeat checker and database probes | PASS: graceful stop/restart, same engine/policy and all 40 probes pass |
| Private .tools/inspect-db.php through shared factory | Remaining local tables 0; native AIO 0; innodb_flush_log_at_trx_commit 1 (durability retained) |
| git check-ignore .env .tools/mariadb/credentials.json .tools/mariadb/data/ibdata1 var/db-worker.log | All private/generated paths ignored |
| Blueprint SHA-256 | Unchanged: 84C8857AB8F09A8E269266DF12CA82F8103661B5F113C18058FEEFBBB86B1967 |
| PowerShell documentation/checklist/gap audit; php bin/setup.php with private configuration hash comparison | PASS: local links resolve, 16 numbered stages with only 1–3 checked, all 17 gaps open; setup rerun preserved private configuration |

Initial invalid-type probe used an overlong token and correctly hit range rejection first; repaired to test CHECK and range independently. The first local instance subsequently hung in native asynchronous I/O. Its forced stop damaged disposable system tables; preserved that workspace data directory and initialized a new empty instance with innodb-use-native-aio=0. Successful repeated probes and graceful restart used that configuration. This is documented local recovery evidence, not a production recovery recipe or a bypass of integrity rules.

MariaDB local compatibility is verified within this capability scope. Actual MySQL 8.4 connection/DDL/transaction acceptance remains **pending**. Domain migrations/constraints, application races, authorization, backup/restore and production host/deployment acceptance remain pending for their owning stages. All 17 release gaps remain open. Stage 4 is unchecked and has not started. The isolated local server is available at 127.0.0.1:13307; private credentials remain ignored. Stop after this compatibility work.

## Stage 4 outputs and verification (current)

Implemented three immutable SQL migrations, ten InnoDB tables, required named CHECK/unique/RESTRICT/self-FK constraints and access indexes; MigrationRunner with SHA-256 tracking, advisory serialization, safe rerun and incomplete/unmanaged-installation refusal; PasswordPolicy and transactional secure initial Admin bootstrap; development-only seed fixtures/loader/private random credentials; read-only snapshot stock reconciliation and corresponding CLI commands. Added tests/integration/stage4.php, Composer commands and docs/schema.md. Updated README, decisions, architecture, compatibility support matrix and gap audit. No Stage 5 contracts/client/business routes, Stage 6 authentication or Stage 8 stock service implemented.

All PHP commands use .tools/php/php.exe (8.5.11). Composer is C:/ProgramData/ComposerSetup/bin/composer.phar; the active server is the previously verified XAMPP 10.4.32-MariaDB loopback instance on 13307.

| Exact command / check | Actual result |
|---|---|
| php bin/check-database.php | PASS: real 10.4.32-MariaDB engine/version/local/session policy |
| php bin/migrate.php (fresh), same command again | First applied 001_domain.sql, 002_support.sql, 003_access_indexes.sql; second all already applied, no resets/duplication |
| php bin/seed-development.php, same command again | Three roles with private random hashes, one category/supplier, three products, three movements/replay rows and safe seed audit committed; second no-op, no password rotation |
| php bin/reconcile-stock.php | PASS/exit 0: 3 products, zero discrepancies; balances 17/10/0 |
| composer test:schema / php tests/integration/stage4.php | PASS: 112 real MariaDB assertions; disposable databases created/dropped by test only; configured local database preserved |
| Schema fresh/rerun/upgrade tests | 001 populated with existing user/hash/lookups/product/opening history, then 002/003 applied; every original row preserved. Complete rerun preserved all rows/checksums/timestamps. Changed checksum, incomplete history and unmanaged baseline refused |
| Constraints/index tests | Canonical identity/uniqueness; exact roles/types; boolean/session/version; nonnegative stock/price/threshold and positive movement; overflow; FK parent/history/self-reference RESTRICT; unique reversal and actor/key; replay/audit JSON/completion/status; hashed limiter/window bounds; ten InnoDB tables/single sentinel; real per-product history EXPLAIN index eligibility |
| Admin tests including actual stdin CLI | Strong bounded hash/verify, forced-change/session fields, safe audit, audit-insert failure rolls back account; existing account and rerun safely refused without credential output or mutation |
| Seed/reconciliation tests | Random hashes for all roles, atomic credential-delivery failure rollback, matching opening/OUT/replay balances, no-effect rerun, existing-data and production service/CLI refusal; archived/no-movement inclusion; injected disposable drift exit 1/read-only, clean exit 0 |
| composer test:database / php tests/database-compatibility.php | PASS: 40 real capability assertions; only random probe tables removed, application data preserved |
| composer validate --strict; composer check | PASS: valid manifest; 52 PHP files linted; 121 foundation HTTP/config assertions and 14 engine/canonical JSON policy assertions |
| Private .tools/verify-stage4-state.php | PASS: main row/credential-file hashes preserved on migration/seed rerun, all three development passwords verify, 3 balanced products and no disposable Stage 4 databases remain |
| PowerShell parser/documentation/checklist/gap audit; git check-ignore | PASS: hidden-input Admin wrapper syntax; local links resolve; only stages 1–4 checked, all 17 gaps open; private development credentials/.env/database credentials ignored |
| Blueprint SHA-256 | Unchanged: 84C8857AB8F09A8E269266DF12CA82F8103661B5F113C18058FEEFBBB86B1967 |

Evidence is in stage4-results.json and database-compatibility-results.json, with separate target scope; no MySQL server was tested. Main local schema and development seeds remain available. Existing XAMPP data/config/services were untouched. No migration file was altered after application and no reset was performed. Credentials are ignored and outside public/.

No material Stage 4 local blocker remains. MySQL 8.4 connection/migration/upgrade/constraints/bootstrap/seeds/reconciliation acceptance is **pending until run there**; production host and later release checks also remain pending. Later business service/authorization integrity remains in owning stages; all 17 release gaps remain open. Stage 4 is complete on the authorized local target, Stage 5 remains unchecked. Stop after Stage 4.

## Stage 5 outputs and verification (current)

Implemented Http/ApiRouter (versioned exact/positive integer parameter routes, method allowlists), shared Response::success and HttpException/ExceptionHandler safe details/header mapping; Support/ApiInput JSON/body/query/text/numeric/enum/date/pagination/sort/key validation; browser API client plus shared-layout base-path metadata/script and explicit development asset allowlist. OpenAPI/generator documents all planned features with implementation status, roles, cookie/CSRF, idempotency, field visibility, bounds, UTC/decimal/wide values, report filters/CSV and typed error contracts. Added docs/api-foundation.md, PHP/API/HTTP/browser-client/live-browser/OpenAPI contract checks and Composer test:api. Updated README, architecture, decisions and gaps. No auth/session/CSRF middleware, business route success, database writes or schema migration added.

Only GET/HEAD /api/v1/health is live. Its minimal success remains unchanged; no query fields are accepted under the new explicit input contract. All 30 planned operations in openapi.yaml remain unimplemented and return 404: auth CSRF/login/logout/me, account password, dashboard, products and detail/create/edit/archive, category/supplier lists and writes, transactions/detail/create/reversal, inventory/transaction reports, users/account administration/reset and audit. No transaction update/delete/public signup route exists. Actual role enforcement remains Stage 6 and later feature stages, not inferred from contract metadata.

| Command / check | Actual result |
|---|---|
| PHP 8.5.11 Composer validate --strict | PASS |
| Same Composer check | PASS: 55 PHP files linted; 139 real HTTP/config/foundation assertions, 14 engine/canonical JSON assertions and 64 API helper/router/envelope assertions |
| php tests/unit/api.php / composer test:api | PASS: JSON object/depth/size/type/duplicate-key/field parsing, UTF-8/query/percent/control guards, integer/decimal/enum/boolean/date ranges, page/sort bounds, route params, planned-route 404, health and typed details/method errors |
| HTTP foundation checks at root, /bizstock and nested base | PASS: malformed/repeated/array/unknown/oversize queries, correlated request ID body/header, health GET/HEAD/405, client asset delivery, isolation and production preview/config guards |
| node --check public/assets/js/api-client.js; node --check bin/build-openapi.cjs | PASS |
| node tests/api-client.cjs | PASS: explicit transport fixtures cover cookie/base path, CSRF bootstrap/cache/late stale token rejection, 401 event, CSRF clearing, operation-key retention/no automatic retry, JSON/non-JSON/network/abort errors, HEAD and CSV. These mocks are not backend auth/CSRF acceptance |
| node tests/browser/api-live.cjs | PASS on real installed Chrome/loopback PHP: client loads, /bizstock health/request IDs work, malformed query errors correlate, planned product and unimplemented CSRF routes return 404, no page errors |
| node bin/build-openapi.cjs; node tests/openapi.cjs | PASS: deterministic JSON-form YAML artifact; 32 operation contracts, 2 implemented/30 planned, 562 resolved local references, permission/CSRF/idempotency/CSV/field guards. Focused structural audit only, not full independent schema-validator certification |
| Private .tools/verify-stage4-state.php | PASS: all local data/credential-file hashes preserved, three development passwords still verify, stock 17/10/0 balances and no disposable DB leftovers |
| Blueprint SHA-256 | Unchanged: 84C8857AB8F09A8E269266DF12CA82F8103661B5F113C18058FEEFBBB86B1967 |

Repaired test assumptions for health's newly strict query contract and the exact operation count. Real-browser verification found the missing development-router asset entry; added it and browser checks passed. No database migration or credential changes were required.

No material Stage 5 blocker remains. Authentication/actual CSRF/session/role enforcement, feature endpoints and domain invariants are pending in their owning stages; all 17 release gaps remain open. MySQL 8.4 production verification remains pending, independently from existing MariaDB evidence. Stage 5 is checked, Stage 6 and later are unchecked. Stop after Stage 5.

## Stage 6 outputs and verified results

Implemented five real authentication/account APIs, private strict cookie sessions, idle/absolute expiry, session/CSRF rotation, generic credential failure, persistent login/password throttling and current active/role/version checks. Password changes lock/recheck accounts and atomically audit without secrets; retained session keeps its absolute deadline and other sessions are invalidated. Forced-change access is restricted. Real /login and /account use shared UI/client, escaped database identity, busy/error/focus handling and sign-out. Root links to those pages.

Reusable policy guards all 25 planned business operations; allowed requests still receive 404 and denied requests 401/403. Viewer sensitive query and Staff correction attempts are denied; positive user/product/category serializers drop hidden/nested extras. Actual business response/filter parity remains in feature stages. No public signup, production fixture persistence, browser token storage, stock writes, new migration or applied migration modification.

Commands use .tools/php/php.exe; Composer path C:/ProgramData/ComposerSetup/bin/composer.phar:

| Command / check | Actual result |
|---|---|
| composer validate --strict | PASS |
| composer check | PASS: 64 PHP syntax checks, 139 HTTP/config foundation assertions, 14 database policy assertions, 64 API foundation assertions |
| composer test:auth / php tests/integration/stage6.php | PASS: 334 assertions against actual 10.4.32-MariaDB in a newly migrated disposable database; shared auth input validation, all anonymous/Admin/Staff/Viewer planned-route permissions, CSRF, wrong/missing/inactive users, forced change/logout, cookie fixation/rotation, expiry, current role/deactivation/session version, password/audit rollback, persistent multi-worker account/peer/password throttles and expired-window reset |
| Installed Chrome tests invoked by test:auth | PASS: 25 real login/account/password/logout assertions; six clean accessibility scans at 360/768/1440px, no horizontal clipping/page errors or injected script; no localStorage/sessionStorage tokens |
| node bin/build-openapi.cjs; node tests/openapi.cjs | PASS: 32 operation contracts, 7 implemented/25 planned, 547 resolved references; future-handler security status explicit |
| node tests/api-client.cjs; node tests/browser/api-live.cjs | PASS: transport regression fixtures plus real browser health/validation/CSRF bootstrap and anonymous refusals |
| php bin/check-database.php | PASS: verified MariaDB 10.4.32 local connection, strict/UTC session, target enforcement; no MySQL claim |
| Private .tools/verify-stage4-state.php | PASS: installed database/credential hashes preserved on safe rerun, all development passwords verify, stock balances 17/10/0, no disposable DB leftovers |

Production cookie options and refusal of direct HTTP are tested separately without connecting to MySQL. Actual MySQL 8.4 execution and production TLS/proxy/hosting acceptance remain pending. Existing installation data and credentials are preserved; tests use private administrator credentials only in disposable loopback databases. No material Stage 6 blocker remains. Release gaps stay open where later handlers, recovery, host/MySQL or full release proof are still required. Stopped after Stage 6; Stage 7 has not started.

## Stage 7 outputs and verified results

Implemented eight real category/supplier operations and /categories, /suppliers management screens. Admin creates/partially edits/deletes with CSRF and current-role/version recheck; Staff reads and Viewer receives category ID/name only with supplier API/page denial. Names/optional email validate through shared helpers; unique category conflicts map safely to 409. Parent locks, all-product reference checks and existing RESTRICT FKs prevent orphaning, including archived references. Every successful mutation commits safe before/after actor/request-ID audit atomically; audit failures roll back create/edit/delete. Shared fields/buttons/states/table/dialog/client support search, trusted sorting, pagination, busy/errors, confirmation, session handling and database persistence after reload. No migration change, stock/history writer or Stage 8 implementation.

Commands use .tools/php/php.exe and C:/ProgramData/ComposerSetup/bin/composer.phar:

| Command / check | Actual result |
|---|---|
| composer validate --strict; composer check | PASS: valid manifest; 70 PHP syntax checks, 139 HTTP/config foundation assertions, 14 engine policy assertions, 64 API foundation assertions |
| composer test:lookups / php tests/integration/stage7.php | PASS: 84 actual MariaDB 10.4.32 assertions in a disposable migrated database: Admin CRUD, Staff/Viewer/anonymous permissions, CSRF, trim/bounds/unknown fields, duplicate create/edit, invalid/optional email, partial PATCH, literal/injection-safe search, stable bounded pagination, active/archived references, missing IDs, audit values/request IDs/failed-mutation silence and create/edit/delete rollback including supplier rollback |
| Installed Chrome lookup tests invoked by test:lookups | PASS: 35 assertions, six clean accessibility scans at 360/768/1440px; both entity create/edit/delete and reload persistence, pagination, reference/duplicate errors, focus/dialog, actual loading/network-error/empty/retry states, Staff read-only and Viewer supplier navigation/direct-page denial |
| composer test:auth / php tests/integration/stage6.php | PASS: 334 authentication/session/CSRF/role assertions and 25 Chrome auth assertions with six clean scans; lookup matrix expectations now reflect real handlers, all other business handlers stay planned |
| node bin/build-openapi.cjs; node tests/openapi.cjs | PASS: 32 contracts, 15 implemented/17 planned, 566 local references; partial lookup schemas, query roles and typed conflicts documented |
| node tests/api-client.cjs; node tests/browser/api-live.cjs | PASS: client transport plus real Chrome health/base path/query/CSRF/anonymous regression checks |
| Private .tools/verify-stage4-state.php | PASS: installed data/credential hashes preserved, all development passwords still verify, stock 17/10/0 balances unchanged; no leftover disposable databases after completed test runs |
| Blueprint/migration checks | Blueprint SHA-256 unchanged; safe tracked migration rerun confirms applied checksums unchanged |

No material Stage 7 blocker remains. Existing installation data is preserved; tests mutate only their own random database and remove it. Admin audit browsing and product reassignment remain their later stages; lookup deletion correctly refuses referenced parents now. MySQL 8.4 execution and production HTTPS verification remain pending independently from local MariaDB. All 17 release gaps remain open for later obligations. Stopped after Stage 7; Stage 8 has not started.

## Stage 8 acceptance

`php tests/integration/stage8.php`: PASS 33 actual MariaDB checks, separate-process oversell/retry/reversal/archive protocol races, actual InnoDB deadlock retry and bounded lock timeout. `php tests/integration/stage4.php`: PASS 112 regressions after replacing seed stock writes with the shared writer. See stock-integrity.md. No migration changed; installed data preserved. MySQL 8.4 execution remains pending.

## Stage 9 acceptance

`php tests/integration/stage9.php`: PASS 40 database/HTTP assertions plus 15 Chrome assertions and three clean axe form scans. Product create/opening/audit/replay commit atomically; real concurrent SKU/create/edit/archive races pass. Viewer positive fields and sensitive filters are enforced in API. Persistence and metadata edits verified after reload. Shared forms, tables, states, dialogs, pagination and client power live catalogue pages. `node tests/openapi.cjs`: PASS 32 operations, 23 implemented, 566 references. Asset allowlist and live error-state link defects found by browser checks were fixed before proceeding. MySQL and production HTTPS remain pending.

## Stage 10 acceptance

`php tests/integration/stage10.php`: PASS 24 browser assertions and four database assertions. A response is deliberately lost after a real commit; retry retains the same key and creates no duplicate. Double confirmation commits once; a competing OUT makes displayed stock stale, then the server rejects the oversized request and the UI refreshes availability. Admin full reversal links both history records; Staff has no reversal controls. Archived/inactive history and reconciliation pass. Six axe scans (movement/history at 360/768/1440) are clean. No edit/delete history handlers or controls exist. Core Stage 8 checks remain the stock acceptance baseline.

## Stage 11 acceptance

`php tests/integration/stage11.php`: PASS 13 MariaDB assertions plus 16 Chrome assertions and three clean axe scans. Read-only repeatable-read snapshot keeps counts/alert rows consistent during a real separate-connection commit. Zero/equality/archive, total units beyond INT range, role projections, matching catalogue filters and fresh post-mutation snapshots pass. Browser confirms manual refresh, revisit, matching alert links and failure/recovery. Dashboard shows up to 100 alerts and latest 10 authorized movements; counts cover all active products. No polling; page visits/BFCache restores/manual refresh and in-page stock-change events refresh data. MySQL/HTTPS remain pending.

## Stage 12 acceptance and consolidated Stages 8–12 audit

Implemented ReportService/ReportController and shared report screens, filtering, snapshot timestamp, exact gross captured-price reference summary, CSV/download and print styles. Inventory snapshots include current active products only; history retains archived/inactive references. Report dates use Manila inclusive dates converted to UTC half-open intervals. CSV includes all matching pages, formula-safe quoted text, at most 10,000 rows/16 MiB. Exact 10,000 passes and 10,001 returns JSON 422. No source migration changed, existing installation data preserved, and all live screens use shared components/client without fixture data.

All commands used PHP 8.5.11 via `.tools/php/php.exe`; Composer invocation was `& .tools/php/php.exe C:/ProgramData/ComposerSetup/bin/composer.phar`.

| Verification command | Final actual result |
|---|---|
| `composer validate --strict`; `composer check` | PASS: valid manifest, 104 PHP syntax checks, 139 foundation assertions, 14 engine policy assertions, 64 API assertions |
| `composer test:business` | PASS all sequential Stage 8–12 and complete-flow suites; subsequent final audit expanded stock/product assertions as below |
| `php tests/integration/stage8.php` | PASS final 37 MariaDB assertions: oversell/rollback/replay/reversal/archive protocol races, real InnoDB deadlock retry, three actual lock timeouts, stale role/active/forced/session rechecks |
| `php tests/integration/stage9.php` | PASS final 43 database/HTTP assertions and 16 Chrome assertions; three clean form axe scans, mobile quantity summary, SKU trim/case and archived identity retention, zero-history SKU metadata edit, concurrent create/edit/archive and opening/audit rollback |
| `php tests/integration/stage10.php` | PASS four database assertions and 24 Chrome assertions; six clean axe scans; response lost after commit, unchanged retry key, double confirmation, stale availability and Admin reversal/link flow |
| `php tests/integration/stage11.php` | PASS 13 database assertions and 16 Chrome assertions; three clean axe scans; snapshot consistency during separate-process commit, threshold/zero/archive/filter parity, wide totals and freshness/failure recovery |
| `php tests/integration/stage12.php` | PASS 41 database/export assertions and 30 Chrome assertions; six clean axe scans; captured prices, Manila midnight, archived/inactive history, JSON/screen/export parity, quotes/multiline/formula defense, row/byte caps and Viewer denial |
| `php tests/integration/flow.php` | PASS seven database assertions and 22 browser/contract assertions; lost-response idempotent product/opening creation, Staff OUT, equality alert, price edit, Staff IN, Admin original-price reversal, zero OUT, CSV, UI archive and preserved history |
| `php tests/integration/stage4.php` | PASS 112 fresh migration/rerun/upgrade/constraints/bootstrap/seed/reconciliation regressions; existing migrations unchanged |
| `php tests/integration/stage6.php` | PASS 334 authentication/session/CSRF/role assertions and 25 Chrome assertions/six clean axe scans; permission matrix now checks implemented handler statuses |
| `php tests/integration/stage7.php` | PASS 84 lookup/audit/reference/permission assertions and 35 Chrome assertions/six clean axe scans; shared live navigation verified |
| `php tests/database-compatibility.php` | PASS 40 actual MariaDB connection/constraint/transaction probes; probe tables cleaned |
| `node bin/build-openapi.cjs`; `node tests/openapi.cjs` | PASS 32 operation contracts: 26 implemented, six explicitly planned; 566 resolved references. Actual complete-flow responses also checked against the documented schema subset |
| `node tests/api-client.cjs`; `node tests/browser/api-live.cjs` | PASS transport/CSRF/session/key/CSV/error behavior and actual Chrome client health/base-path/query/anonymous refusals |
| `php bin/reconcile-stock.php`; private `.tools/verify-stage4-state.php` | PASS main installation: three balanced products, zero drift; data/credential hashes unchanged on safe rerun, passwords verify, no disposable databases left. Existing balances remain 17, 10 and 0 |
| Blueprint SHA-256 | Unchanged: `84C8857AB8F09A8E269266DF12CA82F8103661B5F113C18058FEEFBBB86B1967` |

Final Stage 8–12 plus complete-flow totals: **145 business/database assertions and 108 browser assertions**, with **18 clean axe scans**. Counts include test-harness login/browser checks, not 145 distinct business scenarios. Earlier stage regression counts are separate. Subsequent focused reruns confirmed the final mobile summary, UI archive and revoked-actor/SKU cases; they supersede the initial checkpoint counts above.

No material local Stage 8–12 blocker remains. MySQL 8.4 connection/SQL/concurrency/browser execution and production HTTPS remain **pending**. MariaDB 10.4.32 remains local-only, rejected in production configuration. User management/reset/recovery/audit browsing, participant feedback, broader accessibility/visual/CI/release/restore evidence and deployment remain their owning Stages 13–16; none is marked tested or implemented here. No deployment or production credential use occurred. **Stopped after Stage 12.**

Final documentation/isolation audit: `node .tools/verify-stage12-docs.cjs` passes all 16 stage flags (only 1–12 complete), 17 structured gap rows, 45 local documentation links and the original blueprint hash. All 26 JavaScript/CJS sources pass `node --check`. `git check-ignore` confirms .env, development credentials, runtime logs and private tools stay ignored. Final main-database preservation/reconciliation rerun passes after disposable test cleanup. No unresolved local Stage 8–12 failure remains.

## Independent review before Stage 13

Read the blueprint/master instruction, requirements, actual implementation/tests, OpenAPI, progress and gaps. [The review report](stages-8-12-review.md) provides **PASS / NOT VERIFIED** verdicts for every requested area with specific code/test evidence. Confirmed live header sorting, numeric alignment and stale product/original selection defects were repaired and regression-tested. Architecture/decision descriptions and OpenAPI conflict/export/reference error documentation now match code. No business API behavior, schema or applied migration changed.

Current execution using `.tools/php/php.exe` (8.5.11): `composer validate --strict` and `composer check` PASS (104 syntax, 139 foundation, 14 policy, 64 API). Final `composer test:business` PASS: Stage 8 **49** database/concurrency/HTTP assertions; Stage 9 **43 DB / 21 Chrome**; Stage 10 **4 DB / 27 Chrome**; Stage 11 **13 DB / 16 Chrome**; Stage 12 **43 DB/export / 30 Chrome**; complete flow **7 DB / 22 Chrome/schema**. Total **159 database/harness and 116 Chrome assertions**, with **18 clean business axe scans**. Counts include harness checks and exclude repeated focused reruns.

`php tests/integration/stage6.php` PASS **334** auth/security assertions and **25** Chrome/six axe scans. `php tests/integration/stage4.php` PASS **112** schema/upgrade/constraint/seed regressions. `php tests/database-compatibility.php` PASS **40** MariaDB capability checks. `node bin/build-openapi.cjs` / `node tests/openapi.cjs` PASS **32 operations, 26 implemented/six planned, 566 references**. Client transport and actual Chrome client checks PASS. JavaScript sources pass `node --check`; changed PHP tests/harness pass syntax checks. Documentation checker passes 16 unchanged stage flags, 17 structured release gaps, local links and original blueprint hash.

One intermediate business run stopped at a browser subprocess nonzero exit with no stderr; its cause was not established. The standalone movement rerun and final complete sequential run passed. Harness diagnostics now include exit code/script; the interruption is recorded in the review rather than erased or reported as a pass.

`php bin/reconcile-stock.php` ran before and after: **three installed products / zero drift**. A private read-only SHA-256 baseline of **all ten installed tables and development credential file** matched after all testing. Safe migration/seed rerun and password verification also PASS; balances remain **17/10/0**, with no rotation/reset. Secrets/private hashes/tools remain ignored. Tests used disposable migrated databases and isolated accounts, not existing inventory/credentials.

No remaining confirmed local Stage 8–12 defect. **MySQL 8.4 and production HTTPS remain NOT VERIFIED/pending**, separately from local MariaDB. Full Stage 14 accessibility/participant work and later operational/release evidence remain pending. No Stage 13 implementation or deployment; stopped after review.

## Stage 13 acceptance

Implemented real Admin accounts/reset/audit APIs and shared screens, sentinel-first last-Admin serialization, safe atomic audit and stdin emergency recovery. `php tests/integration/stage13.php` PASS 37 DB/API and 16 Chrome assertions/six axe scans. `php tests/integration/stage6.php` PASS 334 security and 25 Chrome assertions/six axe scans. `node tests/openapi.cjs` PASS all 32 operations implemented, 566 references. No migration, installed account or credential changed. MySQL 8.4/HTTPS remain pending. See [Admin management](admin-management.md). Stage 14 may proceed.

## Stages 14-15 acceptance and final Stage 13 regression

Final Stage 13: PASS 37 DB/API/CLI assertions, 21 Chrome assertions and seven clean axe scans. Stage 14: PASS 11 DB/API and 180 browser assertions, 63 clean axe scans and 105 screenshots at five viewport widths. Repaired live table SKU wrapping and shared form spacing; reviewed actual screenshots. Participant scripts and the 33-requirement evidence audit are documented; actual participant feedback and broader manual assistive-technology acceptance remain pending.

Stage 15: PASS 22 native MariaDB backup/restore/grant assertions and eight clean copied-release installation assertions. Exact ten-table restoration, password hashes, migration rerun, stock balance, runtime grant restrictions and clean empty-database install were verified using isolated targets. Installation/upgrade, production configuration, backup retention, restore/rollback and recovery runbooks, user guide and a manual self-hosted CI workflow are provided. Hosted CI has not run; hosting/domain/credentials and production operations are not provisioned.

Final affected regressions: PASS 159 business/database harness and 116 Chrome assertions with 18 clean axe scans; auth 334 plus 25 Chrome/six axe; schema 112; lookups 84 plus 35 Chrome/six axe. Composer manifest/check passes 118 PHP syntax, 139 foundation, 14 policy and 64 API assertions. OpenAPI has all 32 operations implemented and 566 resolved references; shared client transport and JavaScript syntax checks pass. See [test evidence](test-plan.md).

Main reconciliation passed before/after testing: three products, zero drift, balances 17/10/0. Private hashes of all ten installed tables and credential file are unchanged. Applied migrations and blueprint remain unchanged. MySQL 8.4 and production HTTPS verification remain pending separately from local MariaDB. No deployment/purchase; stopped after Stage 15. Stage 16 not started.
