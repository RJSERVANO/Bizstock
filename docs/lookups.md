# Stage 7 categories and suppliers

Real screens: /categories and /suppliers (prepend APP_BASE_PATH). Account navigation links to the permitted screens, including after initial password change. Admin may create/edit/delete; Staff reads both lists; Viewer reads category ID/name only and cannot access supplier screens/APIs. Anonymous pages redirect to login and forced-change pages to account. API authorization is checked before fields/filters are parsed, and every mutation validates CSRF. No public signup or role selector.

Eight implemented operations under /api/v1:

| Route | Method | Behavior |
|---|---|---|
| /categories | GET | All three roles; positive ID/name projection |
| /categories | POST | Admin create, 201 |
| /categories/{id} | PATCH / DELETE | Admin partial edit or reference-protected deletion |
| /suppliers | GET | Admin/Staff; ID/name/email/UTC creation timestamp |
| /suppliers | POST | Admin create, 201 |
| /suppliers/{id} | PATCH / DELETE | Admin partial edit or reference-protected deletion |

List query fields: q (name-only literal substring, maximum 100 characters), page/per_page (shared defaults/bounds), sort (entity ID or name) and direction asc/desc. SQL identifiers come only from trusted maps; native prepared values and explicit ! LIKE escaping make percent/underscore literal. Sorting includes an ID tie-break. Responses use data/meta with page/per_page/total/total_pages. Pages may naturally shift during concurrent edits; no cross-request snapshot is promised. Search is case/accent insensitive under the existing utf8mb4_unicode_ci collation.

Names use shared UTF-8/control-character validation and trimming; category 1–100 characters, supplier 1–150. Category names are unique using the database's case/accent-insensitive unique constraint; duplicates on create/edit return 409 DUPLICATE_CATEGORY without SQL or conflicting-record disclosure. Supplier names may repeat. Optional contact_email is a conventional validated email of at most 100 characters; omitted create/null/blank means no email, omitted PATCH retains it, explicit null/blank clears it. PATCH must contain at least one permitted field; unknown fields are rejected. JSON writes are bounded by shared parsing. DELETE accepts no body or an empty JSON object; unknown body/query fields are rejected. Missing update/delete IDs return 404.

LookupService owns one transaction for each mutation. It locks/rereads the active Admin and session version/forced flag, then locks the existing parent when applicable. Every product reference is checked, including archived and zero-stock products. Referenced deletion returns 409 REFERENCED_RECORD with instructions to reassign every product; it never reassigns, cascades, modifies stock or erases history. Existing RESTRICT foreign keys enforce the rule if a concurrent product reference races deletion. Product reassignment UI remains Stage 9; this stage deliberately refuses unsafe deletion.

AuditRepository appends category.created/updated/deleted or supplier.created/updated/deleted with server-derived actor/request ID and positive safe before/after metadata in the same transaction. Create has null before; delete null after. Failed validation/duplicates/references do not write audit events; failed audit insertion rolls back the parent change. No passwords/session state are audited. The Admin audit browsing API stays Stage 13; table events are real and tested now.

The responsive authenticated shell reuses tokens, fields, buttons, state panels, table and confirmation dialog plus the shared API client. Admin forms handle busy/errors/focus; deletion needs explicit confirmation. Staff/Viewer receive server-rendered read-only controls and appropriate navigation. Dynamic names/email/errors use textContent, never raw HTML. Lists have search/sort, bounded pagination, refresh, loading/empty/error/retry states, abort/generation handling for stale requests and session-expiry navigation. Writes are not automatically retried or presented as idempotent operations.

Run composer test:lookups with the selected PHP 8.5 runtime and local MariaDB running. The test creates/migrates its own random bizstock_stage7_<hex> database, uses private test administrator credentials, runs HTTP and installed Chrome acceptance, then removes only that database/session files. Existing installed accounts/credentials/catalogue/stock remain unchanged. See progress.md for actual assertions and regression evidence. MariaDB 10.4.32 is local only. MySQL 8.4 execution and production HTTPS/proxy/host verification remain pending. No migration was added or modified and Stage 8 stock services have not started.
