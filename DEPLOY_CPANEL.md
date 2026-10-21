# Blade and Filament deployment on cPanel

## Deployment progress — 21 October 2026

The user uploaded the prepared private application and public-file archives using cPanel File Manager, extracted them, and deleted the uploaded ZIPs. The user reports that the website loads. See [the deployment record](docs/deployment/cpanel-2026-10-21.md) for confirmed setup results and remaining checks, and [the no-Terminal instructions](deployment/cpanel/UPLOAD-STEPS.md) for the exact layout and setup modes.

- Private application: `/home/limachlk/bookmystyle`.
- Public document root: `/home/limachlk/public_html/bookmystyle.limach.lk`.
- Confirmed PHP CLI: `/usr/local/bin/php`, PHP 8.4.26. `/opt/alt/php84/usr/bin/php` also returned PHP 8.4.26 CLI.
- `/usr/bin/php` is CGI/FastCGI on this host. The earlier EasyApache example `/opt/cpanel/ea-php84/root/usr/bin/php` does not exist here.
- The private setup report confirmed the configuration/database connection check, valid application key, and no missing checked extensions. Migrations are marked completed; repeated runs returned `already_completed`.
- The user confirmed removing the temporary preparation cron. A successful preparation report and administrator initialization/login have not been independently verified.

For any future installation update, preserve the production key, database and uploaded media. The fresh-install key/administrator initialization steps are not an upgrade procedure. Do not reset the production database or re-run demo seeders.

## Target: bookmystyle.limach.lk

The production origin is `https://bookmystyle.limach.lk`. Start from `.env.production.example`, supply the actual cPanel database and SMTP credentials privately, and preserve the existing production `APP_KEY`. The template uses host-only session cookies for this subdomain.

This host uses the split layout recorded above. The package generator adjusts the public entry point to load `/home/limachlk/bookmystyle` and binds Laravel's public path to `/home/limachlk/public_html/bookmystyle.limach.lk` for both HTTP and Artisan. On another host that permits changing the document root, pointing directly to the application's `public` directory is also supported by the source. Keep the main domain's application separate.

The SMTP template uses `MAIL_SCHEME=smtp` with port 587. If your provider specifies implicit TLS on port 465, use `MAIL_SCHEME=smtps` and `MAIL_PORT=465`. `MAIL_ENCRYPTION` is not read by this project's mail configuration. Verify delivery using the provider's actual settings.

The local review before upload found three pending migrations: two dashboard index migrations and `2026_10_20_200000_create_push_tables`. The fresh host's migration helper subsequently reported completion. Local and server databases are separate; never infer server migration status from local status. Before future upgrades, back up the server database and review its actual pending migrations.

Configure a once-per-minute cron entry for Laravel's scheduler, using the host's confirmed PHP executable and application path:

```cron
* * * * * /usr/local/bin/php /home/limachlk/bookmystyle/artisan schedule:run >> /home/limachlk/bookmystyle-scheduler.log 2>&1
```

`QUEUE_CONNECTION=database` also requires a queue worker. Use the host's supported worker management; where persistent workers are unavailable, a cron-driven `queue:work --stop-when-empty --tries=3 --timeout=60` needs overlap protection. Confirm this with the host before enabling push delivery. Store Firebase credentials outside `public` and set `FIREBASE_CREDENTIALS` to their absolute path.

Reminder and cleanup switches remain disabled in the template. Enable the required switches only after verifying their tables, scheduler, queue and delivery configuration. Confirm active tasks with `php artisan schedule:list`. Include `public/build` and `public/images/brand-mark-blue.svg` in the upload; Git ignores built assets by design. Run the strict readiness check against the final host configuration before switching traffic.

Laravel now serves the public site, authentication and customer pages through Blade/Livewire, and owner/admin management through Filament. The retired React project has been removed. Keep Laravel routing in place; SPA fallback rewrites would replace native routes.

This is a release runbook, not blanket production-readiness approval. The deployment progress above supersedes historical statements that no upload or host migration had occurred. See docs/migration/step-14-deployment-readiness.md and docs/migration/rollback.md for earlier evidence and recovery guidance.

## 1. Prepare an isolated release

Use the approved source checkpoint and matching composer.lock/package-lock.json. Build in an isolated directory or CI environment, never over the active application. Preserve the existing APP_KEY; rotating it can invalidate encrypted data and sessions. Keep credentials, backups, .local, development tools outside the web root and release artifact.

Validate the host's actual PHP version/extensions with composer check-platform-reqs --no-dev. The project declares PHP 8.2+, while local verification used PHP 8.4.26; the actual host and optimized autoloader remain unverified. Required extensions must satisfy the complete lockfile, including image processing and MySQL.

Before every release, run `composer audit --locked --no-plugins --no-scripts` and `npm audit` against the exact release lockfiles. Review advisories and abandoned packages before approval; a clean historical audit is not evidence about a later release. Run `composer validate --no-check-publish` and keep both lockfiles in the release source. Resolve updates in the isolated build and test them; do not run automatic audit fixes or dependency updates on the live application.

In the isolated release, install locked dependencies with composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts. Review lifecycle scripts before running package:discover and filament:assets explicitly. Run npm ci and npm run build at the project root. Root public/build is required. There is one root Vite build for the native site.

## 2. Configure the server privately

Point the domain document root to the release's public directory; keep app, vendor, .env and storage outside it. If cPanel cannot select this document root, resolve the host layout before release rather than uploading the whole project to public_html. Retain public/.htaccess and Laravel's index.php routing. Only storage and bootstrap/cache need application write permissions; avoid world-writable permissions.

Set APP_ENV=production, APP_DEBUG=false, APP_URL to the real HTTPS domain. Set SESSION_SECURE_COOKIE=true, SESSION_HTTP_ONLY=true, SESSION_SAME_SITE=lax and leave SESSION_DOMAIN unset for a host-only cookie unless a reviewed subdomain requirement exists. Preserve the existing encryption key and configure the actual MySQL database privately.

Use persistent session/cache stores whose tables or services already exist. Configure a real mail transport, sender authentication and delivery monitoring. An SMTP setting alone does not prove delivery. Configure queue workers and supervised restarts if using asynchronous jobs; inspect the application's actual scheduling requirements before installing cron. Restrict TRUSTED_PROXIES to actual proxy IPs/CIDRs, or leave empty for direct hosting. Never trust every proxy.

For the same-origin Blade site, CORS_ALLOWED_ORIGINS may be empty. If a retained external API client is needed, list only reviewed HTTPS origins explicitly. Production no longer defaults to local development origins. Wildcard methods/headers do not grant access without an allowed origin.

Remove public/hot. Do not publish public/index.html, .env, SQL dumps, archives or project manifests. Expose only public media through the intended storage link. Configure the web server to disable all script execution within uploads, including symlink/alias targets; application validation and Apache rewrite rules alone do not prove server enforcement.

## 3. Check the release before switching traffic

Run php scripts/migration/check-deployment.php, then php scripts/migration/check-deployment.php --strict against the host release's effective configuration. The script reads configuration/files only and prints fixed check identifiers and statuses, never secrets. Default mode exits zero when a report is produced; strict mode exits one for failed automated prerequisites, two for initialization failure. Even a strict success leaves release_ready=false because external/manual gates remain unverified. It does not inspect the database, confirm the actual web-server document root, send mail or contact integrations.

The preflight also inspects nested public files for known repository/environment metadata, backup/database/archive/log/key extensions and server-script filenames other than the root index.php, including compound names such as picture.php.jpg. It reads directory metadata rather than artifact contents. Unknown symlinks fail the check without being followed; only the conventional storage link to this release's resolved storage/app/public is followed and inspected. Custom shared-media layouts require a reviewed configuration/workflow before passing this strict check. Missing/unreadable trees and scans exceeding 50,000 entries or 32 directory levels fail inspection. This denylist does not detect arbitrarily renamed secrets, replace upload validation or prove host execution rules. See docs/migration/step-23-public-release-files.md.

Run the read-only migration status command privately. Two pending index migrations remain on the imported local database. Validate those changes and backup restoration on an isolated copy before obtaining release approval for any real database migration. Never run migrate:fresh or seed against imported or production data.

On the isolated release with final environment settings, verify config:cache, route:cache and view:cache; inspect failures before switching traffic. Avoid dumping config:show app because it includes the encryption key. Avoid optimize:clear as a routine step: it also clears the configured application cache. Use targeted config:clear, route:clear and view:clear when necessary. Test the actual cached release, not only an uncached development checkout.

Confirm HTTPS redirects, real Google OAuth callbacks, email verification/password reset delivery, manual payment request and approval behavior, owner subscription renewal/change behavior, role isolation, uploads and storage permissions. OnePay selection currently records a request; it does not prove automatic payment processing. Run production browser and load checks with controlled accounts. Confirm backup/rollback timing and restoration before approval.

## 4. Approved release and rollback

Only after explicit release approval: back up the database/uploads and current source/assets/environment securely; switch the release/document root atomically where supported; restart workers; verify guest, customer, owner and admin routes and monitor errors. Any approved migration must have a separately reviewed recovery plan.

For source-only failure, switch back to the previous complete release with matching dependencies/assets and clear/rebuild its configuration as needed. Do not restore an old database over new customer transactions without a reviewed recovery decision. Maintain the existing APP_KEY across releases.

Laravel guidance: https://laravel.com/docs/12.x/deployment. Project-specific remaining gates are tracked in the migration documents.

## Local index rehearsal evidence

See docs/migration/step-15-index-rehearsal.md: final isolated migration/rollback/reapplication and record preservation checks passed. Six composite indexes are added on the imported schema, with its existing site-visit index preserved. A transient source-index incident was restored with explicit user approval and is documented. Both source migration entries remain pending; production backup/restore, lock-duration validation and migration approval are still required.

## Staging inspection

Run node scripts/migration/verify-staging-host.mjs with the approved staging HTTPS origin for public GET/OPTIONS checks; see docs/migration/step-16-staging-verification.md for limits and exit codes. The checker follows no redirects and sends no credentials or messages. Live integrations and host configuration still require separate verification.

## Maintenance activation

Both FEATURED_CLEANUP_ENABLED and SUBSCRIPTION_REMINDERS_ENABLED default to false. Maintenance commands default to dry runs; reviewed automation must use --apply or --send with the corresponding opt-in. See docs/migration/step-17-maintenance-operations.md for timing, cache locks, retry limits and host approval requirements. No real cron or reminder delivery has been enabled by local verification.

## Retained API token lifetime

SANCTUM_TOKEN_TTL_MINUTES defaults to 1440 minutes (24 hours), bounded to 1..43200. Refresh the reviewed configuration cache before release: the readiness check now rejects an old null token lifetime. Older bearer users may need to log in again; native session lifetime is unchanged. This source stage does not delete or rewrite imported token records. See docs/migration/step-18-api-token-lifecycle.md.

## Account deletion behavior

Native and Filament account deletion now share fresh authorization and credential cleanup. Accounts with salons/subscriptions require separate administrative review and cannot be deleted implicitly through these actions. Existing customer bookings remain with an unlinked user reference; this is not blanket record erasure. Review separate session/reset backend atomicity if the host differs from the tested primary-connection setup. See docs/migration/step-19-account-deletion.md.
