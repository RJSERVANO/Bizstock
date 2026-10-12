# Original local MariaDB recovery — 2026-10-12

Status: **RECOVERED: both authorized table repairs preserve records; all 40 tables pass; reversible local cutover verified.** No Admin password reset has been performed, following the owner's latest instruction to enter a new password privately.

## Final authorized proxies_priv repair and cutover

The owner separately approved `aria_chk --safe-recover --backup` without `--quick` on working-copy mysql.proxies_priv only. Process command-line inspection confirmed only the unrelated XAMPP instance was running; both recovery servers were stopped. Separate pre-repair .frm/.MAI/.MAD files and their hashes were retained in private `proxies-priv-before-safe-recover/`. Same-version utility command: `C:/xampp/mysql/bin/aria_chk.exe --no-defaults --datadir=<working-data> --require-control-file --safe-recover --backup <working-data>/mysql/proxies_priv.MAI`. Exit 0, four records. All 24 copied Aria tables then completed metadata normalization without errors.

Before cutover: **40/40 SQL CHECK TABLE PASS**; canonical rows of all 30 system tables and every SHOW GRANTS hash for all six database accounts exactly match the preserved reference, including all four proxies_priv records. All ten application tables remain readable; historical baseline row hashes and four exact migration records match. Stock reconciliation: three products, zero discrepancies, 17/10/0 units. Both uploads unchanged. No grants bypass, reinitialization, seeds or record deletion.

After clean shutdown, 121 recovered files were copied into `.tools/mariadb/recovered-data-20261012/`, with exact SHA-256 agreement. The original `.tools/mariadb/data/` was left untouched. The pre-cutover server configuration remains private under the recovery directory; `.tools/mariadb/server.ini` now points to the recovered directory on original loopback port 13307, with Aria automatic recovery disabled. The application .env and database credentials were not changed. Normal read/write mode is verified (`@@read_only=0`); actual application PDO/runtime engine policy passes as MariaDB 10.4.32, local development only.

Post-cutover: **40/40 SQL CHECK TABLE PASS** again; all system rows/grants and original application hashes/migrations/accounts match. `bin/check-database.php`, `bin/reconcile-stock.php` and upload preservation checks pass. Public home/login HTTP 200 and unauthenticated `/api/v1/auth/me` HTTP 401 pass. dev_admin is user_id 1, active Admin, original forced-change flag 0; dev_staff and dev_viewer remain active in their original roles with forced-change flags 1. No account, credential, session version or uploaded file was edited. All 167 offline-backup hashes and original database/credential/upload/application-config hashes remain intact. Active server configuration/logs are expected cutover/runtime changes; pre-cutover versions remain backed up.

`composer validate --strict` PASS; `composer check` PASS using PHP 8.5.11: 134 syntax, 139 foundation, 14 database-policy and 64 API assertions. No Admin login/logout/relogin or actual password-change completion is claimed: the owner will choose and enter a new password. MySQL 8.4, production HTTPS and hosted image-storage checks remain pending. Nothing deployed or published.

## Private Admin reset command

Run in a local PowerShell terminal you control, without a transcript/screen recording:

```powershell
Set-Location 'C:\Users\reina\Bizstock'
& .\bin\recover-admin.ps1 -UserId 1 -Reason 'Owner-requested local Admin recovery after verified database repair'
```

The existing wrapper uses `.tools/php/php.exe`, prompts twice with hidden input, and sends the password only through stdin to the existing recovery service. Use 12–72 UTF-8 bytes. Never place the password in a command argument, script, chat or log. This resets only existing dev_admin, records a safe recovery reason, revokes its prior sessions and sets required password change. No password from earlier conversation will be used.

Open **http://127.0.0.1:8080/login**, sign in as **dev_admin** using the password you entered. You must change it to a different password on the account page before accessing the dashboard. Then sign out and sign in using that final password. These owner-operated steps remain pending until performed. Initial development credentials are stored privately in ignored `var/development-credentials.json`; they do not update when passwords change and may already be stale. Existing Staff/Viewer passwords remain untouched.

## Reversible local configuration cutover runbook

Active data: `.tools/mariadb/recovered-data-20261012/`; original data: `.tools/mariadb/data/`; verified pre-cutover recovered checkpoint: private recovery `working-data/`; complete original offline backup: recovery `offline/`. Retain all privately. Start the local recovered server with the same MariaDB binary and `.tools/mariadb/server.ini` (hidden window for background use). Do not launch a second server against the same data directory.

To undo the configuration cutover, first freeze application writes, shut down only the recovered instance on 13307 through an authenticated private client configuration, and wait for its process to exit. Preserve its new data and logs. Restore the retained `pre-cutover-server.ini` to `.tools/mariadb/server.ini`. This reverses configuration only: the untouched original still has its original corruption and is **not a healthy fallback server**. To return to the verified recovered checkpoint instead, copy its stopped `working-data/` into another new private directory, configure 13307 to use it, and repeat all row/grant/migration/integrity/reconciliation checks before exposing it. Account for any post-cutover writes before using that older checkpoint; never silently discard them. No directory deletion, live file replacement or old-password restoration is authorized.

## Earlier recovery checkpoints (historical; superseded above)

## Approved attempt and actual checks

The owner approved `aria_chk --safe-recover` without `--quick` on the working copy only. A separate pre-repair copy of global_priv.frm/.MAI/.MAD was retained privately, alongside the untouched original and complete offline backup. The installed MariaDB 10.4.32 utility ran with `--no-defaults --datadir=<working-data> --require-control-file --safe-recover --backup <working-data>/mysql/global_priv.MAI`: exit 0, six records reported. Copy-only metadata normalization subsequently succeeded; this table's actual SQL CHECK TABLE now returns OK.

Preservation was independently checked using a second read-only reference server on loopback 13310. Its global_priv.MAD came from the untouched original, with an index-only rebuild; its data-file SHA-256 still exactly matched the original after shutdown. Private canonical row hashes for all 30 mysql base tables and SHOW GRANTS hashes for all six database accounts matched the repaired copy at 13309. No privilege contents, authentication hashes or passwords were printed. Actual root and application PDO authentication worked without bypassing grants.

Application verification passed: all ten tables readable, historical row hashes matched where a baseline exists, four migration records matched exactly, and dev_admin/Admin, dev_staff/Staff and dev_viewer/Viewer remain active with their original flags. Stock reconciliation checked three products with zero discrepancies (17, 10 and 0 units). Two uploads match their preservation hashes.

Further metadata normalization exposed another unreadable trailing page in mysql.proxies_priv: page 16384, error 175/file too short; four records. No data-rewriting repair was attempted on this second table. Failed-normalization files were retained separately; reference table files were restored on the working copy solely for read-only preservation checks. Final CHECK TABLE across 40 system/application base tables: **31 PASS, 9 FAIL**. global_priv and all ten application tables pass. proxies_priv remains unresolved; roles_mapping, servers, tables_priv and five time_zone tables still report relocation metadata errors because normalization stopped at proxies_priv. Matching readable rows does not close these integrity failures.

Both temporary servers were cleanly shut down. Final SHA-256 verification: **167/167 offline-backup files unchanged**. Original database, configuration, credentials and uploads unchanged. Active app.log and local-web-err.log changed through runtime logging; their backup versions remain intact. No reinitialization, deleted rows, seed substitution, grant bypass, cutover or password reset. Composer checks are recorded in progress.md.

## Remaining options and approval boundary

1. Obtain explicit approval for a separately backed-up `aria_chk --safe-recover --backup` **without `--quick`** on working-copy mysql.proxies_priv. Compare all four rows and every grant against the captured reference, normalize remaining copied metadata, then require all system/application integrity checks to pass before reversible cutover. Stop on lost records, changed privileges or unresolved corruption.
2. Continue read-only forensic investigation with a MariaDB recovery specialist using private original/backup/reference copies.
3. If available, assess a known-good historical full backup and any later-record loss before authorizing restoration. Do not substitute fresh system privileges or seeded inventory.

Admin reset and actual login/forced-change/logout/relogin are **BLOCKED**, not passed. Username: dev_admin. The requested password remains available in conversation context but has not been applied, printed or logged. Initial local credentials remain in ignored var/development-credentials.json; changed passwords cannot be recovered from hashes. After integrity passes, use the existing secure stdin Admin recovery mechanism, which revokes sessions and requires a password change. MySQL 8.4, production HTTPS and hosted image-storage verification remain pending. No deployment.

## Earlier diagnostic checkpoint (historical)

Verified no running process uses `.tools/mariadb/data`; the unrelated XAMPP process uses `C:/xampp/mysql/data` and was left untouched. Complete offline backup at `.tools/recovery/2026-10-12T02-30-53-449Z/offline`, plus a private SHA-256 manifest: 167 verified files / 188,891,880 bytes. Includes the complete original data directory, server configuration/logs/credentials, application configuration, logs, initial credential file and uploaded files. Do not publish this private backup.

Testing used only a working copy of these original files, with the same installed MariaDB 10.4.32 binary, on loopback 13309/read-only mode. No seed data, reinitialization, grant bypass or changed application configuration. The copied corrupt journal was quarantined, retaining its bytes and aria_log_control. Index-only repairs retained every Aria data-file hash. Empty columns_priv needed key-cache index recovery and relocation metadata normalization. A clean-stop/start race was resolved by waiting for the copy process to exit before further offline work.

The copy successfully started and verified all ten existing application tables. Row hashes matched the pre-failure baseline (rate-limit counters were excluded from the older baseline comparison because earlier authorized sign-ins changed them normally). Four exact migration records matched. Existing dev_admin/Admin, dev_staff/Staff and dev_viewer/Viewer were present with original flags. Stock reconciliation checked three products with zero discrepancies. Credentials and inventory were not reset.

System-table CHECK TABLE exposed relocation metadata problems. Copy-only Aria metadata normalization then failed at mysql.global_priv: page 16384 could not be read, error 175. **No data-rewriting recovery or force/discard-row option has been executed.** The next candidate is backed-up `aria_chk --safe-recover` on this working-copy privilege table, without `--quick`; this rewrites .MAD data and may discard damaged records. Owner approval is required before this attempt. It must never be promoted if existing records/accounts cannot be preserved. The original data/configuration, offline backup, initial-credential file and uploads still match their SHA-256 manifests.

After successful approved copy recovery, remaining work: complete system/application integrity checks, preserve all records, make a reversible local cutover, verify original-port connection/reconciliation, use the existing secure stdin Admin recovery command, then test real login/forced-change/logout/relogin and compare unaffected account/inventory/upload hashes. The requested password is still available in conversation context and must never be printed or logged. No reset is claimed until this work succeeds.

The [MariaDB Aria FAQ](https://mariadb.com/docs/server/server-usage/storage-engines/aria/aria-faq) documents journal recovery and preservation of aria_log_control; relocation normalization does not remove vital data. Here the unreadable privilege-data page requires a separate risk decision. MySQL 8.4, production HTTPS and hosted-storage verification remain pending. No deployment.
