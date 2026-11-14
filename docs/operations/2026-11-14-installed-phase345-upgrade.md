# Installed local Phase 3–5 upgrade — 2026-11-14

**Completed: migrations 005-007 applied; final preservation checks passed with the owner's confirmed sign-in retained; application running at http://127.0.0.1:8080.** The temporary stop and original comparison failure below are retained as execution history, not the final state.

The owner authorized this local upgrade after establishing future documentation-placement rules in the root AGENTS.md. Existing uncommitted implementation work is preserved. The [existing reviewed procedure](../phase345-upgrade.md) remains in place; no broad documentation reorganization is included.

See [sanitized evidence](../evidence/2026-11-14-phase345-upgrade.json). Database dumps, configuration, credentials and raw baselines remain in private untracked storage outside documentation/public. No password change, seed, business fixture against the installation, unrelated repair, deployment or Phase 6 implementation occurred.

## Target and isolation

Installed target: `bizstock_local`, `127.0.0.1:13307`, real MariaDB 10.4.32, explicit `mariadb104-local`; selected PHP 8.5.11 and UTC database session. MySQL 8.4 was not tested.

The verified workspace PHP server PID 22756 was stopped before backup; port 8080 had no listener. The other PHP process was the VS Code language server. No BizStock workers were identified. All 208 scheduled-task actions were inspected with no BizStock/selected-runtime match. At the final migration gate, the selected database server had no other client connections and no enabled database events. Unrelated XAMPP Apache and the port-3306 database were preserved; Apache configuration/roots did not point to BizStock.

Before migration, exactly 001-004 were applied and 005-007 pending. All applied/source checksums matched. Original DDL matched a fresh isolated 004 schema, normalizing only the current AUTO_INCREMENT table counter; no unexpected schema drift was found. All 229 captured existing application/tool-input files remain unchanged.

## Verified coordinated backup

Private backup: `C:\Users\reina\Bizstock\var\backups\installed-phase345-20261114-154414`.

Windows ACL inheritance is disabled; access is restricted to the creating local operator identity, SYSTEM and Administrators. The directory is untracked and outside public/documentation. Do not publish its contents.

- The existing DatabaseBackup implementation and native XAMPP mysqldump produced `database.sql` while application writes were stopped. Credentials were not passed in command arguments or printed.
- Sixteen protected files were copied and individually SHA-256 checked: required `.env`/application/server configuration, private credential files, migration inputs and all four private-upload-tree files. Upload-tree counts include support/placeholder files; they do not claim four product images.
- The initial manifest covers 18 payload files, 151,626 bytes: database dump, private baseline and sixteen protected files. Manifest SHA-256, every payload length and every payload SHA-256 passed. The original manifest still passes after the final stop. Raw row/credential digests remain private.
- All ten installed tables passed CHECK TABLE. Native restore into a new empty randomized database passed ten-table integrity and original schema/record/credential/stock comparisons. All sixteen copied files were restored separately and hash checked.
- The populated restored database upgraded through 005-007, matched a fresh isolated 007 schema, passed twelve-table integrity, original-field/credential/stock checks, documented defaults/NULL additions and empty count-table checks. An isolated rerun correctly reported all seven already applied.
- Both randomized rehearsal databases were initially retained for diagnosis, then removed only after owner confirmation and successful final preservation checks. Private backups/diagnostics remain. No installed database was reset, recreated, seeded or restored.

## Applied migrations and installed checks

The final gate rechecked backup integrity, protected files, original schema/records/credentials/stock, reviewed SQL checksums and exclusive database clients/events. It passed before running:

```powershell
& .\.tools\php\php.exe bin/migrate.php
& .\.tools\php\php.exe bin/check-schema.php
& .\.tools\php\php.exe bin/check-database.php
& .\.tools\php\php.exe bin/reconcile-stock.php
```

Each exited zero. The runner reported 001-004 already applied and applied only `005_edit_and_movement_context.sql`, `006_integer_units.sql`, `007_physical_counts.sql`. They add edit/context/login fields, integer-unit/stock-revision fields and empty physical-count tables. Schema result: seven applied, none pending, ready=true, twelve InnoDB tables. Connection: MariaDB 10.4.32, explicit local-only target, UTC. Reconciliation: three products, no discrepancies.

Before restart, every original field and record across all ten original tables matched. Comparisons projected original columns after additions and selected the four original migration rows; whole-table hashes after adding columns were not used. Original migration rows/checksums, credential/security fields, triggers, configuration/uploads and historical movements/stock matched. AUTO_INCREMENT counters did not move backwards. Full DDL matched the rehearsed schema; additive defaults/NULLs were correct, both count tables empty and twelve installed CHECK TABLE results OK.

Original row counts: audit_events 17; categories 1; operation_requests 3; products 3; rate_limits 10; schema_migrations 4 (now 7 applied); security_locks 1; suppliers 1; transactions 3; users 5.

## Restart, smoke and final stop

After preservation passed, selected PHP restarted hidden on `127.0.0.1:8080`, PID 29692; its listener was verified. Ten GET-only HTTP checks passed: home/login/health 200; dashboard/analytics/count pages 303; auth-me/products/analytics/count APIs 401. Actual headless Chrome displayed the login form at 360/768/1440 without horizontal overflow or JavaScript errors and redirected an anonymous count-page request to login. The smoke helper submitted no login/password action/business write. Installed authenticated feature acceptance was not attempted because a new login changes account metadata; role/workflow tests used disposable databases.

The additional post-smoke all-original-fields comparison returned exit 2: `Original records/fields differ: rate_limits`. Dependent work and rehearsal cleanup stopped. The verified server was stopped again and port 8080 confirmed without a listener. The successful earlier restart is not the final application state.

Read-only diagnostics found nine original tables still match all original fields/records, including five users' original credential/security fields and all business/history/audit/original migration records. Ten rate-limit rows remain, but two entries changed window/reset timestamps and attempts. One new `last_successful_login_at` is populated, although all were NULL at the successful pre-restart gate: `2026-11-14 07:53:42.867519 UTC` (15:53:42.867519 Manila). This is newer authentication activity, not a migration default; its source is unconfirmed. All sixteen protected file hashes still match and three-product reconciliation has no discrepancies.

Preserved private `post-smoke-diagnostic-private.json`, a separate native `post-smoke-current-database.sql` with recorded SHA-256, server logs, original backup/baseline/manifest and rehearsal targets. The new dump is not silently included in the original manifest. No older database was restored over newer activity; no rate-limit/login values were edited, and installed DDL was not rerun. This follows the runbook stop/preserve/inspect and separate-empty-target recovery policy. Attribution/acceptance and reviewed continuation are needed before resuming; final all-record preservation remains unresolved.

## Owner-confirmed activity and successful continuation

The owner subsequently confirmed: **"Yes, I signed in"** while the server was briefly running. The two rate-limit changes and successful-login timestamp are retained as owner activity, not migration changes. No authentication data was reverted, and the original failed exact comparison is not relabeled as passed.

Before restarting, a read-only accepted-activity gate verified the current rate-limit rows and successful-login timestamp still exactly matched the preserved diagnostic snapshot. All other original fields/records, original user credential/security fields, original migration rows/checksums, stock/history, triggers and all sixteen protected-file hashes matched. Full DDL matched the rehearsed schema, documented non-login additive defaults/empty count tables were correct, the original backup manifest still passed and all twelve tables passed integrity. Thus final preservation passed with an explicit, owner-attributed exception for newer authentication activity. No mutation was needed to resolve the hold.

Only the two operation-owned randomized rehearsal schemas were then removed. The original coordinated backup, separate post-sign-in current-state dump, manifests, diagnostic evidence and logs remain private and retained.

The selected PHP server restarted hidden again on `127.0.0.1:8080`, final PID 21300; the listener was verified. All ten GET-only HTTP checks and actual Chrome login/three-width/no-overflow/no-JavaScript-error/private-page-redirect checks passed again without a helper login or business write. The schema, database and stock CLIs again exited zero: seven applied/no pending/ready, MariaDB local-only/UTC, three products/no discrepancies. The application is running; the authentication-attribution/restart hold is resolved. Existing MySQL and other stated verification limitations remain.

## Separately executed disposable tests

Executed during this operation using selected PHP 8.5.11, randomized disposable MariaDB databases and fixture HTTP servers:

| Command | Observed result |
|---|---|
| selected PHP + Composer `validate --strict` | PASS |
| selected PHP + Composer `check` | PASS: 153 syntax files, 139 foundation, 14 engine-policy, 64 API assertions |
| selected PHP `tests/integration/phase3.php` | PASS: 40 integration/harness and 10 Chrome assertions |
| selected PHP `tests/integration/phase4.php` | PASS: 24 integration/harness and 8 Chrome assertions |
| selected PHP `tests/integration/phase5.php` | PASS: 40 integration/harness and 17 Chrome assertions |
| Native restore/fresh-schema comparison/rehearsed upgrade | PASS as detailed above; targets cleaned after successful owner-confirmed continuation |

No MySQL 8.4, production, hosted CI, large-volume, participant or manual assistive-technology verification is claimed. Earlier broader regression results are historical evidence, not reruns here.
