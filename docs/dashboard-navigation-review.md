# Dashboard, navigation and account editor refinement

Local review: 2026-10-12. Uses the existing PHP/PDO services, role projections, browser API client, UI tokens, shared shell/dialogs and four existing migrations. No schema migration, account reset, installed seed, stock write bypass or deployment.

## Behavior

Dashboard title/description and Refresh/last-successful-update share one heading. Compact quick actions respect the authenticated role. Refresh has an explicit disabled/loading state; failed requests retain the last successful timestamp, hide stale results and expose an accessible error/retry. No simulated success.

Alerts precede activity, alongside recent movements where the available content width permits. Deterministic server order is **quantity ascending, then product_id ascending**: all zero-stock products first, then lowest quantities, with stable ID ties. Archived products are excluded, equality is included. The existing API remains bounded to 100 alerts; the screen initially displays five. View all alerts opens the complete low_stock=true active inventory. Name, SKU, available units, threshold and text status are shown. Admin/Staff Receive stock uses the exact internal product ID and type=IN in the movement URL; the form loads authoritative availability and awaits manual review/confirmation. Viewer receives availability-only projections and no movement actions/activity/history/prices/suppliers.

Cards retain active products, total units, inclusive low-stock count and out-of-stock count. Distinct low/out icons and restrained colors accompany text. Four specific links apply active, inclusive low-stock and zero-stock filters. Total units is explicitly combined quantities across active products, not inventory value.

Activity uses one shared quantity scale with five axis ticks and paired teal IN/blue OUT bars. Zero values paint no bar or full-height track. Date labels include month/day; native focusable day controls expose exact decimal-string quantities and activate an announced daily readout. The daily-data table remains available. The 30-day chart scrolls independently when necessary so each day has at least 24px target width. Both periods use the existing Manila calendar/UTC half-open aggregation: all opening stock, original movements and compensating reversals count in their recorded direction, including historical archived products/inactive actors. No financial or growth figures.

The shared sidebar is fixed to the viewport; the grid reserves its full/rail width. Brand/toggle and account footer remain visible; only navigation scrolls. Tooltips render outside the scrolling region, support keyboard focus and Escape, and retain accessible link names. Mobile keeps the existing overlay, independent navigation scrolling, inert background, body scroll lock, focus trap/restoration and Escape dismissal. The skip link is clipped when unfocused; focusing makes it visible above the shell, and activation focuses main-content.

Users create/edit stays embedded in /users. Back to users and Cancel use the shared form-value baseline/discard dialog, never history.back(). Clean forms return immediately; Keep editing retains values; Discard clears sensitive input and closes the editor. Focus returns to list filters without changing search/sort/page. Create has a required Initial password; edit hides/disables it and uses the separate secure Reset password flow. Validation/API errors retain entered fields and focus the error summary. Successful writes refresh the current list, close the editor and announce success; a matching saved row is highlighted/focused, or Find saved account explicitly adjusts the query. Pending writes block internal cancellation. Switching editors also uses the discard guard. Product forms reuse the same clean/dirty Back/Cancel pattern and establish their baseline after data loading.

## Actual evidence

New checks: `tests/integration/dashboard-refinement.php` and `tests/browser/dashboard-refinement.cjs`, using randomized fixture schemas on separate loopback MariaDB **10.4.32 at 13308** and real HTTP/Chrome. Fixture databases/sessions are cleaned. Installed 13307 data is never a fixture.

| Area | Result | Evidence |
|---|---|---|
| Counts/alerts and archived exclusion | PASS | Independent SQL COUNT/SUM and quantity/ID queries for all three authenticated roles; zero, equality and archived records |
| Activity accuracy | PASS | Independent SQL daily sums for every Manila day in 30-day Admin/Staff responses; zero-filled rows, existing boundary/reversal regressions |
| Dashboard links/Receive | PASS | Four card links plus all-alerts counts; exact product/IN form selection; unchanged movement count before confirmation |
| Refresh/chart/states | PASS | Failure retains timestamp; held real request shows loading/disabled refresh; recovery; 7/30 selection, zero-height checks and exact keyboard/table values; real empty fixture |
| Sidebar, drawer, skip | PASS | Dashboard/History/Users top/middle/bottom in full/rail; 360/768/1440; named keyboard tooltips/Escape, focus wrap/restoration, body lock and main focus |
| Users return/save/failure | PASS | Clean returns, dirty Keep/Discard, cancelled create count unchanged, cancelled edit row unchanged, search/page retained, duplicate failure values/error focus and real save; Stage 13 create/reset/deactivate/reactivate persistence |
| Admin | PASS | Real HTTP authentication, full allowed dashboard/actions/accounts; direct API/page permissions and positive fields in permission regression |
| Staff | PASS | Actual receive/activity permitted; accounts, master-data writes and reversals denied server-side in permission regression |
| Viewer | PASS | Actual availability fields only; no prices/suppliers/activity/movements/reports; direct forbidden API/page/export requests tested |
| Responsive/accessibility | PASS | Chrome renders, no page clipping, editor/state screenshots and clean WCAG axe scans at all three widths; 200% CSS page-zoom rendering on long screens |
| Native browser chrome zoom/manual assistive technology | BLOCKED / NOT VERIFIED | Manual check not performed; headless CSS zoom does not establish browser-menu zoom or manual screen-reader acceptance |
| Installed preservation | PASS | Before/after canonical hashes for all ten current tables, private credential file and all uploaded files; three products reconcile with zero discrepancies |
| Production | BLOCKED / NOT VERIFIED | MySQL 8.4, HTTPS, hosted storage/CI and actual participants remain pending |

New suite final result: **75 DB/harness assertions, 227 primary plus 9 empty-state Chrome assertions, 30 clean axe scans**. Affected-regression totals are recorded in progress.md. Intermediate findings fixed: narrow-content/zoom card overflow, mobile metric/icon overlap, narrow 30-day target size, keyboard tooltips after short-viewport rail scrolling, and test-only request interception races. Failed runs are not treated as completed acceptance. The isolated test server was shut down after checks; the installed recovered server remains active and unchanged.

Commands: selected PHP runtime with isolated private DB environment to run dashboard-refinement, stage9, stage10, stage11, stage13, permissions, usability and stage6 integration scripts; composer validate --strict/check; node tests/api-client.cjs, tests/openapi.cjs, tests/release-docs.cjs. Installed preservation helper and bin/reconcile-stock.php were read-only checks.

## Screenshots

[Before/after gallery](visual/dashboard-refinement/index.html): 12 before captures and 55 after captures including responsive lists/editors, full/rail top/middle/bottom, mobile drawers, errors, loading, real emptiness and discard confirmation. All data and accounts in these captures are isolated test fixtures, never private installed inventory/credentials/uploads.
