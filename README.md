# BookMyStyle

Salon discovery and booking application built with Laravel, Blade/Livewire and Filament. Public/customer pages use Blade; owner/administrator management uses Filament. Retained APIs support reviewed external clients. There is no React frontend.

## Local development

Requirements: PHP 8.2+ with the extensions required by composer.lock, Composer, a compatible Node release (CI uses Node 22), npm and MySQL. Commit both lockfiles; install with `composer install` and `npm ci`.

Copy `.env.example` to `.env`, configure a disposable local database and generate an application key for a **new installation only**. Preserve the existing APP_KEY when restoring or updating an established installation. Build assets with `npm run build`; use `npm run dev` while developing. Serve through Herd or Laravel with the document root set to `public`.

Review migrations before applying them. Never run `migrate:fresh` or seed against imported/production data. Do not reuse production credentials or customer data for tests. The Pest configuration uses SQLite in memory with array mail/cache/session settings. Run `php -d memory_limit=512M vendor/bin/pest` for tests. Email transport tests are local; actual SMTP delivery is a separate controlled test.

## Repository and release rules

- Keep this repository private. Do not commit `.env`, credentials, database exports, uploads, logs, backups, vendor or node_modules. Reviewed environment templates contain no operational secrets.
- Use branches and pull requests for changes. Main should represent reviewed code; GitHub branch protection and required checks must be configured after the private repository exists.
- Existing workflows run tests/build and check formatting. They have read-only repository permissions and require no deployment credentials. They do not deploy. Validate their first GitHub runs before making them required merge checks.
- Preserve shared `.env`, APP_KEY and uploads across versioned releases. Deploy from matching lockfiles and built assets; migrations and database recovery require separate review.

See [deployment instructions](DEPLOY_CPANEL.md), [security reports](docs/security/), [email readiness](docs/security/email-readiness.md), [operations runbook](docs/security/operations-runbook.md) and [rollback procedures](docs/migration/rollback.md). Local checks do not establish production readiness. Strict script CSP, real integrations, host verification and monitoring/recovery work remain open.

Historical migration records describe earlier architecture and evidence; they are not current deployment instructions. Private evidence remains outside Git under `.local`.
