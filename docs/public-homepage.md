# Public homepage, role sign-in and navigation

Implemented and locally verified 2026-10-12. This owner-authorized extension reuses the completed sidebar, shared components/client, private image service and migration 004. No new migration, API permission, stock writer, credential reset or deployment was introduced.

The public `/` now explains the implemented product/category/supplier, IN/OUT, alert, report and role workflows. Navy/teal sections, original stockroom artwork, a layered illustrative inventory preview, workflow, About, contact and bottom CTA replace the development foundation page. Sample values are explicitly illustrative and never come from installed inventory. Generated people are labeled fictional. Anonymous visits make no API/image requests, create no session and require no database connection. Signed-in visits remain public; only the CTA changes after checking the current session. Revoked/expired sessions get Sign in; unrestricted sessions get Open dashboard; forced-change sessions get Complete password change. Responses are private/no-store.

`APP_BUSINESS_NAME` (default BizStock, 1–100 characters) and optional `APP_CONTACT_EMAIL` are centrally validated in Config. The installed configuration has no public contact email; the page says “Contact your business administrator.” Supply only a verified business email. Names are escaped; invalid email/control characters are rejected. No contact form, signup, invented social proof, pricing or private upload previews are present. One original hero image has three locally served WebP sizes and meaningful alt text; there are no below-hero raster images to load. The hero uses responsive selection and high fetch priority. The illustration's source/derivatives are documented in [asset provenance](asset-licenses.md).

One `/login` form serves every role. Shared role cards explain access and link to that form; they never submit a role or fill credentials. Back to home supports the configured base path. Normal successful login opens `/dashboard`; `must_change_password` from the actual API response routes to `/account` first. The restricted screen, CSRF, five-minute rate limits/countdown, expiry and revocation are retained. Existing authentication browser helpers now verify the dashboard redirect before explicitly opening Account for their account-specific tests.

The sidebar brand and toggle share a heading grid. Logo, toggle and link icons use one 40px horizontal track; in the 80px rail every center measures x=40px. Expanded logo/wordmark/toggle boxes are separate and vertically centered. Shared icon paths distinguish profile/person, Users/group and Audit/clipboard-clock from movement History. All use 20px outline SVGs with 1.7 stroke. Uploaded avatars remain separate. Existing remembered preference, accessible link names, keyboard tooltips/Escape dismissal and mobile inert/focus-trapped/dismissible drawer are reused.

## Actual local evidence

Selected PHP 8.5.11, XAMPP MariaDB 10.4.32 and actual Chrome. Disposable migrated databases, random fixture passwords and isolated upload namespaces were used throughout.

| Check | Result |
|---|---|
| `composer validate --strict`; `composer check` | PASS manifest; 129 PHP syntax, 139 root/subdirectory foundation, 14 database policy and 64 API assertions |
| OpenAPI/client/release docs | PASS 38 implemented operations / 653 resolved references, client transport/CSRF/retry-key/CSV, 154 local documentation links and pinned owner-authorized scope amendment |
| `composer test:homepage` | PASS 28 HTTP/MariaDB/harness assertions; public/signed/forced/revoked CTA, base paths, privacy, central configuration escaping/validation, local image dimensions/size and before/after reconciliation |
| Homepage Chrome | PASS 65 assertions and 18 clean WCAG 2.2-tagged axe scans; home/login/all-role Account at 360/768/1440, real role login to dashboard, reduced motion, distinct icons, computed alignment, 168px inventory expansion/table containment, rail preference, tooltips and mobile drawer focus |
| `composer test:auth` | PASS 336 database/security assertions; 32 Chrome assertions/seven axe scans, including forced change, new-password persistence, CSRF, expiry, revocation and real rate-limit behavior |
| `composer test:images` | PASS 54 database/HTTP assertions; 50 Chrome assertions/18 axe scans, including actual formats/content/size/dimensions, role/CSRF denial, concurrent replacement, cleanup/rollback, avatar defaults, persistence, thumbnails/details/cards |
| `composer test:business` | PASS 159 database/harness assertions; 117 Chrome assertions/18 axe scans, complete stock/retry/reversal/archive/dashboard/report/export flow |
| `composer test:install` | PASS eight clean package installation/upgrade assertions |
| `composer test:users` | PASS 37 database/API assertions; 21 Chrome assertions/seven axe scans |
| `php tests/integration/ui-refinement.php` | PASS 152 assertions/111 clean axe scans across existing screens and loading/empty/error states |

[Eight before and nineteen after screenshots](visual/homepage/index.html) use only test accounts. [Computed layout measurements](visual/homepage/after/layout-evidence.json) substantiate alignment rather than relying on screenshots alone. The first animation run revealed transient low contrast during the preview fade; animation now applies only to the photo, leaving preview text opaque. An obsolete foundation assertion for a removed health-page link was updated to verify the public login URL; liveness remains independently tested.

Installed stock reconciled before and after tests: three products, no discrepancies. The initial whole-database snapshot at 01:16 differs because the live app recorded product/image/photo changes at 01:17–01:19 and login limiter activity. These were preserved, never reverted. Automated writes were confined to disposable databases. A subsequent 01:29 snapshot verifies the final installed data; the private initial credential file and both installed upload file hashes are unchanged. Four existing migration records remain unchanged; no additional migration was created. This distinction prevents falsely claiming that concurrent live edits did not happen.

## Local access and recovery

Confirmed [homepage](http://127.0.0.1:8080/) and [shared login](http://127.0.0.1:8080/login) return HTTP 200. Active installed usernames are `dev_admin` (Admin), `dev_staff` (Staff), `dev_viewer` (Viewer). No installed-account login attempts or password resets were needed for verification; role login/password checks used disposable accounts.

Initial development credentials are stored privately in ignored `var/development-credentials.json`, outside the public root. Open it privately on this computer; do not publish its contents. It records initial passwords, not current passwords. If you changed yours, use your last chosen password. If forgotten, ask an existing Admin to use Users → Reset password and deliver the temporary credential privately. This revokes sessions and requires a new password. If every Admin is locked out, the trusted local operator can follow [documented emergency CLI recovery](admin-management.md). Recovery must be explicitly requested; this task performed none.

MySQL 8.4, production HTTPS, hosted image storage/GD/ACL/coordinated upload backup-restore, hosted CI and actual participant/manual assistive-technology acceptance remain **pending / NOT VERIFIED**. No deployment or purchase occurred; Stage 16 has not started.
