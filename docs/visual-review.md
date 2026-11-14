# Stage 3 visual and accessibility review

## Live workspace UI refinement - 2026-11-14

Implemented the screenshot-driven Analytics, Help and Physical Counts refinement first, then inspected the remaining website and applied only confirmed presentation problems. Navy navigation, blue primary actions, shared tokens, existing API/client and business workflows remain. No schema/business-rule/export-contract changes, dependencies, installed login, password changes, business fixtures on the installation, deployment or push.

### Main changes and files

- [Analytics template](../templates/live/analytics.php) and [browser controller](../public/assets/js/pages/analytics.js): consistent Movement analytics title, useful subtitle, compact toolbar, synchronized shortcut/custom dates, a native keyboard-accessible calculation disclosure, applied-period/view/snapshot metadata, visible historical-exclusion warnings and view-specific empty states. Tables retain separate captured units and align quantities; no invented mixed-unit chart or total.
- [Help template](../templates/live/help.php): repaired malformed nested markup and an interrupted sentence. Role-specific topic anchors, bounded reading lines, short procedures, task-relevant notes/disclosures and authorized links replace the nested paragraphs. Viewer sees availability/account guidance; Staff sees operational guidance; Admin additionally sees account administration and corrections/classification instructions.
- [Counts template](../templates/live/stock-counts.php) and [browser controller](../public/assets/js/pages/stock-counts.js): primary New count action, accessible inline note form, opening/cancellation/completion focus, preserved create retry key/payload and recoverable inputs, compact status filters, explicit empty/filter/error states, text badges, creator/reviewer IDs, readable scrollable tables, recorded/counted/discrepancy quantities and decision-point approval/recount guidance. No directory query or API extension was added to obtain creator names.
- [Business layout](../templates/layouts/business.php), [page title controller](../src/Http/Controllers/BusinessPageController.php), [results component](../templates/components/live-results.php), [table component](../templates/components/table.php) and [shared list client](../public/assets/js/business.js): presentation titles/subtitles, opt-in task state copy, numeric headers and a backwards-compatible loading/applied-request render hook. Existing unrelated page copy and handler contracts are retained.
- [Workspace CSS](../public/assets/css/usability.css), [public CSS](../public/assets/css/public.css), [dashboard template](../templates/live/dashboard.php), [product form](../templates/live/product-form.php): targeted spacing/column-width refinements described below. Scoped rules preserve public/auth styling and other consumers. The forced-password form also separates its primary password action from Sign out.

### Page review

Responsive means actual Chrome at 360/768/1440 pixels with no horizontal **page** overflow; wide data tables retain a keyboard-focusable horizontal scrolling region. An unchanged decision means inspection found the existing structure usable; it does not assert manual accessibility certification.

| Page / component | Decision | Reason and actual verification |
|---|---|---|
| Movement analytics | Improved | Removed duplicate methodology/gaps, synchronized dates and applied metadata, visible uncertainty, specific states. Five real-data views, shortcuts/custom/reset/refresh, delayed/failing/empty/warning fixtures, keyboard disclosure, responsive screenshots and axe. |
| Help | Improved | Repaired malformed nesting; topic navigation, bounded task guides and role conditions. Admin/Staff/Viewer anchor/permission/axe checks and screenshots. |
| Physical count list/create | Improved | Primary creation panel, focus, state badges, creator/review fields, compact filters and tailored states. Actual isolated creation, uncertain retry, filtered/initial empty fixtures, refresh recovery, 26-count real API pagination (25 + 1), responsive/axe. |
| Count editor/review | Improved | Consistent headings, unit-aware numeric columns and decision-point restrictions. Draft/submitted/approved/cancelled screenshots/axe, failed-save input retention, explicit unsaved discard; existing Phase 5 stale/recount/atomic approval/lost-response regression passes. |
| Dashboard | Improved | Unit overview touched the metric cards; separated panels and compacted per-unit values without aggregation. Responsive/axe screenshots and Stage 11 regression. |
| Products table/cards/details/archive/restore | Unchanged | Existing view switch, composed role-safe details, guarded actions and scrollable long tables retained. Responsive/axe, Stage 9 and Phase 1-2 browser/HTTP regressions. |
| Product create/edit | Improved | Replaced generic subtitle; orphaned unit field moved into Stock and pricing. Responsive/axe screenshots and Stage 9 validation/save regressions. |
| Stock movements/confirmation/reversal | Improved spacing; workflow unchanged | Context controls touched; added shared-token gaps. Responsive/axe and Stage 10 confirmation/retry/correction regression. |
| History/details | Unchanged | Existing filters, captured history, linked corrections and scrolling remain appropriate. Responsive/axe and movement/Phase 1-2 regressions. |
| Categories/Suppliers/editors | Unchanged | Existing shared aligned controls, scoped counts and dirty-editor protection retained. Responsive/axe and Phase 1-2 conflict/dirty-state regressions. |
| Reports/export/print | Improved layout; contracts unchanged | Removed inherited action-row top gap and widened transaction product columns to prevent very tall wrapped rows. Responsive/axe; Stage 12 actual filter/CSV/print/export regression, Phase 4 detailed JSON download. |
| Your account | Unchanged | Existing separate profile/photo/security sections and placeholders remain appropriate. Responsive/axe, real isolated role account/header checks in homepage regression. |
| Users/dialogs | Unchanged | Existing form hierarchy, conflict retention and destructive/reset separation retained. Responsive/axe and Phase 1-2 conflict/return/state regression. |
| Audit/details | Improved filter spacing; details unchanged | Removed inherited action-row gap; retained escaped comparison details. Responsive/axe and Phase 1-2 actor/action/detail regression. |
| Public homepage | Improved | Workflow numbers wrapped onto two lines at tablet width; prevented shrinking/wrapping. Three-width screenshots/axe and separate homepage/navigation/login regression. |
| Login | Unchanged | Existing illustration/form hierarchy and mobile form priority retained. Three-width screenshots/axe and homepage login/navigation regression. |
| Forced password change | Improved action spacing | Primary and Sign out buttons touched; added scoped separation. Isolated forced account, three-width screenshots and axe; no installed login/password mutation. |
| Header/sidebar/mobile navigation/alerts/dialogs/pagination | Structure unchanged | Existing shared branding/focus/rail/drawer controls retained; affected consumers checked. Responsive/axe, homepage rail/drawer/role/header checks, count focus/retry/dirty guard and real pagination tests. |

### Executed checks

Selected PHP **8.5.11** and real explicit **MariaDB 10.4.32 local-only**, with randomized disposable schemas for every mutation fixture. Commands and observed results:

| Command (selected PHP unless stated) | Observed result |
|---|---|
| `C:/ProgramData/ComposerSetup/bin/composer.phar validate --strict` | PASS |
| same Composer `check` | PASS: 154 syntax files, 139 foundation, 14 engine-policy, 64 API assertions |
| `tests/integration/workspace-ui.php` | PASS: 208 Chrome assertions including 33 clean axe scans |
| `tests/integration/phase4.php` | PASS: 24 DB/HTTP/harness + 8 Chrome assertions |
| `tests/integration/phase5.php` | PASS: 40 DB/HTTP/harness + 17 Chrome assertions |
| `tests/integration/stage9.php` | PASS: 43 product DB + 21 Chrome assertions |
| `tests/integration/stage10.php` | PASS: 4 movement DB + 28 Chrome assertions |
| `tests/integration/stage11.php` | PASS: 13 dashboard DB + 16 Chrome assertions |
| `tests/integration/stage12.php` | PASS: 43 report DB/export + 30 Chrome assertions |
| `tests/integration/phase12.php` | PASS: 38 DB/HTTP/harness + 68 Chrome/accessibility assertions |
| `tests/integration/homepage.php` | PASS: 28 HTTP/MariaDB + 68 Chrome assertions, 18 clean axe scans |
| Read-only installed `bin/check-schema.php` / `bin/reconcile-stock.php` | PASS: seven applied, no pending; three products, no discrepancy |
| Private read-only installed before/after preservation | PASS: every installed table hash and protected configuration/credential/upload/migration file hash unchanged |

Early failing checks were not passes: the new Custom dates option initially selected itself/reset to blank; explicit default selection repaired it. An old exact-label check caught the added required-label text; stable labels/native required validation were retained. An old count assertion raced against the new empty-lines row; it now waits for the actual saved captured-unit row. Corrected suites passed without weakening business assertions. Generated historical homepage test artifacts were copied to the new evidence folder and their pre-run bytes restored; unrelated working-tree/application files were preserved.

### Evidence and limits

- [Before/after comparison viewer](evidence/workspace-ui/index.html), [sanitized verification and screenshot hashes](evidence/workspace-ui/verification.json), [browser/axe results](testing/workspace-ui-results.json). Captures use fixture accounts/data, not installed business records. Original captures: 28 before images; refreshed after states and separately retained homepage regression captures accompany them. Priority pages have paired 360/768/1440 captures; wider pages have desktop before and responsive after captures. The unchanged forced-password structure had no original before screenshot; current after captures show its targeted spacing improvement.
- Automated axe does not establish manual screen-reader/participant acceptance. **Native browser zoom was not performed**; viewport resizing and CSS/device scaling are not presented as native zoom evidence. MySQL 8.4, production/hosting and representative-volume performance were not verified here.
- The installed application was left running. All mutations/login/password-reset tests were isolated; no installed records, credentials, uploads, migration history or stock were changed by this task. No new feature or schema work was included.

Earlier Stage 3 and subsequent checkpoints below are retained as history.

Current live refinement evidence (2026-10-12): see [review and changes](ui-refinement.md) and [before/after screenshots](visual/refinement/index.html). The new local review passes 152 assertions/111 axe scans and captures all twelve business/account screens at 360/768/1440px, including real empty lists and explicit loading/error injection. Cross-role review covers 21 permitted screens. Earlier sections remain historical checkpoints; participant/manual assistive-technology and production verification stay pending.

Reviewed 2026-10-11 (Asia/Manila), against blueprint section 4. **Stage 3 checkpoint passed** for the implemented development fixture screens, with no material defect found in the final review. This is not a release accessibility certification or a claim that the business application is implemented.

## Evidence and reproduction

Run the PHP 8.5 Composer checks, then install locked browser tools and run the actual browser review:

```powershell
& .tools/php/php.exe C:/ProgramData/ComposerSetup/bin/composer.phar validate --strict
& .tools/php/php.exe C:/ProgramData/ComposerSetup/bin/composer.phar check
npm.cmd ci --prefix tests/browser --cache .tools/npm-cache
node tests/browser/review.cjs
```

Verified environment: PHP 8.5.11, Node 24.15.0, Playwright 1.64.0, axe-core/playwright 4.13.0 and installed Chrome 155.0.8059.40 (headless). PHP test server and browser terminate after tests. Browser dependency download first failed under network sandbox; authorized retry succeeded. The locked test install was also verified offline using `npm.cmd --prefix tests/browser ci --cache .tools/npm-cache --offline --ignore-scripts --no-audit`.

- Composer strict manifest validation passed.
- 33 PHP files passed syntax checks; 118 foundation assertions passed, including fixtures at root and base paths, malformed fixture query rejection, Viewer fixture field stripping and production preview denial.
- Final browser run: **386 assertions passed**, **66 screen/state/viewport axe scans with zero detected WCAG A/AA violations**. Separate drawer and product dialog scans also had zero violations.
- **70 screenshots** captured; [machine-readable results](visual/stage3/results.json) records checks, browser, contrast and the 66 scanned cases. Main dimensions 360×900, 768×1024 and 1440×900; additional clipping checks at 480, 900 and 1024px.
- JavaScript syntax checks passed for components.js, pages/preview.js and tests/browser/review.cjs; no page errors occurred in the browser run.

## Screenshot index

All artifacts are outside public/ in docs/visual/stage3. Naming: `{screen}-{width}.png` for populated and `{screen}-{state}-{width}.png` for state fixtures. Screens dashboard/products/login/form have populated/loading/empty/error/restricted at all three widths. Gallery and fixed Viewer product view have populated screenshots at all three widths; drawer, focus, validation and confirmation interactions have extra evidence.

| Screen | 360px | 768px | 1440px |
|---|---|---|---|
| Shell and dashboard | [Mobile](visual/stage3/dashboard-360.png) | [Tablet](visual/stage3/dashboard-768.png) | [Desktop](visual/stage3/dashboard-1440.png) |
| Inventory table | [Mobile](visual/stage3/products-360.png) | [Tablet](visual/stage3/products-768.png) | [Desktop](visual/stage3/products-1440.png) |
| Login | [Mobile](visual/stage3/login-360.png) | [Tablet](visual/stage3/login-768.png) | [Desktop](visual/stage3/login-1440.png) |
| Grouped product form | [Mobile](visual/stage3/form-360.png) | [Tablet](visual/stage3/form-768.png) | [Desktop](visual/stage3/form-1440.png) |
| Shared component gallery | [Mobile](visual/stage3/gallery-360.png) | [Tablet](visual/stage3/gallery-768.png) | [Desktop](visual/stage3/gallery-1440.png) |
| Viewer inventory fixture | [Mobile](visual/stage3/viewer-360.png) | [Tablet](visual/stage3/viewer-768.png) | [Desktop](visual/stage3/viewer-1440.png) |

Representative state/interaction evidence: [loading](visual/stage3/products-loading-768.png), [empty](visual/stage3/dashboard-empty-1440.png), [error](visual/stage3/products-error-360.png), [restricted](visual/stage3/products-restricted-1440.png), [drawer](visual/stage3/drawer-360.png), [validation](visual/stage3/login-validation-360.png), [product review](visual/stage3/product-review-768.png), [keyboard focus](visual/stage3/gallery-focus-1440.png). Other state images follow the documented naming convention and have scanned results in JSON.

## Findings and repairs

| Review area | Final outcome / evidence |
|---|---|
| Palette and hierarchy | Neutral canvas/white panels, restrained primary action and text+color stock badges; exact blueprint tokens. No gradients, toy imagery, unsupported metrics or oversized action tiles. System type, tabular numbers and shared spacing align headings/toolbars/panels. |
| Desktop/tablet/mobile composition | 240px desktop sidebar, modal mobile drawer, dashboard three useful metrics, attention list and authorized sample movements. Tablet stacks lower panels; mobile stacks metrics/forms/filters. No page-level horizontal overflow in all tested widths. |
| Inventory density and identity | Review identified wrapped SKU identifiers on narrow tables. Fixed SKU/header nowrap, and added mobile quantity/threshold summaries beside product names. Clear keyboard/scroll instruction preserves discoverability of offscreen columns/actions. Final mobile screenshots re-reviewed and browser checks passed. Longer names wrap within product cells. |
| Buttons/fields | Primary/secondary/danger/ghost share styling and 44px controls; hover colors and 3px keyboard focus verified. Disabled/busy semantics and reduced-motion spinner removal checked. Darker #64748B control borders supplement decorative #E2E8F0 so controls remain distinguishable. |
| States | Loading/empty/error/restricted fixtures captured/scanned at all widths; errors have clear copy/retry, loading busy status, restricted inventory hides sensitive columns/actions. Search-no-match state and clear-filters action work. |
| Product form | Two-column desktop/tablet and single-column mobile, grouped legends, required fields and hints. Create opening quantity versus edit readonly current quantity/SKU. Validation summary/inline errors link to inputs; valid fixture review says nothing was saved. Cancel/keep-editing/discard and beforeunload warning implemented. |
| Keyboard/dialog/drawer | Skip link/main target; native labeled modal dialogs; Escape and focus restoration; drawer background inert and forward/reverse focus stays in drawer. Table arrow-key scroll tested. Confirmation focus/axe checks pass. |
| Navigation/fixture honesty | Active nav clear; unavailable features are Planned text, not dead links. Fixture banner/no active session context on all screens. No role switch, fake API success, production fixture route or password submission. Viewer fixture rendering omits price/supplier/history fields server-side. |
| Contrast | Actual token text pairs: primary on white 6.70:1, low 6.37:1, out 6.80:1, in 6.49:1. All exceed 4.5:1; axe also checked rendered screens. |

Manual image inspection covered representative main screens across the three requested widths, the component gallery, drawer, and representative loading/empty/error/restricted states. Automated scans cover every captured main screen/state combination. The screenshot review and targeted keyboard checks found no remaining material Stage 3 defect after the mobile inventory repair; final browser run regenerated evidence.

## Limits and later review

Fixtures use sample data and are development/test-only. Auth/session/CSRF, real data loading, API field policy, database integrity and production wiring are not tested by this UI review. Automated axe does not establish full WCAG conformance; broader assistive-technology, real-workflow and final accessibility checks remain Stage 14 obligations. Final pages must reuse these components and be checked for visual drift. Database verification is **pending**; see [pre-Stage-4 compatibility report](database-compatibility.md). No migration or database operation ran in Stage 3.

## Stage 14 live-system review (2026-10-11)

Supersedes fixture-only evidence for current live screens. Actual Chrome reviewed 21 permitted role/screen combinations at 360, 540, 768, 1024 and 1440 pixels: 180 assertions, 63 clean axe scans and 105 screenshots. Private artifacts are under var/review-stage14, with results.json. Long product names, responsive page bounds, keyboard drawer/dialog dismissal and focus restoration pass. Admin reset dialog/user management adds seven clean axe scans in its own suite.

Manual inspection of actual desktop product and mobile Viewer product screenshots confirmed spacing and availability summaries. Repaired SKU wrapping in general live tables and padding of shared search/user forms; reran system and affected business suites successfully. This is automated and developer visual evidence, not participant feedback or full WCAG certification. Real participants and broader manual screen-reader/assistive-technology testing remain pending; see [participant scripts](user-testing.md).
