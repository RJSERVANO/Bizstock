# BizStock schema — Stage 4

2026-10-12 usability extension requires **no schema change**. Four tracked migrations remain intact. Own profile updates users.full_name/updated_at with safe atomic audit; created_at is displayed as Member since, never as a password-change date. Dashboard uses idx_transactions_date(transaction_date,transaction_id), bounded UTC WHERE ranges and Manila grouping, with exact DECIMAL sums returned as strings. Tiny-fixture EXPLAIN proves index eligibility and chooses a covering index scan for its broad range; it is not a production benchmark. [Actual plan](visual/usability/index-evidence.json). MySQL 8.4 remains pending.

The ten-table schema and three ordered migrations are implemented. **MariaDB 10.4.32 local development is verified; MySQL 8.4 production verification is pending.** [Stage 4 results](stage4-results.json) record actual schema/upgrade/bootstrap/seed/reconciliation assertions. No business HTTP endpoints, authentication or stock service are implemented by this stage.

## Migration ordering and safety

1. [001_domain.sql](../database/migrations/001_domain.sql): users, categories, suppliers, products, transactions, in FK dependency order.
2. [002_support.sql](../database/migrations/002_support.sql): security_locks plus its singleton, operation_requests, audit_events and rate_limits.
3. [003_access_indexes.sql](../database/migrations/003_access_indexes.sql): additive access-pattern indexes. Verified upgrading already-populated migration 001 data through 002/003 without changes to account hashes, IDs, lookups, products or movements.

MigrationRunner first acquires a per-database GET_LOCK, checks every recorded SHA-256 against immutable SQL files, refuses missing/changed history or incomplete runs, and applies only unrecorded migrations. Fully applied reruns leave rows/checksums/timestamps unchanged. Migration identifiers must be ordered `NNN_name.sql`. SQL files currently contain simple statements only: no procedures, delimiter directives or semicolons inside comments/literals. The runner is not a general SQL parser.

schema_migrations is created by the runner before migration 001. Records are written as running before DDL, with a completed-statement count after each successful statement, and marked applied only after completion. DDL implicitly commits, so partial schema creation is not rolled back. A failed/abandoned run remains running and blocks rerun. Preserve the database, logs and original migration files; inspect actual schema and recorded progress, then perform a reviewed recovery/restore. Never clear history, silently mark a run applied, edit shipped applied SQL or reset an existing installation. A crash between DDL and progress recording requires inspection. New baseline installation refuses existing untracked domain/support tables; importing an unrelated legacy schema requires a separately designed upgrade. No reset command is provided.

## Common column and storage rules

All tables use InnoDB. Domain/human-text tables explicitly use utf8mb4_unicode_ci; schema, singleton and limiter storage have only numeric/explicit ASCII columns. Human category uniqueness is case/accent insensitive and padding-sensitive according to that collation; input is trimmed before application validation. Identifiers use nonbinary ASCII character columns with ascii_bin comparison. SKU is uppercase `[A-Z0-9._-]`, 1–64; username lowercase `[a-z0-9._-]`, 1–50. CHECKs reject whitespace and unsupported characters. Role and movement tokens use exact case. No MySQL-only 0900 collation, JSON operator/default, quantity trigger or cascade deletion is used.

Signed INT quantities/thresholds have range 0–2147483647 (movement quantity 1–2147483647); price/unit_price are DECIMAL(10,2), 0.00–99999999.99 and returned as decimal strings. IDs are generated signed INT for domain tables and BIGINT for supporting records. Server validators must reject invalid IDs and overflow before SQL. DATETIME(6) stores UTC; connection timezone is forced +00:00. Created/updated/movement/event times default to CURRENT_TIMESTAMP(6); application writes must explicitly advance updated_at/version. Boolean-like fields are TINYINT with 0/1 CHECKs.

JSON is nullable LONGTEXT utf8mb4_bin with explicit named JSON_VALID CHECKs. Application CanonicalJson sorts object keys, preserves lists and requires decimal strings; future request validation must bound/validate structures before hashing or persistence. Database syntax validity alone does not make an audit/replay payload safe. Passwords/tokens/session secrets must never enter JSON.

MySQL design review follows its [CHECK restrictions](https://dev.mysql.com/doc/refman/8.4/en/create-table-check-constraints.html): no checks on AUTO_INCREMENT columns or FK-action columns; deterministic local-column expressions only. REGEXP operates on character columns with ascii_bin, not BINARY/VARBINARY strings; see [regular expression collation rules](https://dev.mysql.com/doc/refman/8.4/en/regexp.html). This review is not a MySQL execution pass. Both engines still need separate actual acceptance.

## Tables and concrete fields

| Table | Columns and constraints |
|---|---|
| users | user_id INT AUTO_INCREMENT PK; full_name VARCHAR(100) nonblank; username VARCHAR(50) ASCII unique/canonical; password_hash VARCHAR(255) ASCII nonblank hash (minimum length guard, PHP hash/verify owns actual format); role VARCHAR(10) ASCII Admin/Staff/Viewer; is_active TINYINT default 1; session_version INT positive default 1; must_change_password TINYINT default 1; created_at/updated_at DATETIME(6). No plaintext credential column. |
| categories | category_id INT AUTO_INCREMENT PK; category_name VARCHAR(100) unique, nonblank and trimmed. |
| suppliers | supplier_id INT AUTO_INCREMENT PK; supplier_name VARCHAR(150) nonblank; contact_email VARCHAR(100) nullable; created_at DATETIME(6). Email syntax is a Stage 7 server-validation obligation. |
| products | product_id INT AUTO_INCREMENT PK; sku VARCHAR(64) ASCII canonical unique; product_name VARCHAR(150) nonblank; location VARCHAR(100) nullable; description TEXT nullable; required category_id/supplier_id INT FKs; price DECIMAL(10,2) nonnegative; quantity INT default 0; minimum_threshold INT default 10; version INT positive default 1; is_active TINYINT default 1; created_at/updated_at DATETIME(6). |
| transactions | transaction_id INT AUTO_INCREMENT PK; product_id/user_id INT required FKs; type VARCHAR(3) ASCII IN/OUT; quantity INT positive; unit_price DECIMAL(10,2) nonnegative; transaction_date DATETIME(6); request_key VARCHAR(128) ASCII nonblank/unpadded; payload_hash CHAR(64) lowercase SHA-256 hex; correction_of_transaction_id INT nullable unique self-FK; notes TEXT nullable. UNIQUE(user_id,request_key). |
| operation_requests | operation_id BIGINT AUTO_INCREMENT PK; actor_id INT required FK; operation_kind VARCHAR(40) ASCII nonblank; request_key VARCHAR(128) ASCII nonblank/unpadded; payload_hash CHAR(64) lowercase hex; product_id/transaction_id INT nullable result FKs; response_status SMALLINT nullable 200–299; response_json LONGTEXT nullable JSON; created_at DATETIME(6). UNIQUE(actor_id,request_key) across kinds; status and response both NULL while claimed, both populated when completed. Domain/result/claim must commit together in Stage 8/9. |
| audit_events | event_id BIGINT AUTO_INCREMENT PK; actor_id INT nullable FK for system events; action VARCHAR(60) ASCII nonblank; entity_type VARCHAR(40) ASCII nonblank; entity_id BIGINT nullable positive generic entity identifier (not a polymorphic FK); before_json/after_json LONGTEXT nullable JSON; occurred_at DATETIME(6); request_id VARCHAR(128) ASCII nonblank. Bootstrap/seed audits contain only safe metadata. |
| rate_limits | limiter_key CHAR(64) ASCII lowercase hash PK; window_started_at/reset_at DATETIME(6), reset strictly later; attempts INT nonnegative default 0. Hash must incorporate the later defined scope; persistent counters/policy and expiry cleanup belong to Stage 6. |
| security_locks | lock_id INT PK, CHECK lock_id=1; migration inserts exactly one row. Lock with FOR UPDATE before bootstrap/account administration/recovery counting/writes. No automatic recreation if missing. |
| schema_migrations | migration_id VARCHAR(100) ASCII PK; checksum CHAR(64) ASCII; status VARCHAR(10) ASCII running/applied; completed_statements INT nonnegative default 0; started_at DATETIME(6); applied_at DATETIME(6) nullable. Runner-owned metadata; no manual routine edits. |

Every FK has an explicit full named constraint and ON DELETE RESTRICT. Products reference categories/suppliers; transactions reference users/products/original movements; operation results reference users/products/transactions; audits reference users. Multiple NULL correction links are allowed; a non-NULL original has at most one reversal reference. Reversal relationship, original price, Admin authorization, no reversal-of-reversal and sufficient/active stock require the Stage 8 service, not just this FK. Movement/audit append-only policy is enforced by future service paths/runtime privilege design; direct administrative SQL remains privileged. No update/delete history endpoint exists.

## Indexes and intended reads

Primary keys, username/category/SKU unique indexes, actor/request unique indexes, unique correction link and automatic FK indexes support direct identities and references. Migration 003 adds:

| Index | Columns / purpose |
|---|---|
| idx_products_category_active | category_id,is_active,product_id; filtered availability |
| idx_products_supplier_active | supplier_id,is_active,product_id; authorized supplier filter |
| idx_products_active_name | is_active,product_name,product_id; active catalogue ordering |
| idx_transactions_date | transaction_date,transaction_id; chronological history |
| idx_transactions_product_date | product_id,transaction_date,transaction_id; per-product history |
| idx_transactions_actor_date | user_id,transaction_date,transaction_id; actor history |
| idx_audit_date | occurred_at,event_id; audit pagination |
| idx_audit_entity | entity_type,entity_id,event_id; entity audit |
| idx_rate_reset | reset_at; bounded expiry cleanup |
| idx_users_admin | role,is_active,user_id; account administration counts |

Actual MariaDB EXPLAIN confirms the per-product history index can be used. Fixtures are tiny and FORCE INDEX is used to verify eligibility, not benchmark planner selection. Final query implementations/data distributions need fresh EXPLAIN review in their owning stages; no performance claim is made.

## Bootstrap, development seeds and reconciliation

AdminBootstrap normalizes/bounds identity, hashes a 12–72-byte valid UTF-8 password via PHP PASSWORD_DEFAULT (rejecting NUL and bcrypt truncation), locks the security singleton, requires no existing accounts, inserts active Admin/session_version=1/must_change_password=1 and safe audit in one transaction. Audit failure leaves no account. Rerun refuses existing accounts without changing passwords or data. It is initial provisioning only, not account recovery. The CLI accepts secrets solely through stdin; the Windows wrapper prompts twice with hidden input. Do not bootstrap alongside seeds: use one route for a fresh installation. Authentication and forced-change enforcement are implemented in Stage 6; recovery/last-Admin administration remain Stage 13.

DevelopmentSeeder requires local/test, loopback and empty domain/support data; it never upserts/replaces/truncates existing records. Three development roles receive independently random hashed passwords, with forced change. The CLI creates a private ignored credential file and persists credentials before committing seeds; failure aborts the transaction. A safe audit marker makes reruns no-ops without password rotation. All accounts/lookups/products/opening IN/OUT, balances, versions and movement replay records commit together. It now calls the shared StockMovementService inside its caller-owned transaction; forced-change flags become visible only on commit. Existing seed reruns remain no-ops. No production or HTTP writer calls it.

Fixtures: DEV-PAPER opening 20 minus OUT 3 = 17 at 245.00; DEV-PENS opening 10 = 10 at 12.50 and threshold 10; DEV-FOLDERS = 0 with no movements at 8.00. Includes populated, threshold-equality and no-movement states. No default production password exists. [Fixture source](../database/seeds/development.php) has no credentials. See [setup commands](../README.md).

StockReconciliation uses a REPEATABLE READ, READ ONLY consistent snapshot, LEFT JOIN/COALESCE for no movements and DECIMAL(30,0) movement sums. It includes archived products, reports every drift and writes nothing. CLI exits 0 on balance, 1 on drift, 2 on command failure. Actual disposable drift is detected without repair, while local seeds reconcile. Stock service races/retries/reversal/archive and opening-stock integration are now verified on local MariaDB in Stages 8–12; MySQL verification remains pending. Authentication is now implemented in Stage 6.

## Stage 7 use of existing lookup tables

Stage 7 adds no schema/migration changes: LookupRepository/LookupService implement category/supplier CRUD on these tables, reusing uq_categories_name and product RESTRICT foreign keys. Parent rows are locked and active/archived references refused; real HTTP checks verify no orphan/cascade or stock/history side effects. category/supplier created/updated/deleted events append safe before/after metadata in the same transaction; forced audit failures roll back parent mutations. See [lookup rules](lookups.md) and [Stage 7 evidence](progress.md). MySQL 8.4 and production HTTPS remain pending.

## Stage 6 use of existing supporting tables

No schema or applied migration changed. AuthRepository reads users with native prepared queries; password changes lock users, update hash/session_version/must_change_password and append safe account.password_changed audit metadata in one transaction. Audit failure rolls back all user changes. RateLimitRepository atomically inserts/locks/resets/increments rate_limits buckets; CEIL/TIMESTAMPDIFF/DATE_ADD and portable upsert behavior are verified on actual MariaDB 10.4.32, with MySQL 8.4 execution still pending. Bucket keys are SHA-256 namespaced account/peer identifiers; plaintext secrets/identifiers are not stored there. Sessions use private PHP file storage, not another table. Forced-change/session-version behavior and persistent multi-worker throttles are verified; Admin recovery/last-Admin administration remain Stage 13. See authentication.md and progress.md.

## Stages 8–12 use of existing tables

No new migration or applied SQL/checksum change was needed: the three tracked migrations and ten existing tables support these stages. Product creation inserts at zero; StockMovementService is now the only production writer of quantity, inserts opening/normal/reversal movements and increments product version. ProductRepository updates allowlisted metadata/active flag only. Expected-version checks and the shared product lock protect edits/zero archive. Historical rows/FKs are retained.

operation_requests now stores product.create and movement reservations, global actor/key/hash and canonical original 201 response snapshots. Existing development.movement seed records and all installed data remain untouched. Actor locking serializes claims; product-create response and opening transaction reference complete together with product.created audit. Product.updated/product.archived events record safe metadata atomically. Forced audit/insert failures roll back all effects. Full reversal uses original captured price and unique correction link; current locking checks prevent races.

Dashboard/report services read consistent read-only snapshots; ProductRepository/HistoryRepository share filters across UI/JSON/CSV. Wide DECIMAL aggregates retain exact strings. Existing FK/unique/check constraints and actual two-process races are verified on local MariaDB. Future production grants, deployment/restore and actual MySQL 8.4 execution remain pending. See business-workflows.md, stock-integrity.md and progress.md for limits and acceptance evidence.

The [pre-Stage-13 review](stages-8-12-review.md) reran schema/constraint acceptance and strengthened post-insert rollback, reason/insufficient/archived reversal, concurrent changed-key and original-row immutability evidence. No DDL or applied migration changed. All ten installed tables and private credentials were hash-compared before/after testing; installed balances remain 17/10/0. MySQL 8.4 and production HTTPS remain pending.

## Stages 13-15 schema and operational verification

No new tables or changes to applied migrations. UserService locks the existing security_locks singleton before actor/target rows, serializing last-active-Admin removal; account edits/reset atomically increment session_version and append secret-free audit_events. Deactivation preserves history foreign keys. Admin audit queries are read-only positive projections.

Native MariaDB backup/restore rehearsal restores all ten tables exactly, including password hashes, idempotency and audit records; restored migration rerun and stock reconciliation pass. Separate runtime privileges allow required stock/account/audit inserts and prohibit history UPDATE/audit DELETE. See [deployment runbook](deployment.md). MySQL 8.4 migration, restore, locking and grants remain pending; MariaDB 10.4.32 is local development only.

## Authorized private images (2026-10-12)
Migration 004_private_images.sql adds users.profile_image and products.product_image, nullable ASCII binary VARCHAR(37) generated WebP basenames. No existing column values, foreign keys or quantities change; ten tables/four migrations. Services never return these references. Role projections add only has_image. [Storage and transaction design](private-images.md) documents private files, concurrent replacement and backup implications. MySQL 8.4 DDL/runtime verification remains pending.
