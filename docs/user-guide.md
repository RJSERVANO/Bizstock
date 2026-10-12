# BizStock user guide

Dashboard alerts now show the first five products in lowest-quantity/ID order, with zero stock first. View all alerts opens the full active low-stock inventory. Low-stock counts include zero; Total units combines active quantities and is not inventory value. Admin/Staff can choose Receive stock on an alert to open its product in IN mode; review and confirm manually. Refresh shows the last successful update. Select 7/30 days, use a day control for exact IN/OUT quantities or expand Daily activity data; on narrow screens the 30-day chart scrolls sideways.

The sidebar stays anchored while the page scrolls. Scroll its navigation to reach remaining links; compact icons show keyboard/hover tooltips. Mobile navigation closes with Escape and restores focus. Use Skip to main content with the keyboard.

On Users, Back to users or Cancel closes the embedded editor and returns to the current list. Dirty forms offer Keep editing or Discard changes; cancellation saves nothing and clears the temporary password. Existing-account edits have no Initial password field: use Reset password instead. After save, look for the highlighted row or use Find saved account if current filters hide it. Product forms share the guarded Back/Cancel behavior.

2026-10-12 refinement: role buttons on the shared login are guidance only; use your own administrator-provided username/password. They retain entered values and focus username. Use Show/Hide for password fields; it never changes authorization. Homepage links select matching guidance. Required first password change still precedes inventory access.

Your account now separates Personal details, Profile photo and Password/security. Save details changes only your display name; username/role/status remain read-only. A name change keeps your sessions; password changes revoke other sessions. Selecting a photo previews it locally; Cancel selection leaves the saved photo untouched. Upload/Remove uses the existing safe image service. Use your current chosen password if the initial one was changed; forgotten credentials need an authorized Admin reset or trusted CLI recovery, never a public bypass.

History Search applies filters, Reset filters clears common/advanced selections and returns to page one, Refresh reloads the current selections. Dates are inclusive Manila days. Movement search does not select a product; choose a match, check availability and review the product/quantity before saving. Typing a new search clears the old selection. Retry availability if it fails. Dashboard zero-stock counts link to matching active products; Admin/Staff can view 7/30-day units received/issued including openings and reversals. These are stock activities, not revenue/profit. Viewer receives availability only. [Current installed access/runtime limitations](usability-review.md).

## Homepage and shared role sign-in

Open http://127.0.0.1:8080/ for the public introduction, or http://127.0.0.1:8080/login to sign in. Admin, Staff and Viewer use the same form, with their own administrator-provided username/password. Role links explain access; they never switch roles. Confirmed local usernames are `dev_admin`, `dev_staff` and `dev_viewer`. Initial development credentials are stored privately in `var/development-credentials.json`; a changed password replaces the initial one. Use your last chosen password or ask an existing Admin for a private secure reset. If all Admin access is lost, a trusted operator must follow [emergency recovery](admin-management.md). Do not share the credentials file.

Normal sign-in opens Dashboard. If a password change is required, finish it on Account before continuing. Back to home returns to the public page. Signed-in visitors can still read the homepage and use Open dashboard. No public registration exists. Contact text comes from your business configuration; absent a verified email, contact your administrator. Sidebar logo, toggle and icons align in compact mode; profile, team and audit icons are distinct. Existing photo uploads and product table/card features work as before.

Open the configured site and sign in with the account supplied privately by the owner. Initial/reset passwords require a change in Account before business access. Use 12–72 UTF-8 bytes and keep the password private. Account provides password change and sign out; no self-signup or role selection. Changed roles, deactivation and password resets revoke existing sessions. Contact an Admin for access recovery.

All roles use Dashboard and Products to find availability. At or below threshold includes zero; zero is labeled out of stock. Archived products are excluded from dashboard/current inventory. Use search/category/status, sortable quantity headers and pagination. Viewer cannot see prices/suppliers/location/history or reports, including direct APIs.

Admin creates category/supplier records first, then a product with a unique normalized SKU, required parents, decimal reference price, threshold and optional opening quantity/location/description. Opening stock is a real IN. Current quantity is read-only on edit. SKU cannot change once history exists. Reload a stale edit rather than overwriting; price edits affect future normal movements only. Archive at zero; history and reserved SKU remain. Reassign products before deleting referenced category/supplier.

Admin/Staff use Stock movements: find/select product, load availability, choose IN/OUT and a positive quantity, optionally add a note, then review and confirm. Displayed availability can be stale; server enforces authoritative balance. An uncertain response requires retrying the **unchanged operation in the same form** before starting another request. Inputs remain frozen for retry; never abandon it and blindly submit a new key elsewhere. If the page was closed, inspect immutable history/stock with the owner before recreating the operation.

Admin correction: open History, choose an unreversed original, enter a reason and confirm full reversal. Opposite direction, full quantity and original captured price are derived on server. Insufficient stock, archived product and repeated/reversal-of-reversal requests are rejected. A new corrected movement is a separate operation. Neither row is edited or deleted.

Admin/Staff history filters include product, current category, actor ID, type and Manila dates. Archived products/inactive actors remain present. Product/actor labels reflect current metadata; movement quantity/type/price/time are captured history. Inventory report is current active stock, not historical valuation. Transaction values use captured prices and include originals/reversals as gross reference movement value, never income/profit/accounting valuation.

Apply report filters before CSV export. Inclusive Manila dates become exact UTC boundaries. CSV exports all matching pages up to 10,000 rows/16 MiB, in a new current snapshot; narrow filters on an over-limit refusal. Formula-leading text receives a protective apostrophe. Print shows the current page/applied filters/snapshot. Viewer exports are forbidden.

Admin Users: create a username/name/role and temporary password, deliver it privately, and require first-login change. Edit role/name, deactivate/reactivate, or reset with a new temporary password. Every Admin edit through Users revokes prior sessions. Editing your own account signs you out; log in again. The final active Admin cannot be demoted/deactivated, including competing requests. Deactivation preserves accountability and inventory. Audit log shows safe changes with actor, request ID and before/after values; no passwords appear. Trusted server operators use the documented emergency recovery CLI only when authorized; it is not a web bypass.

For errors, keep the request reference and contact the owner/operator. Do not repeatedly submit new movements to resolve a network error. Dashboard refresh/revisit obtains current data; stale errors hide old results. Use mobile table scrolling for extra columns and actions; quantities remain summarized on product rows. Keyboard users can Tab between controls, use the navigation drawer and dismiss dialogs with Escape. Human participant/screen-reader acceptance and production hosting remain pending; see user-testing.md and deployment.md.
# Sign-in wait time

After too many attempts, wait up to five minutes from the start of the attempt window. The login screen shows the server's remaining time as minutes:seconds and a progress bar. Sign in becomes available when the countdown ends; it never retries automatically. Reloading does not clear the server limit. A later attempt retrieves the current remaining time. Password-change throttling also uses five-minute windows.

# Refined workspace

Your account appears in the application workspace once the required password change is complete. Use the account menu in the top bar for account settings or sign-out. On a small screen, open the navigation drawer using the menu button.

Search and common filters are visible above each list. Open **More filters and sorting** for all remaining options, including IDs and advanced stock/date filters. Where available, type a product or team member name and choose a match; the application keeps its ID internally. Staff history name choices come from the current page; use the advanced ID filter for another member. Reports export the filters you have applied, so apply changes before downloading.

On Users, choose **Add account** or **Edit** to open the account panel. Cancel closes the panel and clears temporary passwords. Support references are available inside expandable error details when troubleshooting is needed.


## Sidebar and pictures
Use the desktop navigation toggle to switch labels/icon rail; its preference is remembered on this browser. On mobile use Open navigation and close/Escape to dismiss. Your account lets you upload or remove your own photo; initials remain the default. Admin edits an existing product to manage its image; save a new product first. Inventory starts as a table; Show thumbnails and Card view use the same live filtered records and permissions. [Image limits and login help](private-images.md).
