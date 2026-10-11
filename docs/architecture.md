# BizStock architecture

Current public extension: FoundationController renders templates/pages/home.php using Config/Ui. Anonymous home visits do not initiate sessions or DB access; a supplied session is checked through existing AuthService solely to select the CTA, never to inject private identity/inventory/photos. Public HTML is private/no-store. Shared role-cards.php and public.css serve homepage/login; normal login follows the actual forced-change flag to dashboard/account. Shared sidebar heading grid and original outline paths reuse the existing drawer/rail implementation. All URLs use central base-path generation. No new API, image service or migration. See [public design and evidence](public-homepage.md). Earlier foundation entries below are historical.

Stages 1-15 implement the full business/Admin API and shared screens, local system review and operational preparation. Stage 16 deployment, actual MySQL/HTTPS/CI/participant acceptance remain pending. Earlier checkpoints are historical. See [test evidence](test-plan.md), [Admin management](admin-management.md) and [deployment](deployment.md).

## Stage 7 implemented boundaries (historical checkpoint)

Application shares one lazy PdoFactory between auth and lookups. LookupController authorizes/parses HTTP and protected screens. LookupService validates names/email, locks/rereads Admin state, owns parent mutation/audit transactions and maps duplicate/reference failures to safe 409 errors. LookupRepository uses only allowlisted category/supplier identifiers and native prepared values; AuditRepository appends canonical positive metadata with actor/request ID. Existing unique/RESTRICT constraints protect races; no new or changed migrations or stock writers.

Eight lookup operations are live; OpenAPI totals 15 implemented and 17 planned. Lists use literal name search, trusted sorts with ID tie-break and bounded pagination; Viewer gets category ID/name and supplier denial. /categories and /suppliers reuse the authenticated shell, components/table/state/dialog and API client with actual CRUD, confirmation, busy/errors, pagination/reload persistence and read-only role controls. Real MariaDB/API/Chrome acceptance passed; MySQL and production HTTPS verification remain pending. Stage 8 has not started.

## Stage 6 implemented boundaries (historical checkpoint)

Application constructs one lazy PdoFactory for AuthService, SessionManager and AuthController. Controllers translate validated HTTP/pages; AuthService coordinates current-user checks and atomic password/audit transactions. AuthRepository and RateLimitRepository own prepared SQL; LoginRateLimiter owns persistent ordered-bucket transactions. SessionManager owns strict cookie/private-file storage, CSRF, rotation, expiry and destruction. Front controller closes sessions before sending responses, including errors.

Authorization supplies role allowlists and positive field projections. Planned handlers remain absent; gates authenticate, restrict forced-change sessions, check roles/CSRF and sensitive Viewer queries/Staff corrections before returning 404. Auth/health are seven implemented operations; 25 business operations stay planned. Feature stages must reuse policy and verify real returned data/query parity. The login/account layout reuses tokens/components/API client with escaped database identity; auth.js wires forms and session events. Previews remain isolated development fixtures.

Existing ten tables and three immutable migrations suffice. Password hash/version/forced flag and safe audit commit together; rate buckets persist across workers. PHP sessions reread users each protected request. Default idle/absolute limits are 30 minutes/8 hours; password changes retain the absolute deadline. Production HTTPS/Secure cookie enforcement is configured; proxy/TLS hosting and MySQL execution verification remain pending.

## Implemented foundation

Composer PSR-4 autoloading; config/bootstrap.php and Support/Config; one lazy Support/PdoFactory; Http/Application, Response, HttpException and ExceptionHandler; FoundationController; public/index.php and public/.htaccess; templates/pages/foundation.php; bin development/runtime/database scripts; actual HTTP foundation tests. / and GET/HEAD /api/v1/health work at root and configurable base paths. Stage 4 adds tracked schema, CLI AdminBootstrap/DevelopmentSeeder and StockReconciliation through the same factory. Business API endpoints, authentication, stock service and full client remain absent. Local runtime is PHP 8.5.11; MySQL 8.4 LTS is not available. See README for setup and Apache public-root/Alias examples; PHP built-in server is for loopback development only.

## Boundaries and planned layout

Stage 3 adds Support/Ui, PreviewController, templates/layouts/app.php and eight reusable templates/components, five page templates, shared tokens/components/layout CSS and vanilla JS interactions. Preview routes are server-gated to local/test; fixtures are selected/field-filtered in PreviewController, not a second database service. No real API client or production business route is introduced. Browser checks use locked Playwright/axe under tests/browser and write review artifacts outside public/. See [design system](design-system.md) and [visual review](visual-review.md).

Use the exact layout in blueprint section 3: public/index.php and public/assets/{css,js,vendor}; src/Http/{Controllers,Middleware}, src/{Services,Repositories,Support}; templates/{layouts,components,pages}; config/; database/{migrations,seeds}; bin/; tests/{unit,integration,e2e}; docs/. Composer files, .env.example, README and AGENTS.md are Stage 2 artifacts. Only public/ is document root; source, tools, docs, secrets, logs and backups remain outside it. Support root deployment and configurable XAMPP base path through central URL generation.

tests/browser/ is the documented adaptation of the planned browser/e2e folder, keeping Node-only test dependencies beside their locked manifest. No frontend build pipeline is required. The requested compatibility interlude adds explicit local-only MariaDB 10.4.32 support through Config/DatabasePlatform/PdoFactory and portable canonical JSON. Actual local capabilities are verified separately; production MySQL 8.4 and domain schema/service acceptance remain pending. See [database compatibility](database-compatibility.md) for collations, exact role/type checks, portable JSON and engine policy. No domain schema or API changed.

Page routes render templates/app shell. Shared browser api-client.js handles JSON/errors/CSRF/credentials/session expiry. /api/v1 middleware provides request IDs, authentication, current-account checks, authorization, CSRF and bounded input. Controllers translate HTTP; services own business transactions; repositories contain prepared SQL through one PDO factory; serializers enforce role field visibility. Templates escape output and reuse components/tokens. Repositories never commit caller-owned transactions.

Planned services: AuthService, AccountService, UserService, LookupService, ProductService, StockMovementService, AuditService, ReportQueryService and DashboardService. Shared validation, transaction retry and idempotency utilities live in Support. No per-page DB connection, scattered browser fetch, production mock data or quantity-changing trigger.

## Relational model (implemented in Stage 4)

Concrete SQL, storage/index/constraint details and migration safety are in [schema.md](schema.md). Actual MariaDB Stage 4 acceptance passed; MySQL verification is pending. The table outline below originates in Stage 1; schema.md is authoritative for concrete fields, including operation result columns, audit occurred_at and migration status/progress fields. Later server validation and domain services remain required.

| Table | Model outline; concrete fields and integrity are in schema.md |
|---|---|
| users | INT PK user_id; full_name VARCHAR(100), unique canonical username VARCHAR(50), password_hash VARCHAR(255), constrained Admin/Staff/Viewer role, is_active, created_at/updated_at DATETIME(6), positive session_version INT default 1, must_change_password BOOLEAN. |
| categories | INT PK category_id; unique nonempty category_name VARCHAR(100), explicit tested collation. |
| suppliers | INT PK supplier_id; supplier_name VARCHAR(150), nullable contact_email VARCHAR(100), created_at DATETIME(6). |
| products | INT PK product_id; canonical unique sku VARCHAR(64), product_name VARCHAR(150), location VARCHAR(100) nullable, description TEXT nullable, required category_id/supplier_id FKs, price DECIMAL(10,2), quantity INT default 0, minimum_threshold INT default 10, positive version INT default 1, is_active, UTC created_at/updated_at. Price/stock/threshold nonnegative. |
| transactions | INT PK transaction_id; product_id/user_id RESTRICT FKs; constrained IN/OUT type, positive INT quantity, nonnegative unit_price DECIMAL(10,2), transaction_date DATETIME(6), ASCII request_key VARCHAR(128), payload_hash CHAR(64), unique (user_id,request_key), nullable unique correction_of_transaction_id self-FK RESTRICT, nullable notes TEXT. Service enforces reversal relationships. Append-only. |
| operation_requests | BIGINT PK; actor_id FK, operation_kind, ASCII request_key VARCHAR(128), payload_hash CHAR(64), result entity IDs, response status and safe response JSON, created_at UTC; UNIQUE(actor_id,request_key). Insert/result/domain mutations commit together. No secrets in replay body. |
| audit_events | BIGINT PK; actor_id nullable FK for system events, action, entity_type/entity_id, safe before/after JSON, timestamp and request_id; append-only, no credentials/session tokens/raw passwords. |
| schema_migrations | Unique migration identifier, checksum, applied_at; ordered upgrades without reset, refuse changed applied checksums. |
| rate_limits | Unique hashed limiter scope/key, window/reset time, attempt count; atomic cross-request updates and bounded cleanup. No raw credentials; exact policy in Stage 6. |
| security_locks | Singleton fixed primary-key row for account administration/bootstrap/recovery serialization; lock before counting/changing active Admins. |

Foreign keys preserve historic product/actor/correction references. Supporting table types and constraints finalized with engine verification in Stage 4. Baseline indexes: products(category_id,is_active,product_id), products(supplier_id,is_active,product_id); transactions(transaction_date,transaction_id), (product_id,transaction_date,transaction_id), (user_id,transaction_date,transaction_id); audit_events(timestamp,event_id) and (entity_type,entity_id,event_id); unique request/correction/SKU indexes and necessary FK indexes. Actual filters/EXPLAIN in implementation determine further indexes; no performance claim yet.

## Transaction and concurrency design

1. Authenticate and authorize; validate bounded allowlisted fields. Derive actor from current session, never request data. Validate operation key and canonical payload hash.
2. Begin transaction; recheck/lock actor active status and required role for writes. Account management acquires security_locks first, then affected user rows in ID order. Domain writes lock actor before product; consistent ordering and bounded whole-transaction deadlock retries are required.
3. Claim operation_requests under unique actor/key. Concurrent duplicate insert waits for commit/rollback. On committed duplicate, compare kind/hash and return original result; different request conflicts. Replay requires current authentication/authorization, not stale access. A failed transaction leaves no completed operation. Store original stock response on replay; client refreshes current stock separately.
4. Lock product by PK with SELECT ... FOR UPDATE; verify active state, stock, numeric bounds and reversal eligibility. Read the immutable original movement without a write lock; after locking its product, use a current locking read for the unique reversal link. Normal movement snapshots current product price; reversal snapshots original unit_price. No client actor, price, time or stock trusted.
5. Insert immutable movement; update cached quantity/version and timestamp; record required audit event for associated domain change. Commit movement/quantity/idempotency result together. Any failure rolls back everything. No external effects inside retryable transaction.

ProductService owns product-creation transaction: claim creation key, create zero product, call StockMovementService internal method on same PDO/transaction for optional opening IN, insert audit/result, commit once. Opening movement uses the creation key/hash directly; there is one outer product.create reservation, not a second movement reservation. Its actor/key uniqueness and outer replay prevent reentry. StockMovementService public entry owns a transaction for standalone movements; internal method cannot begin/commit nested transactions.

Archive and metadata updates use the same product lock. Check supplied expected version after locking; stale returns 409. Archive requires zero and increments version. SKU changes check absence of movements under lock; price changes serialize with movement snapshots. Only service paths write stock. Unique correction link prevents reversal races; disallow reversing reversal rows. Full retry retries all work only after rollback; limit/deadlock exhaustion is a safe retryable error with same operation key, never partial success.

Implemented Stage 13 UserService and emergency recovery share the singleton security lock so concurrent demotion/deactivation cannot remove the last active Admin. Reset/deactivation/role change increment session_version and audit atomically; middleware rereads account state. Forced-change sessions allow password change/logout only (login supplies needed CSRF state). Never deliver initial/reset passwords through unauthorized messaging.

## Read contracts and operations

Through Stage 12, auth/lookups/products/stock/dashboard/report routes are implemented; six user-management/audit operations remain planned (Stage 13). Stage 2 implements minimal health public liveness only. JSON success/error foundation envelopes exist; full bounded pagination/allowlisted sort, decimal strings and UTC ISO timestamps are to be specified in Stage 5 OpenAPI. No fake-success endpoint. Future readiness is privileged. Supplier/history/report/audit access will be denied to Viewer; positive serializers must also remove hidden fields from nested records and metrics.

ReportService reuses ProductRepository/HistoryRepository normalized filters for JSON/CSV/print; Manila dates convert to UTC half-open ranges. Inventory snapshot is current active stock, historical movements retain archived/inactive references and immutable price. CSV neutralizes formula text and is bounded/streamed. Dashboard queries active data and uses quantity <= threshold, including zero.

Read-only reconciliation runs a consistent database snapshot, includes products with no movements using LEFT JOIN/COALESCE, calculates SUM(IN)-SUM(OUT) with safe aggregate width and exits nonzero on discrepancy. It never changes stock.

Stage 3 verifies shared UI against full section 4 tokens/composition/states at 360/768/1440px; later pages reuse it. Unit tests cover validation/policy; actual MySQL integration tests cover constraints/rollback/two-connection races; browser checks cover role workflows/accessibility. CI, install/upgrade and disposable backup restore precede authorized deployment. Production uses HTTPS, protected session/log storage, least DB privileges and locked dependencies. Back up before migration; code rollback alone cannot undo database migration.

## Stage 5 implemented boundaries (historical checkpoint)

ApiRouter/ApiInput and shared Response/ExceptionHandler provide versioned route/method/parameter matching, typed JSON errors, safe details, bounded request parsing/pagination and trusted sorting. At this historical Stage 5 checkpoint Application registered only health; later sections/progress describe implemented business/security services. public/assets/js/api-client.js is loaded by the shared layout with server-escaped APP_BASE_PATH and allowed by the development asset router. It performs same-origin transport, in-memory CSRF/error/session-expiry handling and explicit operation-key forwarding without automatic write retry. No fixture is converted into a fake authenticated client.

OpenAPI and api-foundation.md now specify the previously planned envelopes/filters/decimal/timestamp/cookie/CSRF/idempotency/CSV contracts with explicit implementation status. Only GET/HEAD health works; the 30 planned operations return 404. Actual browser and HTTP validation/client checks passed, while mock transport tests cover planned response handling only. Stage 6 and later service authorization remain unimplemented. No migrations, database content or API business success changed in Stage 5.

## Implemented Stage 12 architecture checkpoint

AtomicWrite locks/rechecks the actor and owns bounded transaction retries; StockMovementService owns movement/balance writes. Internal caller-owned primitives validate input and require a trusted locked/current actor, without nested commits. ProductService combines zero creation, optional opening, audit and durable replay in that outer transaction. ProductRepository has an explicit metadata-column allowlist; quantity is absent. OperationRepository reservations are global across operation kinds for each actor.

ReadSnapshot owns read-only repeatable-read consistent snapshots for DashboardService and ReportService. DashboardRepository aggregates active availability; ProductRepository and HistoryRepository supply shared prepared filters/order/pagination/export limits. ReportService handles CSV quoting, formula neutralization and byte bounds. BusinessPageController renders one live layout; shared PHP components and business.js list/options/state helpers reuse the one API client, controls and tokens. Local preview routes remain explicitly labeled development tools and are disabled in production; live pages contain no sample data.

All Stage 8–12 acceptance and complete-flow checks pass locally. MySQL 8.4/production HTTPS, Stage 13 administration/recovery/audit browsing and Stages 14–16 release obligations remain pending, not implemented success responses.

## Current Stage 15 architecture

All 32 documented API operations are implemented. Historical planned-route descriptions above describe earlier checkpoints. UserController/UserService/UserRepository and AuditLogRepository reuse authentication, CSRF, validation, pagination, serializers and the shared browser client/components. Sentinel-first account transactions protect last-Admin state and audit atomicity; emergency recovery is trusted stdin CLI only.

DatabaseBackup uses the same verified PDO/configuration boundary and explicit native clients, private temporary defaults files and secret-free process arguments. Backup is a consistent transactional dump; restore requires a separate empty target and trusted SQL. It does not provide an HTTP restore endpoint or mutate the existing database. Deployment uses a public-only document root, separate runtime/operator/backup credentials and private sessions/logs/backups. Actual production topology, MySQL/HTTPS and CI remain pending.

## Authorized sidebar/images extension
The current API has 38 implemented operations. ImageController/ImageService/ImageRepository reuse the existing factory, sessions, positive projections, atomic actor/owner locks and flag-only audit. Private re-encoded files accompany nullable owner references in additive migration 004; no stock-writer changes. [Design and recovery](private-images.md).
