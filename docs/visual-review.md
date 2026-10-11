# Stage 3 visual and accessibility review

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
