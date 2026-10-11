# BizStock interface refinement

Reviewed 2026-10-11 to 2026-10-12, Asia/Manila. The supplied screenshots represented the existing interface. This work refines the implemented application and does not deploy it or advance Stage 16.

[Open the before/after comparison](visual/refinement/index.html). It compares twelve actual screens at 360, 768 and 1440 pixels, captured using isolated MariaDB inventory and accounts. [Before computed styles](visual/refinement/before/computed-styles.json) and [after computed styles](visual/refinement/after/computed-styles.json) record stylesheet URLs, fonts, colors, sizes and toolbar layout. These are test captures, not invented production metrics.

## Findings and changes

All four shared stylesheets loaded. Missing structural styling was the main issue: lookup search forms lacked a toolbar layout, pagination lacked spacing, product/movement sections lacked interior padding, and unrestricted Account used the standalone password card with duplicated navigation. Large form grids made filters dominate the page. Assets now have content-hash versions so refreshes load current shared styles/scripts.

- Shared slate/navy shell, grouped navigation, stronger wordmark, blue active state and compact native account disclosure with real sign-out and Escape/focus behavior.
- Explicit system typography, prominent headings, readable supporting/table text, white surfaces, restrained shadows and consistent radii. Dashboard icon backgrounds and values use real API metrics.
- Search/common filters remain visible; a keyboard-accessible native disclosure holds every other existing filter and sort. Product names/SKUs and Admin team names resolve to existing IDs through permitted APIs. Staff can choose names present in returned history, with ID filters retained for other records; no forbidden user-list request is made.
- Aligned numeric columns, clearer headers, subtle hover, consistent row controls and spaced pagination; mobile stock summaries and scrollable tables remain intact.
- Padded product sections and movement details, a desktop side-by-side availability panel, stacked mobile layout, explicit review/save placement and unchanged uncertainty/replay safeguards.
- Unrestricted Account shares the application shell and separates identity from password/security. Forced-change sessions retain the focused restricted screen; a successful change transitions to the shell with a server-session confirmation notice. Navigation is absent from the password card.
- Users table is the main focus; Add/Edit opens a dedicated secondary panel, Cancel clears temporary secrets, and existing reset/deactivation/revocation protections remain enforced.
- Plain business descriptions, team/product names, readable audit actions and safe optional record details. Error/success alerts retain accessible roles; support references are expandable rather than dominant.

No API schemas, business permissions, rate-limit thresholds, password rules, stock writer or applied migrations changed. The password confirmation flash is set only after a real successful password change, not from a query parameter. Existing accounts, credentials and inventory are preserved.

## Verification

Run `.tools/php/php.exe tests/integration/ui-refinement.php` for the current review, with locked browser dependencies installed. `UI_PHASE=before` was used before editing to capture the baseline; do not regenerate that baseline with current code. The new suite covers populated pages, expanded filters, sorting, named product filtering, user-panel open/cancel, real empty queries, delayed requests, server errors, diagnostic disclosures and recovery.

The final refinement suite passes **152 assertions and 111 clean axe scans**; its 111 base/state/login screenshots are in the after directory. Empty lists use real MariaDB filters. Loading/error screenshots use explicit browser request delays/503 injection, and are not evidence of a real infrastructure outage. The supplemental review passes **36 assertions and 15 clean axe scans**, with 15 screenshots of real empty dashboard, injected faults, real availability and no-write confirmation; keyboard account-menu dismissal/focus and real sign-out pass. Authentication adds seven invalid-login/forced-change/countdown screenshots, for **133 after captures**, and passes 336 database/security plus 32 Chrome assertions/seven axe scans. The 21-screen Admin/Staff/Viewer system review passes **180 assertions and 63 clean axe scans** at five widths. Manual image inspection includes desktop dashboard/products/users/account and mobile/tablet products/movements and the confirmation dialog.

Affected functional checks include the complete inventory workflow/concurrency/export suite, account administration, authentication, lookup management, OpenAPI/client and Composer checks. Current results are recorded in [test evidence](test-plan.md) and [progress](progress.md). Intermediate new checks exposed the forced-change confirmation transition; this was fixed with a session notice and rerun. Test selectors were also corrected for user_id versus audit actor_id, datalist option values and intentionally changed uncertainty wording. Failed runs are not counted as passes.

Automated/developer review does not replace actual participants or comprehensive assistive-technology certification. MySQL 8.4, production HTTPS, actual participants and hosted CI remain pending. No deployment or purchase occurred.
