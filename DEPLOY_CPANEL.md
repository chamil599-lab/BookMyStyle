# Blade and Filament deployment on cPanel

Laravel now serves the public site, authentication and customer pages through Blade/Livewire, and owner/admin management through Filament. The retained React client is a migration reference. Do not copy client/dist into the live document root or install SPA fallback rewrites: those would replace native routes.

This is a release runbook, not confirmation that production is ready. No deployment or imported-database migration has been performed. See docs/migration/step-14-deployment-readiness.md and docs/migration/rollback.md.

## 1. Prepare an isolated release

Use the approved source checkpoint and matching composer.lock/package-lock.json. Build in an isolated directory or CI environment, never over the active application. Preserve the existing APP_KEY; rotating it can invalidate encrypted data and sessions. Keep credentials, backups, .local, development tools and the retained client outside the web root and release artifact.

Validate the host's actual PHP version/extensions with composer check-platform-reqs --no-dev. The project declares PHP 8.2+, while local verification used PHP 8.4.26; the actual host and optimized autoloader remain unverified. Required extensions must satisfy the complete lockfile, including image processing and MySQL.

In the isolated release, install locked dependencies with composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts. Review lifecycle scripts before running package:discover and filament:assets explicitly. Run npm ci and npm run build at the project root. Root public/build is required. No production React build is required for the native routes.

## 2. Configure the server privately

Point the domain document root to the release's public directory; keep app, vendor, .env and storage outside it. If cPanel cannot select this document root, resolve the host layout before release rather than uploading the whole project to public_html. Retain public/.htaccess and Laravel's index.php routing. Only storage and bootstrap/cache need application write permissions; avoid world-writable permissions.

Set APP_ENV=production, APP_DEBUG=false, APP_URL to the real HTTPS domain and FRONTEND_URL to that same native site. Set SESSION_SECURE_COOKIE=true, SESSION_HTTP_ONLY=true, SESSION_SAME_SITE=lax and leave SESSION_DOMAIN unset for a host-only cookie unless a reviewed subdomain requirement exists. Preserve the existing encryption key and configure the actual MySQL database privately.

Use persistent session/cache stores whose tables or services already exist. Configure a real mail transport, sender authentication and delivery monitoring. An SMTP setting alone does not prove delivery. Configure queue workers and supervised restarts if using asynchronous jobs; inspect the application's actual scheduling requirements before installing cron. Restrict TRUSTED_PROXIES to actual proxy IPs/CIDRs, or leave empty for direct hosting. Never trust every proxy.

For the same-origin Blade site, CORS_ALLOWED_ORIGINS may be empty. If a retained external API client is needed, list only reviewed HTTPS origins explicitly. Production no longer defaults to local development origins. Wildcard methods/headers do not grant access without an allowed origin.

Remove public/hot. Do not publish public/index.html, .env, SQL dumps, archives or project manifests. Expose only public media through the intended storage link. Configure the web server to disable all script execution within uploads, including symlink/alias targets; application validation and Apache rewrite rules alone do not prove server enforcement.

## 3. Check the release before switching traffic

Run php scripts/migration/check-deployment.php, then php scripts/migration/check-deployment.php --strict against the host release's effective configuration. The script reads configuration/files only and prints fixed check identifiers and statuses, never secrets. Default mode exits zero when a report is produced; strict mode exits one for failed automated prerequisites, two for initialization failure. Even a strict success leaves release_ready=false because external/manual gates remain unverified. It does not inspect the database, confirm the actual web-server document root, send mail or contact integrations.

Run the read-only migration status command privately. Two pending index migrations remain on the imported local database. Validate those changes and backup restoration on an isolated copy before obtaining release approval for any real database migration. Never run migrate:fresh or seed against imported or production data.

On the isolated release with final environment settings, verify config:cache, route:cache and view:cache; inspect failures before switching traffic. Avoid dumping config:show app because it includes the encryption key. Avoid optimize:clear as a routine step: it also clears the configured application cache. Use targeted config:clear, route:clear and view:clear when necessary. Test the actual cached release, not only an uncached development checkout.

Confirm HTTPS redirects, real Google OAuth callbacks, email verification/password reset delivery, manual payment request and approval behavior, owner subscription renewal/change behavior, role isolation, uploads and storage permissions. OnePay selection currently records a request; it does not prove automatic payment processing. Run production browser and load checks with controlled accounts. Confirm backup/rollback timing and restoration before approval.

## 4. Approved release and rollback

Only after explicit release approval: back up the database/uploads and current source/assets/environment securely; switch the release/document root atomically where supported; restart workers; verify guest, customer, owner and admin routes and monitor errors. Any approved migration must have a separately reviewed recovery plan.

For source-only failure, switch back to the previous complete release with matching dependencies/assets and clear/rebuild its configuration as needed. Do not restore an old database over new customer transactions without a reviewed recovery decision. Maintain the existing APP_KEY across releases.

Laravel guidance: https://laravel.com/docs/12.x/deployment. Project-specific remaining gates are tracked in the migration documents.

## Local index rehearsal evidence

See docs/migration/step-15-index-rehearsal.md: final isolated migration/rollback/reapplication and record preservation checks passed. Six composite indexes are added on the imported schema, with its existing site-visit index preserved. A transient source-index incident was restored with explicit user approval and is documented. Both source migration entries remain pending; production backup/restore, lock-duration validation and migration approval are still required.
