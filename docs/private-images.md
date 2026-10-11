# Sidebar and secure images — 2026-10-12

This owner-authorized extension supersedes the blueprint's image-upload exclusion only for own-account profile photos and one Admin-managed product image. Camera capture remains excluded. No accounts were reset, credentials printed, stock logic changed or application deployed.

Desktop navigation switches between full labels and an icon rail using a keyboard-accessible button. Rail links retain accessible names and show hover/focus tooltips. Only `bizstock.sidebar` (full/compact) is stored locally; no account, credential or inventory state is stored there. Mobile retains full navigation in the existing dismissible focus-trapped drawer, with inert background, Escape/backdrop/close dismissal and focus restoration. Tables scroll within their existing regions as the workspace expands.

Account has an independent photo form; initials remain the default and return after removal. Header and Account use the same authenticated photo endpoint. Admin can manage an image on an existing product's edit page; save a new product first, then edit it. Product details display a larger image. Inventory starts in table view, with optional thumbnails and neutral placeholders. Card view uses the same loaded page, filters, sorting, pagination, quantities and role-filtered price/actions. Switching view does not issue stock writes.

## Server and contracts

Migration `004_private_images.sql` adds nullable, private generated-filename references to users and products. Existing rows default to no image. Ten tables remain; four tracked migrations. Existing migration files are unchanged. New safe projections expose only `has_image`, never filenames, source names, storage paths or image contents.

`ImageController` handles HTTP/authentication/CSRF; `ImageService` validates, processes and coordinates transactions; `ImageRepository` owns prepared SQL. The existing PDO factory, session manager, AtomicWrite and audit repository are reused. Forced-change sessions cannot access photos. `GET/POST/DELETE /api/v1/account/photo` operates only on the current user. `GET /api/v1/products/{id}/image` allows authenticated Admin/Staff/Viewer, including archived product reads; its POST/DELETE require Admin and an active product. There is no endpoint for another user's profile photo. All mutations require CSRF.

Multipart POST accepts one `image` file and no extra fields. Actual Fileinfo MIME, dimensions and GD decoding must agree with a supported JPEG/PNG/WebP image. SVG, non-images, truncated files, PHP/script payloads, upload errors, files above 2 MiB and dimensions above 2048 × 2048 are rejected. Client MIME/filename are not trusted. A fresh GD canvas is re-encoded as WebP (maximum 512 pixels for profile / 1024 for product); source metadata and trailing content are discarded. Animated inputs become a static image; EXIF orientation is not applied. Files use cryptographically generated names and exclusive creation, never a client path.

Private files live under `var/uploads/<SHA256(environment|database)>/`. Controlled GET responses use `image/webp`, `nosniff`, `private, no-store` and server request IDs. There is no public upload directory. Runtime identity needs private filesystem access; use OS ACLs on Windows and restrictive ownership/modes on Linux. Image contents and filenames are absent from audit events: only old/new presence flags, actor, entity and request ID are recorded.

Replacement locks the current actor and owner, updates its reference and audit atomically, commits, then removes the former file. Failed DB/audit writes remove the staged file. Independent concurrent replacements retain exactly the current image. Image changes serialize with product archive/movement locks but do not alter quantity, movement history or metadata version; metadata edits never overwrite the image column. Last image writer wins. There is no durable image-upload idempotency-key guarantee or automatic retry; after a network interruption, reload to inspect the current photo. Product creation/opening-stock retry contracts are unchanged.

## Setup, backup and recovery

Enable PHP GD **with WebP read/write support** and Fileinfo in the selected PHP 8.5 runtime, then restart its web process. Composer requires both extensions; run runtime/platform checks. Keep `upload_max_filesize` at least 2M and `post_max_size` at least 8M. The app still enforces its own lower file bound. Run the usual private backup, `php bin/migrate.php`, then a safe rerun and stock reconciliation. Do not change the public document root or expose `var/`.

A database dump alone now omits photo files. During a maintenance window stop HTTP mutations, capture the transactional DB backup **and the matching private image directory**, record checksums and protect both as user data. Restore them together to a separate empty target and verify controlled image reads before reopening traffic. If environment/database name changes, copy files into the new SHA256 namespace; DB references contain only basenames. Keep a pre-upgrade code/database/files backup. Prefer a forward fix; rolling back application code must retain additive columns and private files. Never delete an image namespace belonging to another database.

A crash between file creation and DB commit can leave an unreferenced file, or a crash after commit can leave the former file. There is no automatic garbage collector. Under maintenance, compare generated basenames against both image columns, retain referenced files, and delete only verified unreferenced regular files in the exact namespace after a 24-hour safety window. Never follow symlinks, delete a directory recursively from an unchecked path or log file contents. Production backup/restore of uploaded files, OS ACLs and hosted decoder capabilities remain release checks until tested there.

## Local login

All roles use [the same login page](http://127.0.0.1:8080/login). Confirmed active local usernames are `dev_admin` (Admin), `dev_staff` (Staff) and `dev_viewer` (Viewer). Initial development credentials are stored privately in `var/development-credentials.json`, outside `public/`, ignored by Git. Open that file privately on this computer; do not share it or add it to documentation/screenshots.

An initial password stops working after a user changes it; the private seed file is an initial record, not a current-password store. Use the password you last chose. If forgotten, ask an existing Admin for the documented secure password-reset process; trusted emergency CLI recovery is reserved for loss of all Admin access. Reset forces a password change and revokes sessions. This task did not reset or rotate any account. The five-minute rate limit remains enforced.

## Evidence and pending targets

Actual local MariaDB/HTTP acceptance and Chrome evidence are recorded in progress/test-plan. Tests use disposable databases, random test passwords and isolated storage namespaces. Screenshots use test identities and inventory: [responsive gallery](visual/images/index.html). MySQL 8.4, production HTTPS, deployed storage/backup permissions and actual participant/manual assistive-technology testing remain pending; MariaDB 10.4.32 remains local development only.

The product has_image field is additive and optional in OpenAPI: immutable product-create replies committed before this migration retain their exact original payload. Fresh listing/detail projections include the flag. No stored operation replies are rewritten.
