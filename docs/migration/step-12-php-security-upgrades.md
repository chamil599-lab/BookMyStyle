# Step twelve: PHP dependency security upgrades

The PHP dependency tree is upgraded within the existing application major-version constraints. Composer lifecycle scripts were suppressed during installation; package discovery, cache clearing and asset publication are performed explicitly after reviewing their commands. No migrations, seeds, payment processing or imported-record writes are part of this checkpoint.

## Dependency changes

| Package | New locked version |
|---|---|
| laravel/framework | v12.69.3 |
| filament/filament | v4.15.1 |
| livewire/livewire | v3.8.10 |
| guava/calendar | 2.0.0 |
| spatie/laravel-medialibrary | 11.23.9 |
| laravel/sanctum | v4.3.3 |
| bezhansalleh/filament-shield | 4.3.1 |

Composer resolves against PHP 8.2.0 through config.platform.php, preserving the declared PHP minimum and avoiding Symfony 8 dependencies selected solely because the workstation runs PHP 8.4. Application checks run on the workstation PHP 8.4 runtime; actual PHP 8.2 execution still needs host verification. The calendar stays at its existing exact 2.0.0 version.

The baseline audit reported 70 package/advisory entries across 23 packages. The updated lockfile audit returns no advisories and no abandoned packages. Private update/audit evidence uses .local/migration-baseline/step-12-*; audit results are a point-in-time registry check and do not certify application or host security.

## Remaining release gates

The retained React dependency tree still has 11 findings after the prior compatible updates. Its remaining major upgrades or eventual removal require separate compatibility checks and deployed-consumer review. Production mail/payment/OAuth verification, upload execution enforcement, retention rules, MySQL concurrency/load, the two pending index migrations and production rollback rehearsal remain open. Existing imported data and uploads are preserved. No deployment is performed.

## Local installation and authorization verification

The first update stopped during extraction without an explanatory error. Installation was resumed from the same lockfile; all archives extracted. The optimized-autoloader scan was later stopped because of excessive local filesystem scanning time. A normal local autoloader was generated successfully with optimization temporarily disabled, then config.optimize-autoloader was restored to true. A subsequent no-write install dry run reports nothing to install, update or remove. Deployment must generate its own optimized production autoloader; local tests do not establish its generation time or production performance.

Package discovery, filament:assets and laravel-assets publication ran explicitly with SQLite-in-memory, array cache/session/mail and synchronous queue settings. No Composer lifecycle scripts or migrations were run. Published Filament JavaScript, CSS and font assets are included in this checkpoint; Vite-generated assets remain build outputs.

The first full suite passed 218 tests and failed two request-sequence assertions. Updated Filament denies unauthorized widgets before mounting; updated Livewire rejects calls with missing snapshots. The tests now assert the disabled calendar is rejected at initial mount and populate the edit form before revoking approval, then assert the subsequent save is forbidden and the record unchanged. The application's authorization guards were not relaxed. A new test submits a raster image through the actual native Filament upload form and verifies public storage persistence.

## Final verification

| Check | Result |
|---|---|
| Composer lockfile and installed audits | Zero advisories; zero abandoned packages |
| Manifest/platform | Valid manifest (existing exact calendar-pin warning); all current PHP/extension requirements satisfied |
| Installed versions | No-write Composer install dry run: nothing to install, update or remove |
| Full regression suite | 220 passed, 1,396 assertions; SQLite in memory, PHP 8.4.26, 512 MB CLI memory |
| Native upload | Actual Filament image-cycle form stores a generated raster-image path on the isolated fake public disk |
| Root Vite assets | Build passed; existing empty app chunk warning remains |
| HTTP/Chrome | 89 successful check flags including browser-runner flag; 50 Chrome check flags; zero uncaught JavaScript errors |
| Visual inspection | Desktop owner calendar popup and mobile owner analytics screenshot inspected |
| Routes/views | Cache generation passed; route and compiled-view caches cleared afterward |
| Imported data | Read-only inspection unchanged: 35 tables, users 10, salons 10, services 6, bookings 10, subscriptions 5, media 6, migrations 51 |
| Imported media/integrity | No foreign-key orphans, missing originals/conversions or nontransactional tables; no imported writes |

Final browser artifacts: .local/migration-baseline/auth-smoke-20261011_180554_da8690/. Synthetic database only; mail suppressed. The React source and dependencies are unchanged in this checkpoint, so its previous 11 findings remain unresolved.

Changed PHP passes Pint. Authored files pass Git whitespace checks. A full whitespace check reports two upstream trailing-space literals in the published file-upload.js and rich-editor.js bundles; both files were verified byte-for-byte against the installed vendor bundles and retained intact.

Checkpoint: migration-step-12-php-security-upgrades. Private full-history rollback bundle: .local/migration-baseline/step-12-php-security-upgrades.bundle. Restore the chosen source checkpoint, install its matching locked dependencies and rebuild/publish matching assets using the rollback runbook. No imported-data restoration is needed for this source/dependency-only stage.
