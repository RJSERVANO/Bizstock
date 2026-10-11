# Deployment preparation — Stage 15, no deployment

Local preparation is verified on MariaDB 10.4.32 only. **Not deploy-ready:** MySQL 8.4 execution, production HTTPS, actual participant testing and hosting/credentials remain pending. MariaDB 10.4.32 is local development only and rejected in production. Stage 16 has not started. No services were purchased or published.

## Missing external prerequisites

Owner must select provider, domain/DNS, direct HTTPS/Apache/PHP hosting and supported MySQL 8.4 service, backup destination/encryption and monitoring owner. Obtain deployment/SSH or control-panel access, database migration/runtime/backup credentials, TLS certificate management and private filesystem access. None was supplied or used. Provision a dedicated isolated CI runner/database separately; never reuse production credentials for tests.

PHP 8.5 matches composer.json; install required PDO/pdo_mysql/mbstring, locked Composer dependencies and pinned Bootstrap assets. Node/Chrome/locked Playwright/axe are test tooling, not web-server dependencies. Apache must expose only public/. Source, Composer tools, docs, secrets, sessions, logs and backups are private. Use one server initially: file sessions are not a configured multi-node shared store. Reverse proxy trust is not implemented; direct HTTPS is required and arbitrary forwarded headers do not establish it.

## Configuration and filesystem

Use .env.example privately. Production: APP_ENV=production, DB_TARGET=mysql84, explicit DB_HOST/PORT/NAME/USER/PASSWORD; APP_BASE_PATH empty at root or the configured subdirectory; APP_CURRENCY=PHP, APP_TIMEZONE=Asia/Manila. APP_LOG_PATH points outside public/. Idle/absolute session defaults are 1800/28800 seconds and validated. Provision private var/sessions and var/logs plus operator-only var/backups; Unix directories 0700, files 0600, or equivalent Windows ACLs restricted to the relevant service/operator. .env must be readable only by the PHP operator/service. Backup/export hashes and passwords remain outside public/ and version control. Backup CLI temporary client-option files inherit private directory permissions and are removed on success/failure; passwords never enter process arguments.

Set PHP display_errors=Off, log_errors=On, production opcache enabled and reload PHP workers on release. Application errors remain generic with request IDs; protect/rotate server and application logs and avoid logging request bodies/cookies/passwords. Production cookie flags are Secure/HttpOnly/SameSite=Lax; verify actual response headers after TLS exists. APP_ENV must not be changed to local to bypass HTTPS or engine checks.

Example Apache configuration, replacing paths/domain/certificates with the selected host's values:

```apache
<VirtualHost *:443>
  ServerName inventory.example.invalid
  DocumentRoot /srv/bizstock/current/public
  SSLEngine on
  SSLCertificateFile /private/tls/fullchain.pem
  SSLCertificateKeyFile /private/tls/privkey.pem
  <Directory /srv/bizstock/current/public>
    AllowOverride FileInfo
    Options -Indexes
    Require all granted
  </Directory>
</VirtualHost>
```

public/.htaccess supplies front-controller rewriting. Redirect HTTP to HTTPS at the host; verify PHP receives HTTPS=on. For a subdirectory, Alias /bizstock to the same public directory and APP_BASE_PATH=/bizstock; follow README's Directory/Alias example. Never Alias the repository root. This template has not been installed on a host.

## Database principals

Use separate migration/operator, web-runtime and backup principals. Migration operator owns CREATE/ALTER/INDEX/REFERENCES and supporting migration writes only for the application schema; initial schema creation/restore require trusted operator privileges. Runtime has SELECT on the schema; INSERT/UPDATE on users, products, operation_requests; INSERT on transactions/audit_events; INSERT/UPDATE/DELETE on categories/suppliers/rate_limits. No runtime DROP/ALTER or UPDATE/DELETE on history/audit. Keep schema_migrations/security_locks read-only to runtime.

MySQL's FOR UPDATE needs SELECT plus UPDATE, DELETE or LOCK TABLES. Grant schema-scoped LOCK TABLES for the current locking reversal/security sentinel reads without granting history updates. See the [official MySQL 8.4 locking-read privilege specification](https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html). No code uses explicit LOCK TABLES. The proposed grants were rehearsed with an isolated MariaDB principal: stock/reversal/user/audit operations pass; history UPDATE/audit DELETE fail. **MySQL grant execution remains pending**.

Backup needs schema SELECT and privileges for any views/triggers in the actual schema. Current schema has neither; --no-tablespaces avoids requiring PROCESS for a tablespace dump. Future routines/views/triggers require a reviewed backup policy update, not silently omitted objects. Backup and restore credentials are configured through the same private environment, never a password command-line option. Do not grant FILE/SUPER or use root for web requests. Disposable tests require a separate loopback administrator allowed to create/drop their uniquely named test databases/principals.

## Installation and upgrade

1. Select an immutable reviewed release/commit and verify local checks plus actual target MySQL/HTTPS gates. No release commit is created by this task.
2. Install into a new private release directory: `composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader`, then `composer assets`. Configure production environment, private writable paths and PHP/Apache. `bin/setup.php` creates a **local** default environment; do not run it over a production environment or assume it provisions production secrets.
3. Run `php bin/check-runtime.php` and `php bin/check-database.php` with selected credentials. Wrong engine/environment fails. Freeze application writes and migration DDL during the pre-upgrade consistent backup; host-level maintenance responses are an operator action, not a implemented app toggle.
4. Back up database and separately protect configuration/keys. Record checksum, release, engine version and migration state. Never seed development data in production.
5. Use migration principal: `php bin/migrate.php`. It verifies checksums/advisory lock, refuses changed applied SQL and unresolved running DDL, and reruns safely. Do not edit applied migrations, reset tables or auto-repair failed migration state. Review any partial nontransactional DDL explicitly before continuing.
6. Fresh database only: provision the initial owner through hidden-input `bin/bootstrap-admin.ps1`, or private stdin `php bin/bootstrap-admin.php username 'Full name' --password-stdin`. Existing accounts are not bootstrapped/reset on upgrade.
7. Switch the web release atomically, reload PHP/opcache, use runtime principal, and run health/login/role/CSRF/product/movement/report/denial checks with authorized disposable verification records. Reconcile stock; confirm history and actual cookie/internal-file restrictions. Restore writes only after checks pass. Actual host cutover/smoke evidence belongs to Stage 16.

Local server: `composer serve` using the selected PHP runtime. Prepend APP_BASE_PATH in URLs. Installation rehearsal copies current source into a new private package, performs an offline locked --no-dev dependency install/assets, migrates an empty disposable database, reruns migrations, checks no demo users, health dispatch and reconciliation, then deletes only its package/database. Stage 4 separately rehearses additive upgrade with existing disposable data. Existing installation is preserved.

## Backup, restore and rollback

Policy for owner adoption: encrypted daily backups and a verified backup before every schema/release change; retain seven daily, four weekly and twelve monthly copies off-host. Restrict access and encryption keys separately; periodically review legal/business retention needs. Schedule a quarterly restore rehearsal, plus after database/client/schema changes. Alert on missing/failed backups and checksum failure. Schedule/destination/encryption/alerts are **not provisioned** until hosting is selected; SQL backup creation/restore is locally verified.

```text
php bin/backup-database.php /absolute/path/mysqldump /private/backups/release.sql
php bin/restore-database.php /absolute/path/mysql /private/backups/release.sql --empty-database
```

First command refuses overwrite and public destinations, creates output exclusively with private permissions and uses --single-transaction/--quick/--no-tablespaces/--hex-blob for all ten InnoDB tables. Record its SHA-256 and encrypt the SQL before off-host transfer. Hashes/credentials in backups are sensitive. Avoid concurrent migrations/DDL during dumps. Native MySQL 8.4 client/restore is pending; installed XAMPP clients are tested only with MariaDB.

For restore, verify/decrypt/checksum a **trusted** operator backup. Configure a separate empty database using private process environment. The tool refuses a nonempty schema and never clears it. The guard is not a malicious-SQL sanitizer: only restore inspected dumps from trusted backups; this application's dump excludes database CREATE/USE directives. Keep target access restricted if import fails: SQL restore is not guaranteed atomic, and a partially imported target must be discarded/recreated by the operator rather than exposed. Restore configuration/keys separately, verify all tables/credentials/migration checksums, rerun migrations safely, reconcile stock and smoke-test before an explicit cutover. Use a fresh session namespace/clear target sessions after restore so old sessions cannot regain access. Leave the original database untouched until restore is accepted.

Rollback decision: if only code failed and the previous version is compatible with the current schema, switch to that verified release and reload workers; keep database history. **Code rollback does not undo migrations.** If incompatible DDL/data failure requires restoration, freeze writes, record every post-backup committed operation, restore into a separate empty target, reconcile/smoke-test and obtain the owner's data-loss/RPO decision before cutover. Do not replay stock from guesses or silently repair balances. Keep failed database/release/logs private for diagnosis. No down-migration or production rollback has been executed here.

## CI, monitoring and remaining gates

.github/workflows/verify.yml is a manually triggered, pinned-checkout workflow for a dedicated self-hosted `bizstock-test` runner. Provision PHP 8.5, Composer, Node, Chrome, native MySQL 8.4 clients and isolated loopback MySQL 8.4 first. Configure CI-only DB credentials and client/Composer PHAR paths in the named secrets/variables. Its runtime/admin test credentials are for disposable schemas, not production. It runs syntax/foundation/schema/auth/lookups/business/users/system/backup/install/OpenAPI/client checks. Offline installation uses the cache populated by the initial locked install. **Workflow execution and actual MySQL CI are pending**; files alone are not passing CI.

Monitor GET /api/v1/health only as liveness, not database readiness. Run privileged CLI connection/reconciliation and inspect failed commands/request IDs privately. Alert on 5xx/login abuse, backup/migration/restore failures, disk/session storage exhaustion and stock drift. Do not expose DB readiness/secrets publicly. Owner must assign monitoring/on-call responsibility and verify alerts on the selected host. Review dependencies monthly, update locks/assets in a branch and rerun database/browser/install/restore checks before release; never blindly update production dependencies. Participant/assistive-technology feedback, MySQL 8.4, production TLS and actual operational alerts/backups remain release gates.

## Image storage upgrade
The newly authorized image scope requires migration 004, GD/WebP and Fileinfo, protected writable var/uploads namespaces and coordinated database/files backups. Never expose private files through static hosting. Stop mutations while capturing/restoring matching DB/files; retain additive columns/files during code rollback. Production ACL/decoder/image-backup rehearsal remains pending alongside MySQL 8.4/HTTPS. [Detailed installation, cleanup and recovery](private-images.md).

## Public homepage configuration

APP_BUSINESS_NAME (default BizStock) is public, escaped, nonempty and at most 100 characters. APP_CONTACT_EMAIL defaults empty; supply only a verified public business email, otherwise administrator guidance is shown. Include the three committed public/assets/images/stockroom-*.webp derivatives and public.css in release assets; no generated original/private upload directory belongs under public/. Review [asset provenance](asset-licenses.md). Public / remains readable signed in; CTA responses are private/no-store. Shared /login still determines permissions from authenticated accounts, never role links. APP_BASE_PATH prefixes all public and protected links/assets. Production HTTPS, MySQL 8.4, hosted image storage and its coordinated backups remain pending; this configuration does not constitute deployment.
