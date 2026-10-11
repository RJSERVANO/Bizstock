# BizStock

The public [homepage](http://127.0.0.1:8080/) and [one shared login](http://127.0.0.1:8080/login) are implemented. Active local usernames: `dev_admin` (Admin), `dev_staff` (Staff), `dev_viewer` (Viewer). Each uses its own administrator-provided credentials. Initial development credentials are private in ignored `var/development-credentials.json`; changed passwords supersede that file. Forgotten passwords require an existing Admin's secure reset, or trusted emergency CLI recovery if no Admin access remains; no public signup or automatic resets. Normal login opens Dashboard; required password changes open Account first.

Set optional `APP_BUSINESS_NAME` and `APP_CONTACT_EMAIL` in private `.env`; use only a verified business contact. Empty email displays administrator guidance. Every link/asset supports `APP_BASE_PATH`. Existing uploads, migration 004 and dashboard are reused. Run `composer test:homepage` for the new isolated acceptance checks. [Design, screenshots and evidence](docs/public-homepage.md), [asset provenance](docs/asset-licenses.md). MySQL 8.4, production HTTPS and hosted image storage verification remain pending.

The live interface has been refined across dashboard, inventory, movements, reports, lookups, users/audit and account. See [changes and browser evidence](docs/ui-refinement.md) and the [before/after comparison](docs/visual/refinement/index.html). Run the isolated UI review with `php tests/integration/ui-refinement.php`; the installed inventory and credentials are preserved.

Current checkpoint: **Stages 1–15 implemented and locally checked**. Admin `/users` and `/audit-events` are live; all 32 documented API operations are implemented. Existing inventory/accounts/credentials are preserved. **Not deploy-ready:** MySQL 8.4, production HTTPS, actual participant testing, CI execution and selected host/credentials remain pending. MariaDB 10.4.32 is local only. No Stage 16/deployment.

Read [test evidence](docs/test-plan.md), [Admin/reset/recovery instructions](docs/admin-management.md), [user guide](docs/user-guide.md), [deployment/install/upgrade/backup/restore/rollback runbook](docs/deployment.md) and [participant scripts](docs/user-testing.md).

Additional isolated checks: `composer test:users`, `composer test:system`, `composer test:backup`, `composer test:install`. Native backup tests default to XAMPP client paths locally; other runners set TEST_MYSQLDUMP/TEST_MYSQL_CLIENT/TEST_COMPOSER. CI-only database administrator variables are TEST_DB_ADMIN_USER/TEST_DB_ADMIN_PASSWORD. Never run destructive disposable tests against production. The clean package test installs from the workspace Composer cache and removes only its temporary package/database.

For local emergency recovery use `bin/recover-admin.ps1 -UserId ID -Reason 'Reason'` with hidden password entry. Backup/restore commands and required private directories/principals are in deployment.md. Restore accepts only a separately configured empty target and a trusted inspected SQL dump. No account reset is needed to upgrade through Stage 15; migrations are unchanged and seeds stay development-only/no-op on existing data.

Single-business inventory application. Stages 1-15 and authorized UI/image/public extensions are implemented and locally verified on MariaDB 10.4.32. Stage 16 deployment remains unstarted. MySQL 8.4, production HTTPS and hosted image storage verification remain pending. See [progress](docs/progress.md), [schema](docs/schema.md), [workflows](docs/business-workflows.md) and [OpenAPI](docs/openapi.yaml).

## Runtime requirements

- PHP **8.5.x**, maintained patch release, with PDO/pdo_mysql, mbstring and OpenSSL. Composer **2.9.x**; cURL/ZIP recommended for package installation.
- Production database target: **MySQL 8.4.x LTS**, InnoDB/utf8mb4; actual verification pending. Local development additionally supports explicit **MariaDB 10.4.32** on loopback with APP_ENV local/test. Wrong versions, engine mismatches and production use of this MariaDB target are rejected. Database is optional for foundation/liveness; later features require it. No production host chosen yet.
- Apache with PHP support and mod_rewrite for deployment/XAMPP; PHP built-in loopback server for development only. Only `public/` is document root.

Initial detection on 2026-10-11: XAMPP PHP 8.2.12, MariaDB client 10.4.32, Apache 2.4.58 and Composer 2.9.8; no server listening at 127.0.0.1:3306. The compatibility interlude subsequently verified the installed MariaDB engine in a separate workspace instance on port 13307. PHP verification uses official PHP 8.5.11 in ignored `.tools/php/`; global/XAMPP configuration remains unchanged. MySQL is not installed or verified here.

Version sources: [PHP support policy](https://www.php.net/supported-versions.php), [PHP Windows downloads](https://www.php.net/downloads.php?os=windows), [MySQL 8.4 LTS](https://dev.mysql.com/doc/refman/8.4/en/mysql-releases.html), [Bootstrap download/integrity hashes](https://getbootstrap.com/docs/5.3/getting-started/download/). Bootstrap **5.3.8** is pinned in composer.lock and installed locally with official SHA-384 verification; pages do not depend on a live CDN.

## Fresh setup

Use the selected PHP on PATH, then:

```sh
composer install --no-interaction --prefer-dist
composer setup
composer assets
composer validate --strict
composer check:runtime
composer check
composer serve
```

Open `http://127.0.0.1:8080/`; health is `/api/v1/health`. Health reports process liveness only, not DB connectivity, schema readiness or release readiness. No secrets or database details returned. Stop the development server with Ctrl+C.

In this Windows workspace, use the portable PHP explicitly (system `composer.bat` otherwise runs XAMPP PHP):

```powershell
$php = '.tools/php/php.exe'
$composer = 'C:/ProgramData/ComposerSetup/bin/composer.phar'
& $php $composer install --no-interaction --prefer-dist
& $php $composer setup
& $php $composer assets
& $php $composer check
& $php $composer serve
```

The portable runtime is not committed. To reproduce it, download the official PHP 8.5.11 x64 NTS ZIP from `https://downloads.php.net/~windows/releases/archives/php-8.5.11-nts-Win32-vs17-x64.zip`, verify SHA-256 `0ea96e0d2b9b737a6036f05cf4e95c49313faa6d0f27bd97edb2742503f0c043`, extract to `.tools/php`, and enable curl, mbstring, openssl, pdo_mysql and zip in php.ini with `extension_dir="ext"`. Use current patched 8.5 builds for ongoing maintenance. NTS is CLI/FastCGI; Apache module deployments require the appropriate TS build or separately configured FastCGI. Do not replace XAMPP binaries in place without a compatible upgrade.

`setup` creates ignored `.env` from the example only when missing and private var/logs and var/sessions directories; it does not overwrite configuration or create accounts/database data. Run assets after install to copy verified CSS/JS and license from locked vendor packages. Use install, not update, for a clean checkout. Lockfile updates are deliberate reviewed dependency changes.

## Configuration

Process environment values take precedence over `.env`; never commit private configuration.

| Variable | Default / purpose |
|---|---|
| APP_ENV | local; allowed local/test/production; errors remain sanitized in all modes |
| APP_BASE_PATH | empty at site root, or /bizstock; no trailing slash; URLs generated centrally |
| APP_CURRENCY | PHP; MVP currency |
| APP_TIMEZONE | Asia/Manila for business display/reports; runtime/DB storage UTC |
| DB_HOST / DB_PORT | 127.0.0.1 / 3306 |
| DB_TARGET | mysql84 by default; mariadb104-local only for explicit local/test development |
| DB_NAME / DB_USER | bizstock / bizstock; private least-privilege account when DB provisioned |
| DB_PASSWORD | empty placeholder, supply privately; never included in health or logs |
| APP_LOG_PATH | var/logs/app.log; existing writable directory outside public/ required |

`composer check:database` verifies actual engine/version and required session settings through the shared PDO factory. It configures the session but creates no schema. `composer test:database` runs disposable real-engine constraint/transaction probes in local/test only. These passes do not establish domain schema/service acceptance or prove another engine.

## Apache root and subdirectory

Prefer a virtual host with `DocumentRoot "C:/Users/reina/Bizstock/public"` and a `<Directory>` block for that same public path enabling `AllowOverride All` and `Require all granted`. Enable mod_rewrite; public/.htaccess disables listings, hides dot paths and routes non-files to index.php. Set APP_BASE_PATH empty for root deployment.

For an XAMPP subdirectory, use an Apache Alias pointing directly at public/, **not** the whole project under htdocs:

```apache
Alias /bizstock "C:/Users/reina/Bizstock/public"
<Directory "C:/Users/reina/Bizstock/public">
    AllowOverride All
    Require all granted
</Directory>
```

Set `APP_BASE_PATH=/bizstock`, reload your Apache configuration, and visit `/bizstock/`. Do not expose the repository through a second alias/document root. XAMPP requires the selected PHP runtime and separate acceptance on the explicitly configured database target. The built-in development router also supports this base path; same serve command, visit `http://127.0.0.1:8080/bizstock/`.

Production requires HTTPS, private log/session permissions, display_errors disabled at server level and source/secrets/backups outside public/. Stage 15 supplies the full operations/runbook and Stage 16 verifies the selected real host. Apache deployment examples have not been live-installed in this stage.

## Checks and boundaries

`composer check` runs PHP syntax checks and actual loopback HTTP tests at root, /bizstock and a nested base path, including private-file isolation, route/method errors, HEAD, request IDs, asset serving, configuration validation and sanitized bootstrap/internal errors. Test processes terminate automatically; test logs stay in ignored var/. MySQL integration, sessions/roles/CSRF, full API client/OpenAPI and business services belong to later stages and are not simulated here.

## Stage 3 fixture UI and visual checks

Run the same `composer serve` command and open `/preview/dashboard`, `/preview/products`, `/preview/products/new`, `/preview/login` or `/preview/components`. `?state=loading`, `empty`, `error` or `restricted` exercises state previews; `/preview/products/new?mode=edit` shows readonly stock/SKU. Fixed Viewer fixture views live at `/preview/viewer/dashboard` and `/preview/viewer/products`. There is no role-switching control. These routes are labeled, return 404 in APP_ENV=production and never call a database. Valid forms open an explicit no-save/no-session review rather than pretending to authenticate or persist data.

See [component APIs](docs/design-system.md) and [visual review/screenshots](docs/visual-review.md). Browser test dependencies are locked under tests/browser, outside public/; they are development tools, not a frontend build dependency. With Node >=20 and installed Chrome:

```powershell
npm.cmd ci --prefix tests/browser --cache .tools/npm-cache
node tests/browser/review.cjs
```

On another machine set `BIZSTOCK_PHP` to your PHP 8.5 executable if not using `.tools/php/php.exe`. Set `BIZSTOCK_BROWSER=msedge` for installed Edge, or use the default Chrome channel. The test launches its own loopback server and headless browser and terminates them afterward. It writes screenshots and a JSON report to docs/visual/stage3. PHP fixture/production-isolation checks are included in `composer check`.

MariaDB 10.4.32 connection, capability probes and Stage 4 schema/bootstrap/seeds/reconciliation are verified separately; MySQL 8.4 and later business-service verification remain **pending**. See [compatibility policy](docs/database-compatibility.md) and [Stage 4 evidence](docs/stage4-results.json).

## XAMPP MariaDB local development

This workspace uses the installed XAMPP engine in an isolated instance on **127.0.0.1:13307**, with data/configuration/random credentials under ignored .tools/mariadb. Existing XAMPP data and services are untouched. Run initialization only once after the PHP/Composer setup above:

```powershell
& .tools/php/php.exe bin/init-local-mariadb.php
powershell.exe -NoProfile -ExecutionPolicy Bypass -File bin/local-mariadb.ps1 start
& .tools/php/php.exe bin/configure-local-mariadb.php
& .tools/php/php.exe C:/ProgramData/ComposerSetup/bin/composer.phar check:database
& .tools/php/php.exe C:/ProgramData/ComposerSetup/bin/composer.phar test:database
```

Initialization refuses an existing data directory. Configuration creates a local database/account and updates only private DB settings; it preserves APP settings and existing data. Use APP_ENV=local (or test). DB_TARGET=mariadb104-local selects this exact engine/version; configuration chooses port 13307/database bizstock_local/private account. Subsequent sessions need only the start command; stop with the same PowerShell invocation ending in `stop`. Bypass applies only to that script process, without changing machine execution policy. The instance uses innodb-use-native-aio=0 after an observed local I/O hang; transaction durability/checks remain enabled. See compatibility report for evidence and limitations.

To use your own already-running XAMPP instance, configure the explicit local target, loopback host, its port, database and private account yourself, then run both database commands. That instance/data directory was not tested by this run. Do not point provisioning scripts at an existing business installation. Test probes require development DDL privileges and must use a disposable database; production uses separately provisioned MySQL 8.4, DB_TARGET=mysql84 and least-privilege runtime/migration accounts. Do not copy local .env or root credentials into deployment.

## Stage 4 schema and account setup

With the selected database configured and running, use portable PHP/Composer as above:

```powershell
& .tools/php/php.exe C:/ProgramData/ComposerSetup/bin/composer.phar migrate
& .tools/php/php.exe C:/ProgramData/ComposerSetup/bin/composer.phar reconcile:stock
```

Migrations are ordered and SHA-256 tracked; complete reruns do nothing. Existing untracked domain tables, changed/missing applied files and incomplete migration records are refused. Back up an existing installation before upgrades. DDL commits implicitly; review a failed migration's actual schema and recorded completed_statements before recovery. No automatic reset/drop/history rewrite command exists. See [schema ordering and recovery](docs/schema.md).

For a fresh owner installation, create the initial Admin with hidden password prompts (no password argument, echo or default):

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File bin/bootstrap-admin.ps1 -Username owner -FullName "Business owner"
```

Use `-Php` for a different PHP 8.5 executable. On other systems, send a secret through stdin to `php bin/bootstrap-admin.php owner "Business owner" --password-stdin` using your trusted secret-input mechanism. The password is 12–72 UTF-8 bytes without NUL; initial Admin requires an empty account table, is active and must change password. Bootstrap rerun preserves existing accounts and refuses replacement; this is not Stage 13 recovery. Real login and forced-change enforcement are now available at /login and /account; fixture previews remain isolated.

Alternatively, on an empty local/test schema only, seed development examples instead of owner bootstrap:

```powershell
& .tools/php/php.exe C:/ProgramData/ComposerSetup/bin/composer.phar seed:development
& .tools/php/php.exe C:/ProgramData/ComposerSetup/bin/composer.phar reconcile:stock
& .tools/php/php.exe C:/ProgramData/ComposerSetup/bin/composer.phar test:schema
```

Seeds create all three roles with random hashed passwords and forced change, and consistent opening IN/OUT history. Credentials are stored in ignored `var/development-credentials.json`, outside public/, without printing them. A seed rerun makes no changes or new passwords. Any existing non-seed installation is refused. Production seeding is prohibited. The current workspace used this development route; its three products reconcile (17, 10 and 0 units). Keep those credentials/data local.

`test:schema` creates random `bizstock_stage4_<hex>` databases on the configured loopback server and deletes only databases it created. It preserves the configured database. Tests require CREATE/DROP DATABASE plus schema/trigger permissions; on this isolated MariaDB instance they use the ignored private root credential. For a separately configured test server set private TEST_DB_ADMIN_USER/TEST_DB_ADMIN_PASSWORD process variables (never commit them). MySQL tests use APP_ENV=test, DB_TARGET=mysql84 and that engine's disposable database administrator. No MySQL pass is currently claimed. Reconciliation exits 0 when balanced, 1 on drift, 2 on command failure and never repairs stock.

## Stage 7 categories and suppliers

After signing in and changing any initial password, use the account navigation to /categories or /suppliers (prepend APP_BASE_PATH). Admin can add/edit/delete; Staff reads both and Viewer reads categories only. Names persist after reload; search/sort/pagination and loading/empty/error/retry states use the real APIs. Supplier contact email is optional. Referenced parents return a reassignment message and stay intact, including when products are archived; reassignment itself remains Stage 9.

Run `composer test:lookups` with the selected portable PHP/Composer as above, local MariaDB running and existing browser dependencies installed. It creates/removes only a random disposable test database/session directory, preserving installed users, credentials and stock. See [lookup/API/validation/audit details](docs/lookups.md). MySQL 8.4 and production HTTPS verification remain pending. Stage 8 stock-service work has not started.

## Stage 5 API infrastructure and contracts

Health, authentication/account and eight category/supplier operations are live (15 total). The 17 other business operations remain planned, with permission/CSRF gates and 404 for allowed requests; denied requests return 401/403. See [API foundation](docs/api-foundation.md), [authentication](docs/authentication.md), [lookups](docs/lookups.md) and [OpenAPI](docs/openapi.yaml). Health accepts no query fields. JSON envelopes, validation and client are shared; unfinished business success is never simulated.

Composer check includes the PHP API contracts. With Node and the existing browser test dependencies, run:

```powershell
node tests/openapi.cjs
node tests/api-client.cjs
node tests/browser/api-live.cjs
```

The OpenAPI artifact uses JSON syntax compatible with YAML 1.2; regenerate with `node bin/build-openapi.cjs`. Its focused audit checks references/status/security/contracts. Client transport tests use explicit fixtures; actual authentication acceptance uses real MariaDB and Chrome. CSRF tokens stay in memory; handle bizstock:session-expired and clearSession after session changes. Writes have no automatic retry: retain the caller-supplied operation key for one logical mutation. No frontend build is required.

## Stage 6 login and account

Start the verified MariaDB instance and run `composer serve` with portable PHP as above. Open http://127.0.0.1:8080/login (prepend APP_BASE_PATH if configured). Use your privately provisioned initial owner credential, or read your local-only ignored var/development-credentials.json privately. Initial credentials require a password change on account. Never copy development credentials into production. /account supports password changes/sign-out and role-appropriate live workspace navigation. No public signup; Admin user management and recovery are implemented; see docs/admin-management.md.

`composer test:auth` runs disposable MariaDB HTTP/API/browser acceptance and cleans only its own test database/sessions. It requires the same private test administrator access as test:schema and installed browser dependencies. Existing data/passwords are preserved. Default idle/absolute expiry is 30 minutes/8 hours; see .env.example and [session/security details](docs/authentication.md). Keep var/sessions outside the document root with private filesystem permissions. Production requires direct HTTPS with Secure cookies; reverse-proxy/TLS hosting verification remains pending. MySQL 8.4 remains the production target and its execution verification remains pending; MariaDB 10.4.32 is local development only.

## Live workspace and checks through Stage 12

After login/initial password change, use /dashboard, /products, /movements, /history and /reports/inventory or /reports/transactions (prepend APP_BASE_PATH). Admin creates/edits/archives; Staff records IN/OUT and reads reports; Viewer sees availability only. Admin full reversals preserve original price/history. Category/supplier management remains shared. Account navigation links all permitted live screens. Admin user management/audit browsing are live at /users and /audit-events; all 32 documented operations are implemented.

Use the selected runtime:

```powershell
& .tools/php/php.exe C:/ProgramData/ComposerSetup/bin/composer.phar test:business
& .tools/php/php.exe C:/ProgramData/ComposerSetup/bin/composer.phar check
node tests/openapi.cjs
```

Individual commands: test:stock, test:products, test:movements, test:dashboard, test:reports, test:flow. All tests create/remove only their own random loopback databases and private sessions. They require private disposable-test administrator privileges and the installed Chrome/browser dependencies described above. They preserve the configured application database. Do not run them against production.

Resolve uncertain product/movement outcomes with the unchanged in-memory operation key before leaving the form. Do not repeat an ambiguous operation in a new tab without inspecting history. CSV exports all matching pages up to 10,000 rows/16 MiB, in a fresh snapshot; print displays the current page. See [business workflow, scope, retry and export rules](docs/business-workflows.md). MySQL 8.4 execution and production HTTPS remain pending; MariaDB 10.4.32 is local development only. No new migration or seed reset is needed for Stages 8–12.

## Profile photos, product images and sidebar
See [secure image setup and local login guidance](docs/private-images.md). Enable GD/WebP and Fileinfo in PHP 8.5, restart the local web process, back up privately and run migration 004 plus safe rerun/reconciliation. Keep var/uploads outside the public document root and back it up with the database. All roles use http://127.0.0.1:8080/login; current local usernames and private initial-credential location are documented without passwords. No account resets are part of this change.
