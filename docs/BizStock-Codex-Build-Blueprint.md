# BizStock: build blueprint and sequential Codex prompts

## 1. Final goal

Build a responsive, authenticated inventory website for one small business. Owners manage users, products, categories and suppliers. Inventory staff record stock movements. General employees see availability without changing data. Every movement updates persistent stock safely and remains traceable. Dashboard and reports use the same database. The release includes documented APIs, tests, installation instructions, backups and a verified deployment.

This is a build guide, not an already implemented website. Run the prompts below in your Codex repository, one stage at a time.

## 2. Requirements and decisions

The following specification is complete and self-contained. Codex should implement these decisions directly, using this guide as the project contract.

| Area | Implementation decision |
|---|---|
| User roles | Admin manages the business, Staff records stock movements, Viewer has read-only availability access. |
| Password storage | Store a one-way hash in `password_hash VARCHAR(255)`, never a plaintext or reversible password. |
| Stock changes | Use one atomic stock movement service. Ordinary product editing cannot change quantity. |
| Movement history | Keep movements append-only. No transaction edit/delete UI or API. Correct mistakes with a compensating IN/OUT movement linked to the original and a required reason; verify that the correction cannot create negative stock. |
| Product opening quantity | Create at zero; any opening stock becomes an IN movement in the same database transaction as product creation. |
| Low-stock boundary | Active product is low stock when quantity <= minimum_threshold, including zero. |
| Supplier/category deletion | Block deletion while referenced; require reassignment first. No cascading removal of history. |
| Users/products deletion | Deactivate using is_active. Deactivated users cannot retain access; archived products cannot receive movements. Historical reports still include their movements. Archive only at zero stock by default. |
| Category and supplier cardinality | Each product has exactly one category and one primary supplier. |
| Price meaning | Product price and movement unit_price are reference unit values. Reports must not label them profit, revenue or accounting valuation. |
| Tenant scope | One business, one database. Multi-business accounts are outside MVP. |
| Currency/time | Use Philippine peso (PHP), integer item quantities, UTC database timestamps and Asia/Manila display/report boundaries. Keep currency/timezone in central configuration. |
| API ready | Real versioned JSON endpoints, documented contracts and authorization, database-backed operations, automated checks and production configuration. Cookie sessions are sufficient for this same-origin website; third-party API tokens are later scope. |

### Access policy

| Capability | Admin | Staff | Viewer |
|---|---|---|---|
| Product availability/name/category/current quantity | Read | Read | Read |
| Dashboard inventory counts and low-stock list | Read | Read | Read |
| Prices and supplier details | Read/manage | Read | Hidden |
| Products/categories/suppliers | Manage | Read | Availability fields only |
| Record IN/OUT | Yes | Yes | No |
| Reverse an incorrect movement / audit log | Yes | No | No |
| History and inventory/transaction reports | Yes | Yes | No |
| User/role administration | Yes | No | No |

Enforce policy in backend routes and response fields, not just navigation. No public signup. Admin creates accounts. Protect the last active Admin from demotion/deactivation. Staff may view all business movement history by this default policy.

### Schema and backend improvement authority

Codex is authorized to improve tables, SQL, indexes, constraints, service boundaries and backend implementation when necessary to meet this specification. The five domain tables are a starting model, not a maximum table count. Add supporting tables for audit events, migrations, persistent rate limits or idempotency when justified. Keep the architecture a deployable modular monolith; avoid unnecessary microservices, queues or a second backend.

For every change, update migrations, schema docs, validation, services, OpenAPI, UI and relevant tests together. Preserve data and historical integrity. Prefer additive migrations; existing installations must upgrade without a reset. Maintain stable `/api/v1` behavior or explicitly document a versioned change. Never edit an applied migration to silently change production. Routine improvements within these rules are already authorized; hosting credentials and destructive production operations still require their actual prerequisites.

### Required gap review and closure

Maintain `docs/gaps.md`: gap ID, affected feature, severity, expected behavior, fix, owning stage, regression evidence and status. Audit after each stage and before release. Implement all in-scope release blockers; do not turn a missing required behavior into a future enhancement. Optional scope remains separate. Tests and honest evidence determine completion; this guide alone does not prove that a website is implemented or defect-free.

| Gap to close | Required implementation/evidence |
|---|---|
| Duplicate products/unclear identity | Required unique normalized SKU, immutable once movements exist; name need not be unique |
| Lost metadata edits | Version column and conditional updates; stale edits return 409 instead of overwriting |
| Archive/write race | Archive and movement paths share row-lock discipline; require zero balance before archive |
| Duplicate retries | Actor-scoped unique operation keys, payload hash and replay result; opening-stock product creation also idempotent |
| Corrections | Admin-only full reversal, opposite type/same quantity and original unit_price, required reason; unique reversal link prevents repeated reversal, both rows remain immutable. Create a fresh corrected movement afterward if needed. Reject reversal when stock is insufficient or product archived |
| Missing audit trail | Append-only audit_events for metadata changes, archives, role/account changes and password resets; actor, action, entity, safe before/after values, timestamp/request ID; never store secrets. Domain change and audit insert atomic |
| Stock drift | Read-only reconciliation command compares cached quantity with SUM(IN)-SUM(OUT), reports discrepancies and exits nonzero; no silent stock repair |
| Account recovery | Secure Admin reset and CLI emergency-owner recovery, force password change at next login; invalidate old sessions with session_version |
| SQL/API abuse | Prepared statements, allowlisted sorting/fields, numeric bounds, decimal-safe arithmetic, bounded payloads/pages, no internal stack traces |
| Operational gaps | Locked dependencies, fresh install and upgrade checks, documented environment variables, real-database CI, backups/restore and migration-safe rollback |

## 3. Stack and architecture

Use HTML, CSS, vanilla JavaScript, Bootstrap, PHP, PDO and MySQL/InnoDB. This keeps the application straightforward to develop, inspect and deploy without adding a separate frontend framework or backend service. Use Composer for PHP autoloading and development tools. Use XAMPP locally only if its actual database/runtime versions match the supported target; some installations provide MariaDB rather than MySQL. Detect and document this difference. Do not assume SQL constraints behave identically.

Choose a currently supported PHP release and a host-supported MySQL release at setup time. Pin dependencies and commit lockfiles. Prefer a same-origin Apache/PHP deployment with MySQL; a static-only host cannot run this backend. Hosting provider selection and deployment credentials remain user decisions.

Separate HTTP controllers, services containing business rules, repositories containing SQL, and templates containing presentation. The browser calls `/api/v1` through one shared client; page routes render the app shell. One database transaction owns both the movement insert and quantity update. Do not also install a trigger that updates quantities a second time.

Suggested project layout (Codex may adapt it once in stage 1 and document why):

```text
AGENTS.md
README.md
composer.json / composer.lock
.env.example
public/
  index.php
  assets/css/tokens.css       # fonts, palette, spacing, radii, focus styles
  assets/css/components.css   # shared buttons, inputs, cards, tables, badges
  assets/css/layout.css       # sidebar, header, responsive layout
  assets/js/api-client.js     # fetch, errors, CSRF, session expiration
  assets/js/components.js     # reusable interactive controls
  assets/js/pages/            # feature-specific interaction
  assets/vendor/              # pinned Bootstrap assets if vendored
src/
  Http/Controllers/
  Http/Middleware/
  Services/
  Repositories/
  Support/
templates/
  layouts/app.php
  components/                # button, input, table, dialog, status, pagination
  pages/
config/
database/migrations/
database/seeds/
bin/                         # migration, bootstrap admin, maintenance commands
tests/unit/
tests/integration/
tests/e2e/
docs/
  requirements.md
  decisions.md
  architecture.md
  design-system.md
  gaps.md
  schema.md
  visual-review.md
  openapi.yaml
  progress.md
  test-plan.md
  user-testing.md
  deployment.md
  user-guide.md
```

Only `public/` is web-accessible. Secrets, source, migrations, backups, Composer development tools and documentation stay outside the document root.

## 4. Professional UI blueprint

Build a calm, readable business interface for adult business owners and inventory staff. Use a collapsible sidebar, clear page headings, compact summary cards, grouped forms and searchable inventory. The design below is complete; no external design file is required.

### Shared layout and screen composition

- Desktop sidebar: approximately 240px, BizStock wordmark, outline icons plus text, subtle active state, account/role at bottom. Optional compact rail retains accessible tooltips. Mobile uses a dismissible drawer with proper focus handling.
- Navigation: Dashboard, Products, Stock Movements, History, Reports; Admin also sees Categories, Suppliers, Users and Audit Log. Hide unavailable actions and enforce the same policy server-side. No role-switching demo control in production.
- Dashboard: three neutral metric cards for active products, total units and low-stock products; below, a larger low-stock panel alongside recent movements for authorized users. Viewer gets availability-only content. Quick actions are compact buttons, not oversized colorful tiles. Show last refresh time; omit invented percentages and pending-order totals.
- Inventory: page header and Add product action, then a white toolbar with search, category/status filters and permitted export. Default to a paginated table: SKU, name, category, quantity, threshold, stock status, role-permitted price/supplier and actions. Use current quantity and threshold as separate labeled values, not an ambiguous capacity meter. A compact card view is optional using the same API; one Admin-managed product image is now authorized by the scope amendment below.
- Product form: grouped Basic information and Supply/stock rules sections; two-column fields on desktop and one column on mobile. Category and supplier are relational selectors. SKU is unique; location is optional text. Price and threshold are editable metadata. Opening quantity is allowed only on create; current quantity is read-only afterward. Save/cancel controls have consistent placement; warn about unsaved edits.
- Stock movements: searchable product selector on the left, movement details and confirmation summary on the right; stack on mobile. MVP submits one product per operation. Include IN/OUT, quantity, available stock, reason/reference and correction mode. No batch cart, recipient directory or camera/barcode features in MVP.
- History: product/type/actor/date filters, summary of matching movements and a compact detailed table with immutable movement ID, product, actor, quantity, historical unit price, timestamp and correction link. Do not label stock movements purchase orders.
- Reports: report/date/filter toolbar, useful summaries and results table; CSV export and print-friendly browser view. Optional simple category quantity and daily IN/OUT charts may be added only with real queries, defined metrics and table equivalents. No revenue, turnover or historical valuation graph without the required accounting/snapshot data.
- Users: table of name, username, role and active state; create/edit/deactivate controls; secondary panel explaining the three roles. Small account summary rather than a large decorative banner. No self-selected role or simulated login state.
- Lookup pages, audit log, login and account: use the same shared fields, buttons, cards and table patterns. Login is focused and uncluttered. No dead controls or advertised unsupported features.

### Design tokens and component standard

Apply these tokens consistently through shared styles and components.

| Element | Target |
|---|---|
| Palette | Canvas #F8FAFC, surface #FFFFFF, text #0F172A, muted text #475569, border #E2E8F0, primary #1D4ED8, primary hover #1E40AF |
| Navigation | Approximately 240px sidebar, compact brand mark, short text labels and consistent outline icons; clear active state using subtle blue background and dark blue text |
| Typography | One system sans-serif stack; optional self-hosted Inter. Body 16px, tables 14px, section headings 20px, page titles 28px, weights 400/500/600. Tabular numerals for quantities and amounts |
| Spacing | Shared 4/8/12/16/24/32px scale; desktop content padding 24–32px, mobile 16px; align headings, filters and table edges |
| Components | 8px radii, thin borders, minimal shadows, 40–44px control heights; comfortable table rows around 48px; preserve touch accessibility |
| Actions | One clear primary action per page, descriptive text such as Add product or Record movement; secondary actions outlined or quiet; danger styling reserved for destructive actions |
| Status | Low stock: #92400E text on #FEF3C7; out of stock: #991B1B on #FEE2E2; in stock: #166534 on #DCFCE7. Include labels and check contrast in actual rendering |
| Dashboard | Three useful metric cards, a prominent low-stock section and authorized recent movements; avoid ornamental charts or unsupported sales metrics |
| Tables | Compact labeled toolbar, search/filter controls, clear headers and sort state, right-aligned numerics, subtle row hover, accessible action menu and pagination |
| Motion | Brief functional transitions around 120–180ms, reduced-motion support; avoid decorative animations |

Avoid toy imagery, playful display fonts, multicolored cards, large gradients, excessive rounding and emoji navigation. Use neutral business language and general product examples. Required values, status and actions must be easy to scan. Check palette combinations rather than assuming these tokens alone establish accessibility.

### Responsive behavior and quality standard

Follow the screen composition above. Adapt it deliberately for tablet/mobile rather than scaling a desktop screenshot down. Collapse navigation, stack filters/forms/cards as needed and contain table scrolling. Keep quantities and essential actions discoverable; never clip the whole page horizontally. Check 360, 768 and 1440px viewports and intermediate widths.

Target WCAG 2.2 AA: keyboard access, visible and unobscured focus, readable contrast, labels, announced errors/status, sensible focus order, usable target sizes and reduced-motion support. Improve any inaccessible component and document the change. Stock status must use text as well as color. Right-align numbers, use consistent currency/date formatting and sortable columns with clear state where sorting is supported.

Every data screen handles loading, empty, error, populated and permission-limited states. Buttons share primary/secondary/danger/ghost variants and normal/hover/focus/disabled/busy states. Reuse confirmations, pagination, badges, alerts and empty states.

### Visual review checkpoint

In Stage 3 build the shell, login, dashboard, inventory table and product form using clearly labeled fixtures. Capture screenshots at 360/768/1440px. Review spacing, hierarchy, contrast, table density, controls and all loading/empty/error states against this section. Record results in docs/visual-review.md and fix material defects before extending the design. Future screens must reuse the same components.

## 5. Data blueprint

| Table | Fields and implementation rules |
|---|---|
| users | user_id INT PK, full_name VARCHAR(100), username VARCHAR(50) UNIQUE, password_hash VARCHAR(255), role Admin/Staff/Viewer, is_active default true, created_at, updated_at, session_version INT default 1, must_change_password BOOLEAN. Never return password_hash. |
| categories | category_id INT PK, category_name VARCHAR(100) UNIQUE NOT NULL. |
| suppliers | supplier_id INT PK, supplier_name VARCHAR(150) NOT NULL, contact_email VARCHAR(100) nullable, created_at. |
| products | product_id INT PK, sku VARCHAR(64) UNIQUE NOT NULL, product_name VARCHAR(150), location VARCHAR(100) nullable, version INT default 1, created_at, updated_at, description TEXT nullable, required category_id and supplier_id FK, price DECIMAL(10,2), quantity INT default 0, minimum_threshold INT default 10, is_active default true. |
| transactions | transaction_id INT PK, product_id/user_id FK, type IN/OUT, positive quantity INT, unit_price DECIMAL(10,2), transaction_date timestamp. Include a unique request key scoped to the actor, a payload fingerprint for safe retries, nullable correction_of_transaction_id self-reference and notes. Make correction_of_transaction_id unique when non-null; corrections follow the Admin-only reversal rule above. |

Use InnoDB, utf8mb4, named constraints, nonnegative prices/stock/thresholds and positive movement quantity. Validate server-side even when database CHECK constraints exist. Verify actual target-version enforcement. Reference parent records with RESTRICT deletion. Add indexes based on history/date/product filters and foreign keys; explain query plans instead of promising speed without evidence.

Stock write sequence: authenticate and authorize → validate → begin transaction → handle idempotency key → lock active product row by primary key → validate available stock → capture current price (original price for a reversal) on server → insert movement and update quantity → commit → return refreshed stock. Any failure rolls back both operations. Enforce duplicate requests through a database uniqueness rule, with same key/different payload returning conflict. Handle duplicate races without double counting.

## 6. API contract blueprint

| Routes under /api/v1 | Purpose |
|---|---|
| GET /auth/csrf; POST /auth/login; POST /auth/logout; GET /auth/me | Session lifecycle and CSRF bootstrap; login also has CSRF protection. |
| PATCH /account/password | Current user changes password with current-password verification; forced reset sessions can access only this and logout. |
| GET /dashboard | Role-filtered database metrics and low-stock summary. |
| GET/POST /products; GET/PATCH/DELETE /products/{id} | Lists/details/create/metadata update/archive. DELETE means soft deactivate, documented. |
| GET/POST /categories; PATCH/DELETE /categories/{id} | Admin writes; reference-protected delete. |
| GET/POST /suppliers; PATCH/DELETE /suppliers/{id} | Admin writes; reference-protected delete. |
| GET/POST /transactions; GET /transactions/{id} | Authorized history and movement recording; no edit/delete. |
| GET /reports/inventory; GET /reports/transactions | Filtered JSON or CSV using one shared query/filter specification. |
| GET/POST /users; PATCH/DELETE /users/{id} | Admin accounts, role changes and deactivation. |
| GET /audit-events | Admin-only paginated audit trail. |
| GET /health | Minimal liveness without secrets; restricted readiness checks separately. |

Use success `{data, meta}` and error `{error: {code, message, fields?}, request_id}` envelopes. Lists support bounded page/page_size, allowlisted sorts and filters. Define 200/201/204, 400, 401, 403, 404, 409, 422, 429 and 500 where relevant. Represent decimal currency consistently as strings in JSON. Standardize timestamp formats. CSV responses have documented content type and download headers instead of JSON envelopes.

Use same-origin session cookies, no secrets or tokens in localStorage. Validate CSRF for mutations, including login/logout. Production cookies are Secure, HttpOnly and SameSite; use narrowly scoped local-development exceptions for HTTP. Disable cross-origin access by default. If later adding external API clients, design their authentication separately.

## 7. How to use the prompts

1. Create/open a Git repository in Codex. Save this guide as `docs/BizStock-Codex-Build-Blueprint.md`. This is the complete specification Codex needs to start.
2. Paste the master instruction once, then run stage 1.
3. Run one numbered stage at a time. Do not paste the whole guide as one implementation request.
4. Read Codex's summary and run/demo the acceptance check before continuing. A blocked credential or hosting step is not a passed stage.
5. Commit each verified stage, if Git operations are authorized and the workspace is clean enough to isolate the changes. Never overwrite someone else's work.

### First message to send Codex

```text
Read docs/BizStock-Codex-Build-Blueprint.md completely, including the master instruction. Treat it as the project specification. Inspect the repository and follow existing AGENTS.md instructions. Implement Stage 1 only: requirements, architecture, decisions, gap audit and progress checklist. You may improve SQL/schema/backend design under the blueprint's improvement rules. Do not build later-stage features yet. Verify the Stage 1 outputs, report completed work and blockers, and stop after Stage 1.
```

After Stage 1 is complete, send: `Implement Stage 2 from the blueprint, run its checks, update progress and the gap register, and stop after Stage 2.` Continue the same way for each subsequent stage. A new conversation should use the resume prompt in section 8.

### Master instruction — paste once

```text
You are implementing BizStock, a single-business inventory web application. Read docs/BizStock-Codex-Build-Blueprint.md as the complete project specification. Implement its feature scope, schema, design system, permission matrix and integrity rules.

Use PHP/PDO/MySQL/InnoDB, HTML/CSS/vanilla JavaScript and Bootstrap unless the repository already has an agreed stack. Inspect existing files and AGENTS.md first. Preserve unrelated work. Build only the stage I request. Reuse shared UI components, tokens, API client, validation and domain services; do not create a separate button style or database connection per page.

Enforce authorization in the backend and returned fields. Use password hashing, prepared queries, CSRF and secure sessions. Quantity changes must be atomic with an append-only movement record; no negative stock, duplicate submissions or history deletion. Do not use localStorage or mock data as production persistence. Label temporary fixtures clearly and remove them when wiring real endpoints.

You are authorized to improve SQL tables and backend internals under section 2. Keep migrations, API contracts, UI and tests synchronized. Maintain docs/gaps.md and close in-scope defects rather than deferring them.

For every stage: implement the work, run meaningful checks, repair failures, update docs/progress.md and report changed files, exact verification commands/results and remaining blockers. Never claim tests, migrations or deployment ran if they did not. Stop after this stage. Ask only when missing information changes scope or makes the requested action impossible; document routine assumptions. Do not publish, purchase services or use production secrets until the deployment stage is explicitly requested with a selected target.
```

### Stage 1 — requirements, architecture and gap audit

Goal: an actionable specification before writing application code.

```text
Implement stage 1: specification only. Inspect the repository and read docs/BizStock-Codex-Build-Blueprint.md. Create docs/requirements.md with requirement IDs, acceptance criteria and the specified role-permission matrix. Create docs/decisions.md recording the chosen architecture and business rules: Admin/Staff/Viewer, password_hash, suppliers, immutable movements, linked compensating corrections, opening stock, soft deletion, reference-protected deletion, currency/timezone and one-business scope. Create docs/architecture.md and docs/progress.md with the numbered stage checklist. Establish MVP exclusions: POS/payment integration, purchase orders, manufacturing/bill of materials, camera capture (photo uploads now authorized by the scope amendment below), automated reordering, barcode scanning, multiple warehouses/businesses, forecasting and email alerts. Create docs/gaps.md covering every closure rule in section 2 and assign its owning stage. Do not implement features yet. Resolve routine design choices using this guide; report only material conflicts with the existing repository.
```

Done when: every required feature maps to an acceptance criterion; the schema, permissions and business rules are internally consistent.

### Stage 2 — repository and runnable foundation

Goal: reproducible development environment and clean separation of files.

```text
Implement stage 2 using the agreed architecture. Detect installed PHP/database tools, distinguish MySQL from MariaDB, select compatible supported versions and record them. Create Composer autoloading, locked dependencies, front-controller routing, configuration loading, one PDO factory, exception handling and development scripts. Create .env.example with placeholders, .gitignore, README setup instructions and AGENTS.md with the project rules. Expose only public/ as document root; support a configurable base path for XAMPP subdirectory use and root deployment. Vendor/pin Bootstrap assets or document the reproducible asset installation. Add a minimal page and health endpoint. Do not add business features yet. Verify a fresh setup, syntax and routing; report unavailable tools honestly.
```

Done when: a new checkout can install and render without secrets committed or internal files exposed.

### Stage 3 — shared design system and shell

Goal: consistent fonts, buttons, inputs and navigation before feature screens multiply.

```text
Implement stage 3 using section 4 as the complete UI specification. Build tokens.css, components.css and layout.css, reusable PHP components and shared JavaScript interactions. Adapt Bootstrap to the neutral slate/white palette and restrained blue accent. Implement the sidebar, login, dashboard, inventory table and grouped product form with clearly labeled fixtures. Add shared buttons, fields, dialogs, badges, alerts, pagination and all loading/empty/error states. Create a development-only component gallery. Use professional language, readable typography and compact useful controls; no decorative gradients, oversized tiles or role-switching demo. Check keyboard/focus, contrast and responsive layouts at 360/768/1440px. Capture screenshots, fix clipping/alignment/usability issues and record results in docs/visual-review.md. Document component APIs in docs/design-system.md.
```

Done when: shared components and prototype screens satisfy section 4 at all three viewport sizes, with documented visual checks and no material defects.

### Stage 4 — migrations and consistent seeds

Goal: reliable schema matching the latest requirements.

```text
Implement stage 4. Create ordered, tracked migrations for the five domain tables and justified supporting tables using the schema defined in this blueprint, including password_hash, all three roles, idempotency fields and correction references. Enforce foreign keys, unique usernames/category names, proper decimals, valid roles/types and quantity rules, with server validation planned where version constraints differ. Add indexes for actual access patterns. Provide a migration runner, a safe CLI initial-admin creation command and development-only seeds. Generate passwords with PHP password_hash; never embed production/default credentials. Seed quantities and movement history consistently, including opening stock. Test fresh migration, rerun without duplication, valid relationships, invalid references and reconciliation. Destructive resets must be explicitly development-only. Include SKU/version/location, session invalidation/reset fields, audit events and persistence for retry/rate-limit rules. Document concrete column types, constraints, indexes and migration ordering in docs/schema.md. Test upgrade against existing disposable data as well as fresh installation. Update schema documentation.
```

Done when: fresh schema and seeds work on the actual target database, and seed stock reconciles.

### Stage 5 — API foundation and contracts

Goal: one predictable interface for every feature.

```text
Implement stage 5. Create versioned /api/v1 routing, JSON envelopes, typed error mapping, request IDs, shared validation, bounded pagination and allowlisted sorting. Create the shared browser api-client.js with JSON parsing, normalized errors, CSRF handling, credentials and session-expiry handling. Create docs/openapi.yaml covering every planned route, schemas, cookie/CSRF authentication, permissions, field visibility, filters, decimal/timestamp formats, errors, CSV downloads and idempotency header semantics. Mark planned endpoints as not implemented in docs/progress.md; do not create endpoints that return fake success. Add contract checks for existing foundation routes and malformed requests. Subsequent stages must update contracts alongside implementation.
```

Done when: response formats are stable and the client uses no scattered fetch/error logic.

### Stage 6 — login, sessions and authorization

Goal: real access control for three roles.

```text
Implement stage 6: login/logout/me/CSRF bootstrap, password change and reusable authorization middleware. Use password_hash/password_verify, regenerate session IDs after authentication, secure session settings, inactivity/absolute expiry, generic login errors and a server-side rate limiter that works across requests. Recheck user active status and role on protected requests so account changes invalidate access promptly. Enforce the agreed permission matrix including Viewer field restrictions. Build login/account screens with shared components. Add CSRF protection for all mutations including login/logout, output escaping and no-cache rules for authenticated responses. Test each role, unauthenticated access, forbidden direct API requests, wrong passwords, invalid CSRF, expired sessions and deactivated users. No public signup.
```

Done when: unauthorized writes fail even when a user bypasses the UI.

### Stage 7 — categories and suppliers

Goal: dependable lookup data before product creation.

```text
Implement stage 7: real category and supplier list/create/update/delete endpoints, repositories, services and screens. Admin writes; Staff reads as defined; Viewer cannot retrieve supplier data. Validate name lengths, category uniqueness and nullable email. Block deletion while referenced and show a clear reassignment message; do not cascade. Add search/pagination where useful and shared form/dialog/error states. Persist changes after reload. Test authorized CRUD, denied writes, duplicate category, invalid email and referenced deletion. Update OpenAPI and progress.
```

Done when: parents can be managed without orphaning products or history.

### Stage 8 — atomic stock service

Goal: establish the highest-risk business rule before product/stock screens depend on it.

```text
Implement stage 8: one StockMovementService and POST /transactions plus read endpoints using actual MySQL/InnoDB. Validate positive integer quantity, IN/OUT, active product, active authorized actor and idempotency key. Apply the Admin-only full-reversal rule in section 2, including original price, same product/quantity, opposite type, unique correction link and required reason. Within one database transaction lock the product, enforce stock availability, capture the server-side current price for normal movements or original price for reversals, insert immutable movement and update quantity. Handle same-key retries without double updates and reject same-key/different-payload conflicts through database-backed uniqueness. Define concurrent retry/deadlock behavior. Never trust client actor, unit_price, timestamp or stock total. Do not use quantity triggers in addition to this service. Build integration tests for IN/OUT, rollback on failure, insufficient stock, inactive product, price history, duplicates and two separate concurrent database connections competing for limited stock. Implement and test a read-only stock reconciliation command. Test reversal races, archive races, numerical overflow and bounded deadlock retries. Explain how zero-stock creation will call this service in stage 9 without nested transaction commits.
```

Done when: two requests cannot oversell; failed/duplicate requests cannot change stock partially.

### Stage 9 — product management

Goal: usable inventory catalogue connected to real persistence.

```text
Implement stage 9: product list/detail/create/edit/archive endpoints and screens. Include category, supplier, description, decimal price, minimum threshold, quantity and stock status. Admin creates/edits metadata; Staff reads; Viewer sees only permitted availability fields. Search and filter server-side by category, supplier where allowed, status and active/archive state. Product creation starts at zero and any opening quantity is recorded by StockMovementService atomically with creation. Ordinary PATCH cannot alter quantity; reject that field. Archive only at zero stock by default, preserve history and prevent new movements. Use shared tables, forms, pagination and confirmations. Test persistence, invalid foreign keys, nonnegative price/threshold, forbidden fields, opening balance and archive behavior. Implement unique SKU, optional location, stale-edit conflict detection, actor-scoped idempotent product creation and atomic metadata audit events. Reconcile concurrent archive/write behavior. Remove catalogue fixtures.
```

Done when: product editing cannot silently bypass inventory history.

### Stage 10 — stock movement UI and history

Goal: staff can complete daily work safely.

```text
Implement stage 10. Build the IN/OUT screen with product selection, current availability, positive quantity, type and optional note. Include an Admin-only full-reversal mode that selects an original movement, requires a reason and submits a compensating movement through the same service; show both linked records in history. Show a confirmation summary and clear success/error messages. Use the shared API client and one idempotency key per intended operation; retain it for retry after uncertain network failure and create a new key only for a new movement. Disable submission while pending but rely on server protections for correctness. Refresh authoritative quantities after success/conflict. Build paginated history with product/type/actor/date filters and immutable detail view including price snapshot. Preserve history for inactive actors/products. No movement edit/delete buttons. Test the complete UI flow, double-click, reconnect/retry and stale availability.
```

Done when: movements are easy to record and a repeated click does not count twice.

### Stage 11 — live dashboard and low-stock alerts

Goal: a trustworthy inventory overview.

```text
Implement stage 11. Build database-backed total active products, total active item quantity and low-stock count/list using quantity <= minimum_threshold. Show recent movements only to authorized roles. Viewer receives no hidden supplier/pricing/history fields in the API. Link cards/alerts to matching product filters. Distinguish zero stock and low stock with text plus color. Define freshness: refresh after mutations and on page revisit/manual refresh; polling is optional and must be documented. Handle loading, no products and failures. Verify threshold equality, zero, archived exclusion and consistency with products after movements. Remove dashboard fixtures.
```

Done when: dashboard counts and product availability agree after changes.

### Stage 12 — reports and CSV exports

Goal: useful reports derived from persistent records.

```text
Implement stage 12: inventory snapshot and transaction history reports for Admin/Staff, with product/category/type/actor/date filters where applicable. Use shared filtering/query logic for JSON, screen and CSV. Interpret report dates in Asia/Manila then convert to UTC half-open ranges. Include historical transactions for archived products/inactive users. Inventory snapshot is current active stock; clearly label scope/time. Transaction amounts use captured unit_price, not current product price, and are reference movement values, not profit/revenue. Protect exports from spreadsheet formula injection in text fields, enforce permissions and keep secrets out. Bound or stream large exports and document limits. Test screen/export parity, date boundaries, archived history, price changes, CSV escaping and denied Viewer export.
```

Done when: exported rows match the same filtered data shown in the app.

### Stage 13 — Admin user management

Goal: owners can safely manage access.

```text
Implement stage 13: Admin user list/create/update/role change/deactivate and admin password reset through a documented secure workflow. No public signup, password/hash responses or plaintext logs. Define initial password delivery as a manual owner workflow; do not send email without authorization. Validate unique username, strong password policy and permitted roles. Block removal/demotion of the last active Admin, including concurrent requests using a database transaction/serialization strategy. Preserve user foreign keys and historical movements. Apply role/deactivation changes to existing sessions on next protected request. Implement session_version invalidation, forced change after reset, atomic audit events and Admin audit log. Document CLI emergency recovery and test reset-session restrictions. Build shared-component screens and test all roles, last-admin protection, duplicate usernames and inactive-user sessions.
```

Done when: access changes work without deleting accountability or locking out all owners.

### Stage 14 — integration, accessibility and user testing

Goal: prove the full system works, not just separate pages.

```text
Implement stage 14. Run the appropriate syntax/lint, unit, real-database integration and browser end-to-end checks. Cover Admin creates lookups/product → records opening stock → Staff records IN/OUT → Viewer checks availability → Admin reviews low-stock and exports history. Include direct unauthorized API attempts, concurrent stock-out, duplicate retries, rollback, historical price and fresh installation. Check keyboard operation, focus/dialog behavior, labels/errors, contrast, 360/768/1440px layouts and long tables/names against the WCAG 2.2 AA target. Compare final screens against section 4 and the Stage 3 baseline; repair visual drift and record results in docs/visual-review.md. Replace all production mock/localStorage data and dead controls. Create docs/test-plan.md with actual results and docs/user-testing.md containing task scripts for owner/staff/viewer, time/errors/completion measures and an improvement log. Do not invent participant feedback; mark real user sessions pending until performed. Audit every requirement and docs/gaps.md; implement missing in-scope behavior, run regression checks and close every release blocker with evidence. Reconciliation must pass. Map requirement IDs to evidence.
```

Done when: automated checks pass and real user testing is honestly recorded or clearly pending.

### Stage 15 — deployment preparation

Goal: reproducible release package and an operational runbook.

```text
Implement stage 15 without publishing yet. Identify the selected host or document the unresolved choice; require PHP and MySQL support, public document root configuration and HTTPS. Create production configuration guidance, .env.example, migration/bootstrap commands, CI checks and docs/deployment.md. Document environment variables, database privileges, session storage, writable paths, disabled display_errors, protected logs/request IDs, backup schedule/retention, restore procedure, health monitoring and dependency update process. Keep source/secrets/backups outside public/. Provide safe release steps, database backup before migrations, migration compatibility and rollback strategy; code rollback alone is not database rollback. Rehearse clean installation and backup/restore against disposable data. Create docs/user-guide.md and list remaining external credentials/host tasks. Provide a predictable release flow: install locked production dependencies, load target environment, back up database, apply tracked migrations, bootstrap owner when needed and run smoke checks. Provide Apache rewrite/document-root examples and a local development start command. Optional Docker Compose is a reproducible development alternative, not a production hosting prerequisite. Do not claim deploy-ready if critical tests or runtime support are unresolved.
```

Done when: the release can be installed from a clean checkout and disposable backup restore has been verified.

### Stage 16 — deploy and verify the real target

Goal: a working release with persistent data and verified operational setup.

```text
Implement stage 16 on the explicitly selected deployment target using authorized credentials. First verify the release commit, passing checks, target PHP/database versions, HTTPS, document root and backup. If a target or credential is missing, finish every independent preparation and report exactly what is missing; do not pretend deployment occurred. Deploy the release, apply migrations safely, create the initial Admin through the secure CLI and configure secrets without logging them. Do not install demo accounts/data in production. Verify login, each role, product creation, atomic IN/OUT, threshold alerts, reports/export, denied endpoints, history persistence after reload and health checks. Use clearly identified disposable verification records with a documented cleanup policy preserving movements. Verify production cookie flags, hidden internal files and backup/monitoring configuration. Record URL, release commit, migration state, actual smoke-test results and remaining tasks in deployment/progress docs. If a critical check fails, follow the documented rollback plan and report the outcome.
```

Done when: actual production smoke checks pass; a URL alone is insufficient.

## 8. Recovery prompts

### Resume in a new Codex conversation

```text
Read AGENTS.md, docs/requirements.md, docs/decisions.md, docs/architecture.md and docs/progress.md. Inspect current files and Git changes. Summarize completed stages, verified results, unresolved blockers and the next incomplete stage. Do not restart or rewrite completed work. Implement stage [NUMBER] only, following the master rules and its acceptance checks.
```

### Repair a failed stage

```text
Stage [NUMBER] failed this acceptance check: [ACTUAL ERROR / REPRODUCTION]. Reproduce it, identify the root cause and make the smallest complete repair consistent with the architecture. Add a meaningful regression check for this failure. Run affected checks and update progress. Preserve unrelated work; do not redesign working modules.
```

### Shared-component cleanup

```text
Audit implemented pages for duplicate button/input/table/dialog styling, fonts, scattered fetch calls and repeated validation/business logic. Consolidate genuine duplicates into existing shared components/tokens/client/services without changing behavior. Check every affected screen/state and report the consolidation. Do not create a second design system.
```

## 9. Release gate and team ownership

- All specified features implemented and mapped to evidence.
- Professional shared design verified through screenshot review; usable mobile layout and accessibility checks completed.
- Database persistence; no production fixtures or simulated API success.
- Server-enforced roles and restricted response fields.
- Stock and movement insert atomic, concurrency safe and retry safe.
- Historical price and archived user/product references preserved.
- Real filtered reports and protected CSV export.
- Fresh setup, tests, actual user feedback and measured improvements documented.
- HTTPS, secrets, production permissions, backups/restore, monitoring and rollback verified.
- No critical unresolved issue labeled complete; every in-scope release blocker in docs/gaps.md closed with verification evidence.

Suggested team ownership: Member 1 owns architecture/API/stock/deployment; Member 2 owns tokens/components/screens/accessibility; Member 3 owns schema/seeds/test evidence/user testing/docs. Review the stock and permission changes together. Avoid simultaneous edits to the same shared files.

## 10. Technical references

These references support implementation choices; this blueprint defines the project scope. Check the documentation matching the versions actually installed.
- Bootstrap CSS variables: https://getbootstrap.com/docs/5.3/customize/css-variables/
- PHP password hashing: https://www.php.net/manual/en/function.password-hash.php
- PHP sessions: https://www.php.net/manual/en/book.session.php
- MySQL InnoDB isolation/locking behavior: https://dev.mysql.com/doc/refman/8.4/en/innodb-transaction-isolation-levels.html

Shared CSS variables support central UI tokens, PHP supplies password hashing/session primitives, and InnoDB transactional locking supports the stock service design. These primitives still require the application-level validation, permissions and tests described above.

## Authorized scope amendment ? 2026-10-12

The owner explicitly supersedes the previous photo/camera-upload exclusion for own-account JPEG/PNG/WebP profile photos and one Admin-managed product image, secure private storage/re-encoding/controlled access, default initials and placeholders, optional thumbnails/card view and locally remembered collapsible sidebar. Camera capture and other original exclusions remain outside scope. Preserve role restrictions, CSRF, stock integrity, data and credentials; no deployment.

## Authorized public homepage extension (2026-10-12)

The owner authorizes a public / homepage with original BizStock navy/teal branding, honest implemented-feature/workflow/role guidance, labeled illustrative previews and generated or verified commercially licensed local imagery. Public content must never expose installed inventory, accounts or uploaded images; business name and optional verified contact email are centrally configured. No public signup, role switcher, credential display, fake contact form or invented testimonials/statistics. One secure /login serves every role; presentation-only role links never determine permissions. Normal login opens the permitted dashboard; forced password change stays required first. Refine shared sidebar alignment and distinguish profile/Users/Audit icons, reusing existing collapse/drawer/photo/product-image/card features and migration 004. Verify base paths, responsive/browser/accessibility, all-role login/forced change, upload and inventory regressions, preservation and reconciliation. No new migration is required for public presentation. MySQL 8.4, production HTTPS and hosted image storage remain pending until actually tested. No deployment authorized.

## Authorized usability, activity and own-profile extension (2026-10-12)

The owner's pasted request authorizes a responsive split login using the existing disclosed stockroom illustration; accessible Admin/Staff/Viewer guidance selection and validated public role hints; password visibility controls; aligned History filters with reset and real correction links; clearer movement search/selection/availability and confirmation; active out-of-stock counts, 7/30-day Manila stock-unit activity, exact IN/OUT totals and role-permitted quick actions; and separate own display-name/photo/password controls with cancellable photo previews. Role guidance is not an authorization role switcher: it never submits a role, fills credentials, grants permissions or overrides forced password change. All activity comes from the complete immutable ledger, including opening stock and reversals, and is absent from Viewer responses. No invented financial statistics.

Own display-name changes require authentication, CSRF, validation and atomic safe audit; username, role and active status remain administrator-controlled. Reuse private image storage/service and migration 004. Existing columns/indexes suffice; no migration 005 or new stock writer. Installed role checks may use private initial credentials once, never printing passwords, resetting accounts or clearing forced-change flags. Changed initial credentials and unavailable databases are explicit verification blockers. Use isolated fixtures for changes, preserve installed rows/files/credentials and verify reconciliation where the real connection permits it. Explicitly test role screens, actions and direct URLs/API responses, with a PASS/FAIL/BLOCKED matrix based on actual executions. MySQL 8.4, production HTTPS, hosted storage and actual participant/manual AT remain pending. No deployment or Stage 16 work.

## Authorized dashboard/navigation refinement (2026-10-12)

The owner authorizes compact live dashboard heading/quick actions, first-five deterministic zero-first stock alerts and exact-product IN links, explicit filtered card links and quantity wording, shared-scale paired IN/OUT chart with accessible exact daily values, fixed full/rail sidebar with independently scrolling navigation and accessible tooltips/skip/mobile focus, and guarded embedded Users Back/Cancel/save/failure navigation with create-only Initial password. Reuse the existing PHP architecture, shared dirty-form guard and positive role projections. All writes/tests use isolated fixtures; preserve the recovered installed database, accounts/passwords/uploads/stock. No repair/reset/reseed of installed data, migration or deployment. Production and unrun manual checks stay pending. See docs/dashboard-navigation-review.md for actual evidence.

## Authorized login-reference revision (2026-10-12)

The owner authorizes a clean white login page with slim BizStock/Back to home header, centered illustration-left/form-right layout, NEW original locally optimized 3D inventory art (not reused/cropped/recolored homepage artwork), HTML headline/supporting copy, simple honest footer, compact presentation-only role controls and mobile form priority. Preserve all existing authentication/security/forced-change controls and completed History/movement/dashboard/account work. Verify actual renders, faults and real three-role authorization without resetting installed accounts. Unavailable installed database remains an explicit blocker; independent real-engine tests do not constitute recovery. No deployment.
