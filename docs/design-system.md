# BizStock shared design system

Stage 3 implements blueprint section 4 with PHP templates, vanilla JavaScript and locked Bootstrap 5.3.8. Future pages must reuse these components. All current /preview routes are fixtures in local/test only; production returns 404. No login, stock persistence or API success is simulated.

## Tokens and layout

public/assets/css/tokens.css centralizes canvas #F8FAFC, surface #FFFFFF, text #0F172A, muted #475569, border #E2E8F0, primary #1D4ED8/hover #1E40AF and specified stock-status pairs. System font, 16px body/14px tables/20px section/28px page headings; tabular quantities/prices. Shared 4/8/12/16/24/32px scale, 8px radii and 150ms functional motion. Reduced-motion preference disables transitions/animations.

Control outlines use #64748B instead of the decorative border token so inputs/outlined controls remain identifiable at sufficient non-text contrast on white. Focus is a 3px primary outline with clearance. components.css owns controls/panels/tables/status/dialogs; layout.css owns sidebar/header/grid/responsive composition. No page-specific button/field styles. Bootstrap is adapted through variables/shared classes rather than a second component system.

Desktop sidebar is 240px. At <=900px it becomes a modal drawer with close/backdrop/Escape, background inertness, focus trap and restoration. At <=600px metrics/forms/filters stack; content padding 16px, desktop 24–32px. Tables scroll within their labeled, keyboard-focusable region; the page does not scroll horizontally. Quantity/threshold are distinct right-aligned columns. Skip link targets a focusable main landmark.

## Component APIs

`BizStock\Support\Ui` is created with central Config. `Ui::escape(value)` escapes all text/attribute values. `component(name, props)` renders only allowlisted component files; unknown names fail. `icon(name)` returns fixed decorative SVG, never user-provided markup. Config::url supplies root/subdirectory-safe URLs.

| Component | Props and behavior |
|---|---|
| button | label; variant primary/secondary/danger/ghost; optional icon; type button/submit; disabled/busy; href for local anchor or dialog ID for confirmation. Busy is disabled with aria-busy; use descriptive labels. |
| field | unique id, label; type text/password/number/search/email/select/textarea; required, readonly, value, hint, autocomplete, maxlength, pattern; number step/max; select options map. Labels/hints/errors linked; errors announced through summary, aria-invalid and inline text. |
| badge | status in/low/out; specified palette plus text label. Zero takes out-of-stock label and also belongs in low-stock count. |
| notice | heading/message and info/error/success tone; error role=alert, others role=status. Never put secrets or raw errors in message. |
| state | state loading/empty/error/restricted; prefix/screen for retry link. Loading busy indicator, human copy and retry/error states. Populated content provided by page. |
| table | caption, key=>label columns map and escaped rows; status cell uses badge, price uses PHP reference formatting. Name/quantity support sorting with aria-sort; actions use accessible native details. |
| pagination | shared Previous/Next/page/count controls; JavaScript supplies current totals and disabled boundaries. |
| dialog | unique id, title, message; native modal dialog, title association, close/Escape and return focus. Caller supplies summary through textContent, not HTML. |

`templates/layouts/app.php` owns sidebar/header/fixture banner/assets/landmarks and uses allowlisted page templates. Unimplemented navigation entries are text marked Planned, not dead buttons or false routes. Viewer fixtures omit supplier/prices/history/editing server-side and use fixed context routes; this is not production authorization (Stage 6).

## Shared interactions and fixture boundaries

components.js exposes `BizStockUI.announce(message)` and `openDialog(id, trigger)`; handles drawer/dialog focus, shared validation, inline errors and unsaved form navigation/beforeunload warnings. Valid fixture submission opens a truthful no-save/no-session review. No passwords are submitted or stored. product fields distinguish opening quantity on create and readonly current quantity/SKU on movement-bearing edit fixture.

pages/preview.js handles **fixture-only** search/category/status filtering, numeric/name sorting and four-row pagination. Low filter includes zero; counts announce updates. No fetch or localStorage. Stage 5 will add the shared real API client and later feature stages will replace fixtures rather than preserve duplicate production data logic.

## Review routes and checks

- /preview/dashboard, /preview/products, /preview/products/new (optional ?mode=edit), /preview/login, /preview/components.
- Fixed permission contexts: /preview/viewer/dashboard and /preview/viewer/products; no role-switching widget.
- Each main screen accepts ?state=populated/loading/empty/error/restricted for review. Unknown or array state/mode fails 400. Fixtures are labeled and disabled in APP_ENV=production.
- Gallery demonstrates button variants, disabled/busy controls, field validation, badges, alerts, dialogs and linked data-state screens. Hover/focus states use the same CSS in all screens.

See [visual review](visual-review.md) for actual screenshots, browser/accessibility results and limitations. Database verification remains pending; this stage makes no database connection.

Through Stage 12, the live business layout/navigation uses these same tokens and PHP controls/table/state/dialog/field components. business.js shares list pagination, selector population, error/focus, currency/time formatting and badges; the one API client handles transport. Stock review freezes uncertain intents and guards unsaved navigation. Catalogue mobile rows repeat quantity/threshold beside SKU. Report print hides app controls and keeps applied filters, snapshot and current-page scope. Eighteen clean axe scans cover the new business forms/movement/history/dashboard/report screens at 360/768/1440; final broader visual/participant accessibility review remains Stage 14.
# Current live interface refinement

See [the refinement review](ui-refinement.md) and its [before/after browser gallery](visual/refinement/index.html). Shared tokens now include navy #15243b, slate text #17263c, canvas #f3f6fa, white surfaces, restrained blue, 12px panel radii and subtle shadows. Desktop headings are 30px/700; body text 16px; table/controls 14px. Existing semantic status colors remain functional signals, not decorative card colors.

workspace-start.php is the shared live shell for business, lookup and unrestricted account pages. The account menu uses native details/summary and a real CSRF-protected sign-out handler. Forced-change pages remain restricted. Ui::asset emits content-hash cache versions for live styles/scripts.

Shared components.js transforms data-search controls into compact toolbars and native advanced disclosures without dropping form controls. Common search/category/status/type/date controls stay visible; sorting and all other capabilities remain expandable. business.js name helpers send only existing ID filters; Admin names use authorized users APIs, Staff names derive from their visible history. showMessage renders semantic alerts and optional support-reference details using DOM text nodes. User editors are separate padded panels with explicit open/cancel, preserving the main table. Forms retain unsaved-change handling, backend validation, immutable history and safe retries.

