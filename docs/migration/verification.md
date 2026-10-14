# Verification baseline

Captured 10 October 2026, Asia/Colombo. Failures are recorded rather than repaired in this inventory step.

| Check | Result | Evidence / implication |
| --- | --- | --- |
| Laravel bootstrap/routes | Passed | 233 routes in baseline/laravel-routes.json |
| Scheduler registration | Zero events | baseline/schedule.json; two intended commands exist only in old console kernel |
| Existing Pest suite | 13 passed, 22 failed, 56 assertions | Private pest-baseline.log; about 63 seconds, in-memory SQLite |
| React production build | Passed | Private react-build.log; large bundle warning, about 1.23 MB minified JS / 323 KB gzip |
| Root Laravel/Filament asset build | Passed | Private laravel-build.log; existing empty app JS chunk and outdated Browserslist warning |
| Local MySQL setup | Created | book-my-style, initially empty; no migrations/seeds/import |
| Actual application-data backup | Passed after import | 35 tables; private backup 20261010-232903 |
| Restore drill | Passed | Isolated MySQL restore; all schemas, counts, record fingerprints match |
| Browser/visual journeys | Not performed | Database available; fixture accounts and visual capture remain pending |
| PHP helper syntax | Passed | create-empty-database.php and capture-baseline.php |

Test failures largely concern starter-kit routes/views no longer aligned with the React redirect architecture: named dashboard/profile/auth/verification routes are missing, expected HTTP 200 responses are redirects, and settings components reference profile.edit. These failures predate migration. Do not count them as new migration regressions, but do replace or adapt coverage as features move back to Blade/session auth.

No dependency vulnerability audit or live penetration test was performed. Current locked package versions are recorded for later compatibility/security review.

## Gate before step two changes

- [x] Import the user's real data into the created database.
- [x] Capture the populated database, confirm schema/media references and migration history, and verify an isolated restore.
- Capture role-specific browser behavior without sending real notifications or payment actions.

The populated-data backup and restore gate is now satisfied. See import-verification.md. Browser/visual parity checks remain pending; two index migrations are recorded but have not been executed.

## Step three verification — 11 October 2026

Shared booking/subscription services are connected to existing API, Livewire, Blade checkout, and Filament persistence/actions. The user approved retaining the paid plan while requests are pending, scheduling same-plan renewals after expiry, and starting approved different-plan changes immediately. See [step-three changes](step-3-shared-services.md).

| Check | Result |
|---|---|
| Focused security and shared-service tests | 47 passed, 159 assertions; isolated SQLite and fake delivery |
| Final full suite | 60 passed, same 22 pre-existing failures, 215 assertions |
| Local MySQL concurrency | Four races passed: create, reactivate, quota across two salons, duplicate subscription request |
| Blade compilation | Passed; compiled cache cleared afterward |
| Changed PHP formatting | Passed Pint |
| Imported data aggregate inspection | Counts unchanged; no FK orphans or missing media; transactional tables |
| Imported schema changes | None; 51 migration rows remain |

Concurrency fixtures and migrations ran only in uniquely named synthetic test databases. Private logs and worker results remain under `.local/migration-baseline/`. Browser parity and deployment/security gates listed above remain outstanding.

## Step four verification - 11 October 2026

See [Blade authentication changes and compatibility](step-4-blade-auth.md).

| Check | Result |
|---|---|
| Full automated suite | 110 passed, 376 assertions; isolated SQLite and fake delivery |
| Real HTTP smoke test | 12 checks passed, including Livewire login, cookie flags, CSRF rejection, profile/dashboard access and logout |
| Root Laravel/Filament asset build | Passed; existing empty app JS and outdated Browserslist warnings |
| Blade compilation | Passed; compiled cache cleared afterward |
| Changed PHP formatting | Passed Pint |
| Imported database inspection | Unchanged: 35 tables; users 10, salons 10, services 6, bookings 10, subscriptions 5, media 6, migration records 51 |
| Import integrity | No foreign-key orphans, missing media originals/conversions, or nontransactional tables |
| Imported schema changes | None; pending index migrations were not executed |

The earlier starter-kit failures are resolved by restored Blade authentication/settings routes. The homepage test now verifies the intended React redirect during migration. The HTTP test caught and led to fixing an early proxy-configuration lookup that framework tests missed. Google credential validation is mocked in identity tests; live Google consent, SMTP delivery, production browser parity, and the broader security audit remain outstanding. Source and runtime artifacts are recorded privately under `.local/migration-baseline/`.

## Step five verification - 11 October 2026

See [public Blade pages and cutover notes](step-5-public-blade.md).

| Check | Result |
|---|---|
| Final full suite | 138 passed, 510 assertions |
| Real HTTP checks | 24 passed: auth/session/CSRF and all public HTML, sitemap and robots endpoints |
| Real Chrome checks | 8 passed: stylesheet loading, mobile menu, theme toggle, mobile home/detail overflow, live available slots, actual guest booking submission, no uncaught JavaScript errors |
| Visual inspection | Private desktop/home/detail and mobile/home/detail/booking fixture screenshots; inspected mobile home/booking and desktop salon |
| Root asset build | Passed; storefront CSS 24.61 KB / 5.94 KB gzip; existing empty app JS and old Browserslist warnings |
| Blade compilation | Passed; compiled cache cleared afterward |
| Formatting/syntax | Changed PHP passed Pint; browser script passed Node syntax check |
| Imported data | 35 tables; users 10, salons 10, services 6, bookings 10, subscriptions 5, media 6, migration records 51; counts unchanged |
| Import integrity | No FK orphans, missing media originals/conversions, or nontransactional tables |
| Imported schema changes | None; pending migrations remain unexecuted |

The longer full suite exceeded the CLI's 128 MB memory limit during Filament rendering. The successful run used `php -d memory_limit=512M vendor/bin/pest --compact`; production memory configuration was not changed. Synthetic fixture tests suppress delivery and never use imported data. Browser artifacts are private under `.local/migration-baseline/auth-smoke-20261010_193918_112ca2/` (artifact timestamps use host UTC before Laravel boots). Automated checks establish behavior, not pixel-perfect React parity or production Apache/email/Google/dependency-audit readiness. Remaining customer pages and dashboard bookmark cutover remain the next gates.

## Step six verification - 11 October 2026

See [native customer dashboard and account journeys](step-6-customer-blade.md).

| Check | Result |
|---|---|
| Final full suite | 155 passed, 656 assertions; CLI memory 512 MB |
| Customer regression coverage | 17 tests covering private routes, role/verification gates, native profile/password aliases, booking classification/API parity, guest identity, cross-account/repeated cancellation, hidden prices, deleted services, pagination, favorites, notifications, password revocation and malformed API identifiers |
| Real HTTP checks | 41 passed plus successful browser runner; includes customer pages, private cache directives, foreign booking denial, CSRF failures, account aliases and existing public/auth checks |
| Real Chrome checks | 19 passed; actual guest booking, customer session login, cancellation confirmation/submission/history, favorite removal, notification read/delete/clear, desktop/mobile layouts and no uncaught JavaScript errors |
| Visual inspection | Inspected desktop and mobile customer dashboard and mobile history fixture screenshots |
| Root assets | Build passed; storefront CSS 25.34 KB / 6.01 KB gzip; existing empty app JS and old Browserslist warnings |
| Blade compilation | All templates cached successfully; generated cache cleared afterward |
| Formatting/syntax | Changed PHP passed Pint; Node syntax and git whitespace checks passed |
| Imported data | Counts unchanged: 35 tables, users 10, salons 10, services 6, bookings 10, subscriptions 5, media 6, migrations 51 |
| Import integrity/schema | No FK orphans, missing media originals/conversions or nontransactional tables; no migrations, seeds or imported writes |

Final real HTTP/browser artifacts are private under `.local/migration-baseline/auth-smoke-20261010_200139_f191ec/`. Fixtures and their temporary credentials are synthetic, ignored and never exposed as public assets. Real mail is suppressed. Browser tests account for the cancellation notification generated during the journey rather than assuming a single notification remains. Header tests assert no-store/private directives and permit additional restrictions applied by the session layer.

Customer React redirects are removed. Profile/password use the established native forms; notifications refresh on page visits/actions rather than the old SSE stream. Full Filament resource parity, admin/owner bookmarks, production integrations, deployment and broader security audit remain outstanding. No production deployment was performed.

## Step seven verification - 11 October 2026

See [secure Filament management foundation](step-7-filament-management.md).

| Check | Result |
|---|---|
| Final full suite | 175 passed, 749 assertions; CLI memory 512 MB |
| Management regressions | 20 tests: direct administrative create/edit denial, cross-owner resources, actual Filament create/save forms, creator/editor protection, administrator creation without personal plan, salon/service/staff quotas, expiry recovery, administrator deletion protection, legacy bookmarks, calendar model/record forgery, feature/date range limits and revoked approval |
| Real HTTP checks | 67 passed plus successful browser runner; owner/admin session handoff, catalogue/booking/subscription lists, create screens, owner user-creation denial, role bookmark redirects and Filament private cache headers added to prior customer/public/auth checks |
| Real Chrome checks | 25 passed; both roles enter their Filament dashboard through native login, see services, and render mobile lists without page overflow; existing customer/public journeys still pass, with no uncaught JavaScript errors |
| Visual inspection | Settled desktop owner dashboard and mobile administrator services screenshots inspected; responsive sidebar state is allowed to settle before capture |
| Root assets | Build passed; existing Browserslist and empty app JS warnings remain |
| Blade and source | All templates compile; compiled cache cleared; changed PHP passed Pint, Node syntax and git whitespace checks passed |
| Imported data | Counts unchanged: 35 tables, users 10, salons 10, services 6, bookings 10, subscriptions 5, media 6, migrations 51 |
| Import integrity/schema | No FK orphans or missing media originals/conversions; no nontransactional tables, imported migrations, seeds or record edits |

Final HTTP/browser artifacts are private under `.local/migration-baseline/auth-smoke-20261010_201745_5c2949/`. All fixture accounts and temporary credentials are synthetic; mail is suppressed. Native profile and password forms remain the account settings destination. Legacy API/write/nested routes and React source are retained during staged retirement. Analytics financial definitions, remaining package/user/subscription actions, production integrations, live MySQL catalogue concurrency/load testing and full deployment gates remain open. This checkpoint does not claim complete dashboard parity or a complete security audit.

## Step eight verification - 11 October 2026

See [Filament reporting and administrative actions](step-8-filament-reporting.md).

| Check | Result |
|---|---|
| Full suite | 189 passed, 838 assertions; CLI memory 512 MB |
| Reporting/action regressions | 14 passed, 89 assertions; scheduled/expired/pending buckets, retained payment history, exact daily windows/month-end rollover, deleted service value, foreign/invalid filters, revoked analytics feature, scoped years, package edit/quote preservation and validation, removed automatic batch approval, archived history, customer approval/stale rejection, protected administrator editing, unchanged password/audit fields, active subscription cancellation, one-time trial, safe package archival, creation roles and availability dates |
| Real HTTP | 72 passed plus successful browser runner; analytics and package edit added to existing public/auth/customer/owner/admin checks |
| Chrome | 31 passed, zero uncaught JavaScript errors; both roles' analytics chart initialization, live 7-day filtering and mobile overflow checks included |
| Visual inspection | Final mobile owner analytics and desktop admin analytics screenshots inspected with corrected estimate labels and settled responsive layout |
| Root assets | Build passed with Vite 7.3.7 and pinned locally bundled Chart.js 4.4.8; existing empty app JS warning remains |
| Root npm audit | Initially 11 vulnerable dependencies (1 critical, 9 high, 1 moderate); compatible updates within existing ranges applied; final full root audit reports zero known vulnerabilities |
| Templates/source | All Blade views compile, cache cleared; changed PHP passes Pint, browser script syntax and git whitespace checks pass |
| Imported data | 35 tables; users 10, salons 10, services 6, bookings 10, subscriptions 5, media 6, migrations 51; counts unchanged |
| Import integrity | No FK orphans, missing media originals/conversions or nontransactional tables; no imported migrations, seeds or record writes |

Final HTTP/browser artifacts are private under .local/migration-baseline/auth-smoke-20261010_204431_dd4d19/. The summary contains 72 HTTP checks plus one successful browser-runner flag; its browser summary contains 31 checks. Fixture credentials remain synthetic/ignored and mail is suppressed. Root audit evidence is private in step-8-npm-audit.json and step-8-npm-audit-final.json. Browser testing exposed and resolved reactive Chart.js cleanup errors rather than ignoring them. The fixture server uses a 30-second request timeout and 512 MB memory for cold Filament renders.

No production deployment was performed. The separate React/PHP dependency audits, reporting API reconciliation/retirement, exact visual/filter parity, notification/WhatsApp workflows, calendar interaction/timezone comparison, account retention decisions, mail/payment integration, production MySQL archive/request concurrency and load tests remain open. Native values are estimates or recorded subscription amounts, not certified cash receipts/profit. See the stage document for these financial definitions and the retained legacy surfaces.

## Step nine verification - 11 October 2026

See [notifications, WhatsApp and calendar](step-9-notifications-calendar.md).

| Check | Result |
|---|---|
| Full suite | 200 passed, 898 assertions; CLI memory 512 MB |
| New regressions | 11 tests, 60 assertions: account-scoped notification actions, revoked verification/approval, escaped imported content, safe API destinations, correct service/audience links, expired owner inbox, WhatsApp normalization and cross-owner denial, timezone-independent calendar dates including midnight, forbidden calendar mutations and revoked SSE tokens/accounts |
| Real HTTP | 76 passed plus successful browser runner; management inboxes added to prior public/auth/customer/owner/admin checks |
| Chrome | 44 passed, zero uncaught JavaScript errors; inbox read/delete, bell refresh, safe WhatsApp URLs, calendar popup in Los Angeles browser timezone, existing customer journeys and analytics included |
| Assets | Both root Laravel/Filament and retained React builds passed. Root empty app chunk warning remains; retained React build reports outdated Browserslist data and a large bundle |
| Templates/source | All Blade templates compile and compiled cache is cleared; changed PHP passes Pint, browser script syntax and git whitespace checks pass |
| Imported data | Read-only aggregate counts unchanged: 35 tables; users 10, salons 10, services 6, bookings 10, subscriptions 5, media 6, migrations 51 |
| Import integrity | No FK orphans, missing media originals/conversions or nontransactional tables; no imported migrations, seeds or record edits |

Final synthetic HTTP/browser artifacts: `.local/migration-baseline/auth-smoke-20261011_031641_0618bb/`. Credentials and fixture data remain private/ignored; mail is suppressed and no WhatsApp message is sent. Calendar popup and mobile owner inbox screenshots were inspected. The initial review exposed a stale unread badge after management actions; Livewire events now refresh it immediately, with Chrome checking the result. Snapshot timing allows modal animations to settle.

No production deployment was performed. Legacy APIs/React remain, and native notifications use polling rather than push. Real mail/payment delivery, production SSE worker capacity/revocation, MySQL concurrency/load testing, broader dependency/media security audits, account retention decisions and final visual/route parity remain release gates. No database date/time conversion was performed.

## Step ten verification - 11 October 2026

See [management route retirement and native renewal](step-10-legacy-route-retirement.md).

| Check | Result |
|---|---|
| Full suite | 215 passed, 1,365 assertions; CLI memory 512 MB |
| Retirement/recovery regressions | 15 passed, 462 assertions; all 56 documented management API methods return 410 and leave business tables unchanged; role/verification/approval checks, stale account objects, retired nested/session writes, preserved GET bookmarks, active LKR plans, expired recovery, duplicate requests, forged subscription fields, unavailable packages, bank references and amount bounds |
| Native request/approval | Existing shared-service regression now submits the Blade renewal form and approves through the actual Filament table action; renewal starts after the current period |
| Real HTTP | 88 passed plus successful browser runner; retired API read/write, retired CSRF-protected session writes, plans/checkout, missing-CSRF rejection, expired recovery and preserved current plan after browser renewal added to prior checks |
| Chrome | 50 passed, zero uncaught JavaScript errors; native plan state, mobile plans/checkout, conditional payment-reference field, actual renewal submission and pending state added to existing workflows |
| Root assets | Build passed; existing empty app chunk warning remains. Subscription/error views and bell included in storefront CSS scanning |
| Templates/routes/source | Blade templates and routes cache successfully, caches cleared; changed PHP passes Pint, browser script syntax and git whitespace checks pass |
| Imported data | Read-only counts unchanged: 35 tables; users 10, salons 10, services 6, bookings 10, subscriptions 5, media 6, migrations 51 |
| Import integrity | No FK orphans, missing media originals/conversions or nontransactional tables; no imported writes/migrations/seeds |

Final synthetic artifacts: `.local/migration-baseline/auth-smoke-20261011_125919_19b697/`. Mail is suppressed; only the private fixture database is mutated. The first Chrome attempt timed out navigating to analytics while the full suite ran; the final runner allows 45 seconds for page navigation and runs separately from the suite. Other CDP operations remain bounded to 15 seconds. A global variable redeclaration and a body-read race during form navigation were corrected in the browser harness. Final desktop plan selection and mobile checkout screenshots were inspected. This is a local test timeout adjustment, not production performance evidence.

The retained React build/dependencies are unchanged in this stage; its management screens now receive explicit retirement responses. External consumers and deployed frontend domains must be reviewed before releasing the breaking API change. Remaining integration, dependency/media security, retention, MySQL load/concurrency and production release gates are described in the stage document. No deployment was performed.

## Step eleven upload hardening and audit

See [step eleven](step-11-upload-security-audit.md) for the changes, dependency findings and release gates.

## Verification

- Full isolated SQLite suite: 219 tests passed, 1,391 assertions.
- Four new upload tests cover raster storage, SVG/script disguises, excessive dimensions, unsafe URL schemes and revoked cached roles.
- Both root and React production builds passed. Existing empty root app chunk and large React chunk warnings remain.
- Synthetic HTTP checks: 89 successful check flags (including the browser-runner flag). Chrome: 50 successful flags and zero uncaught JavaScript errors.
- Browser artifacts: .local/migration-baseline/auth-smoke-20261011_130947_9ad6d7/.
- Imported database read-only inspection: 35 tables; users 10, salons 10, services 6, bookings 10, subscriptions 5, media 6, migrations 51. No foreign-key orphans or missing media originals/conversions; no nontransactional tables. No imported records, schema or files were changed.
- Changed PHP passes Pint and Git whitespace checks pass. Apache execution blocking still requires verification on the actual deployment host.

No production deployment was performed. Unrelated pre-existing image deletions and untracked files are excluded from this checkpoint.

## Step twelve PHP security upgrades

See [step twelve](step-12-php-security-upgrades.md) for resolved versions, installation details and release gates.

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

## Step thirteen retained React security upgrades

See [step thirteen](step-13-react-security-upgrades.md) for exact versions and release gates.

## Final verification

| Check | Result |
|---|---|
| Retained React npm audit | Zero findings, down from 11 |
| Root npm and installed Composer audits | Zero findings; Composer also reports no abandoned packages |
| Builds | Retained React and root Laravel/Filament builds passed; existing large React chunk and empty root app chunk warnings remain |
| Proxy security regressions | Four Node tests passed: strict default/remote TLS, explicit local exception, scheme/credential rejection and HTTPS-only remote proxies |
| Retained React browser | 14 baseline checks; 16 final checks; zero uncaught JavaScript errors; measured layout metrics preserved and explicit display-font family verified |
| Retained visual inspection | Before/after mobile home screenshots inspected; heading fallback correction verified |
| PHP regression suite | 220 passed, 1,396 assertions; isolated SQLite and suppressed mail |
| Native HTTP/Chrome | 89 successful check flags including browser runner; 50 Chrome check flags; zero uncaught JavaScript errors |
| Imported integrity | Read-only checks unchanged: 35 tables, users 10, salons 10, services 6, bookings 10, subscriptions 5, media 6, migrations 51; no FK orphans, missing media originals/conversions or nontransactional tables |
| Source checks | Browser harness syntax and authored-file Git whitespace checks passed |

Native browser artifacts: .local/migration-baseline/auth-smoke-20261012_022023_bb4f3c/. Retained browser artifacts: react-smoke-before/ and react-smoke-after/ under the same private migration directory. The final retained fixture includes a minimal non-networking Google script interface for the fake client ID; it does not verify OAuth. Chrome request-interception cancellations during navigation are tracked separately from JavaScript errors.

An initial PHP process run concurrently with the root build ended after one progress marker without a diagnostic report. The standalone rerun completed successfully; its log is step-13-php-tests-final.log. This is local verification evidence, not production performance evidence.

Checkpoint: migration-step-13-react-security-upgrades. Private full-history rollback bundle: .local/migration-baseline/step-13-react-security-upgrades.bundle. Restore the selected source checkpoint and install its matching package lockfiles, rebuild root/client assets as appropriate, and follow the rollback runbook. No imported-data restoration is needed for this source/dependency stage. Unrelated pre-existing image deletions and untracked files remain outside the checkpoint.

## Step fourteen production readiness

- Final full PHP suite: 227 passed, 1,424 assertions; SQLite isolated and mail suppressed. Private log: .local/migration-baseline/step-14-php-tests.log.
- Seven new regressions cover readiness/manual-gate separation, secret-free failures, missing assets, wildcard origins/proxies, API identity/login response privacy and actual production/local CORS configuration.
- Default preflight reports successfully. Strict local preflight exits 1 as expected: production environment, debug mode, application URL and CORS origins fail production prerequisites. Report values are withheld; private output: step-14-readiness.json.
- Private-path config:cache, route:cache and view:cache passed with in-memory SQLite and suppressed mail. The first cache command used a Windows absolute override that Laravel prefixed with the project path; the corrected relative override passed. The final read-only preflight also works against private cached configuration. Cached configuration files may contain secrets and remain inside ignored .local; never publish them.
- PHP style and authored Git whitespace checks passed. No frontend rendering or dependency changes in this stage; builds/browser evidence remains from step thirteen.
- No imported database writes, migrations or seeds, and no production environment changes/deployment. Two pending index migrations and all host/integration gates remain open. Preflight does not query the database or approve release.

Checkpoint: migration-step-14-deployment-readiness. Private full-history rollback bundle: .local/migration-baseline/step-14-deployment-readiness.bundle. Rollback source/configuration defaults using the matching checkpoint and release assets; no database restoration is required for this stage. Unrelated image deletions and untracked local files are excluded.

## Step fifteen index rehearsal

- Final full PHP suite: 232 passed, 1,447 assertions (77.60 seconds), SQLite isolated and mail suppressed. Private log: .local/migration-baseline/step-15-php-tests-final.log. Five focused regressions passed separately (23 assertions).
- Final isolated MySQL artifact: .local/migration-baseline/index-check-20261012_185926_3198ce/. Fresh backup matched source schemas and all table record fingerprints before migration; 35 tables checked. Six composite index definitions verified; existing site-visit occurred_at index preserved.
- Business records, columns, foreign keys and original indexes preserved; repeat migrate made no changes; rollback matched the exact pre-migration snapshot; reapplication matched the first result; final source snapshot unchanged. Private report: step-15-final-rehearsal.json.
- Temporary MySQL credential option files remaining across rehearsal/backup directories: zero. PHP style and authored Git whitespace checks passed. No frontend/dependency changes; browser/build evidence remains from step thirteen.
- Initial clone configuration inherited the source connection name, causing seven source index additions while migration entries were written to the copy. Automatic approval review rejected source repair; the user explicitly approved restoring the original seven-index state. Exact index definitions and record fingerprints were checked, those indexes were removed and the original schema/history/data verified. Repair report: step-15-source-repair.json. This stage must not be described as having performed no source DDL.
- Older backup mismatch, Windows path normalization and replication dump settings were also corrected. The final helper strips inherited connection names, validates copy routing and blocks writes on the source Laravel connection. It refuses executable source database objects and unsafe dump statements.
- Source migration history remains 51 entries with the two index migrations pending. No production deployment occurred. Host/load/lock-duration checks and actual migration/release approval remain open.

Checkpoint: migration-step-15-index-rehearsal. Private full-history rollback bundle: .local/migration-baseline/step-15-index-rehearsal.bundle. The source schema was already restored under explicit user approval; do not restore old data or rerun down migrations against the source to revert this source-code stage. Unrelated image deletions and local untracked files remain excluded.

## Step sixteen cached native release and staging tools

- Final cached native fixture: .local/migration-baseline/auth-smoke-20261014_015658_ea9065/. All 94 HTTP/check flags passed, including actual cached configuration/routes, compiled views and unauthorized API identity privacy. Chrome: 50 flags passed, zero uncaught JavaScript errors. Synthetic SQLite accounts and suppressed mail; no source MySQL migrations or writes.
- The Oct 12 cached rerun was interrupted before producing its final report. The Oct 14 rerun completed successfully; authoritative fixture summary.json and browser-summary.json are UTF-8. Shell-redirection logs may be UTF-16 on Windows and are not the authoritative JSON artifacts.
- Node tests: ten passed (six staging probe regressions plus four retained development proxy regressions), using synthetic responses only. Syntax checks passed for both new Node files.
- Targeted real Laravel auth response tests: three passed, fifteen assertions, on isolated SQLite. A previously failing unauthorized response skipped middleware headers; the exception response callback now preserves private/no-store and noindex/nofollow headers on api/auth/* failures.
- PHP style and authored Git whitespace checks passed. No frontend/dependency changes require new asset builds in this stage; cached native browser coverage verifies the existing root/Filament assets.
- Live staging URL/access details remain unsupplied. No live host probe, real OAuth login, external mail delivery, payment submission or production deployment is claimed. The prepared host checker always leaves release_ready=false and manual host/integration gates open. The obsolete second React deployment guide now points to the native runbook.

Final full PHP suite: 233 passed, 1,452 assertions (104.31 seconds); isolated SQLite with suppressed mail. Private log: .local/migration-baseline/step-16-php-tests-final.log.

Checkpoint: migration-step-16-cached-staging-checks. Private full-history rollback bundle: .local/migration-baseline/step-16-cached-staging-checks.bundle. Revert the source checkpoint to undo the exception header change and test tools; no imported-database rollback is required for this stage. Unrelated image deletions and untracked local files are excluded.

## Step seventeen maintenance operations

- Full PHP suite: 240 passed, 1,492 assertions (84.63 seconds). Private log: .local/migration-baseline/step-17-php-tests-final.log. Seven focused maintenance regressions passed separately (40 assertions), using SQLite in memory and faked notifications.
- Runtime schedule:list checked with isolated SQLite/array cache/mail: zero events with both flags disabled; hourly featured cleanup and daily 09:00 Asia/Colombo reminders when explicitly enabled. Schedule listing did not execute either command.
- Dry-run and disabled-host gates preserve records and send no notifications; successful reminder retries, changed expiry, approved renewals, owner verification/approval, future-start exclusion and calendar-day boundaries are covered. Featured active-scope exact start/expiry semantics and opt-in cleanup are tested.
- Commands now intentionally default to dry runs; actual side effects require both host flags and explicit --apply/--send options. Both flags remain disabled by default, including in .env.example. No real .env changes, imported MySQL maintenance, cron installation, real reminders or production deployment occurred.
- PHP style and authored Git whitespace checks passed. No frontend/dependency changes; the existing native cached HTTP/Chrome evidence remains from step sixteen.
- Persistent host cache locks, SMTP, cron/restarts, delivery/partial-failure behavior and staging remain release gates. Cache success markers reduce duplicate sends but do not provide an exactly-once delivery guarantee.

Checkpoint: migration-step-17-maintenance-operations. Private full-history rollback bundle: .local/migration-baseline/step-17-maintenance-operations.bundle. No imported-database restoration is needed for this source stage. Review automation flags before reverting command signatures or redeploying; private artifacts and unrelated image deletions remain outside the checkpoint.

## Step eighteen API token lifecycle

- Full PHP suite: 250 passed, 1,526 assertions (80.47 seconds). Private log: .local/migration-baseline/step-18-php-tests-final.log. Focused lifecycle/readiness tests: fourteen passed, 49 assertions, using SQLite in memory, faked notifications and a synthetic Google verifier.
- Actual API registration/password login/Google issuance have explicit expiry timestamps. Fresh tokens work; explicit-expired and legacy age-expired tokens are rejected. Legacy record expiry is not rewritten and rejection does not delete the record. Bearer logout preserves other device tokens; a fixture transient-token session route exercises native session logout.
- Password change/reset revoke all bearer tokens through the existing user hook. TTL settings cannot disable expiration with zero/negative/invalid values; old null cached expiration fails the preflight. The read-only local preflight's finite_api_token_lifetime check passed; other production gates remain pending. Private report: step-18-readiness.json.
- PHP style and authored Git whitespace checks passed. No frontend/dependency changes; native cached browser evidence remains from step sixteen.
- Initial session fixture combined native web AuthenticateSession with auth:sanctum and hit a RequestGuard method conflict before the controller. The corrected fixture uses web authentication with a transient token to isolate the controller session branch; stateful SPA middleware is not enabled or claimed as tested in this stage.
- No imported token rewrite/deletion, source database migration, actual .env change, external OAuth/mail test or production deployment occurred. Once effective configuration is activated, older bearer users may need to log in again. Expired record pruning remains unconfigured.

Checkpoint: migration-step-18-api-token-lifecycle. Private full-history rollback bundle: .local/migration-baseline/step-18-api-token-lifecycle.bundle. Revert source/configuration and rebuild the reviewed configuration cache to roll back this policy; do not restore old user data or tokens to undo a frontend/source stage. Unrelated image deletions and untracked local files remain outside the checkpoint.

## Step nineteen account deletion authorization and cleanup

- Full PHP suite: 261 passed, 1,580 assertions (84.65 seconds). Private log: .local/migration-baseline/step-19-php-tests-final.log. Focused deletion/profile/reporting regressions: thirty passed, 165 assertions; SQLite isolated and mail/notifications faked.
- Eleven new deletion cases verify token/notification/reset/database-session cleanup, other-account isolation, stale role/password rejection, linked salon/subscription protection, stale actor/target policy decisions, rollback after a synthetic delete failure, all three real Filament actions and customer booking retention with null user_id. Existing native profile deletion and wrong-password behavior still pass.
- Initial full run found one moderation contract regression: a protected onboarding-trial rejection raised authorization before its existing established-account validation. Rejection now checks management authorization, validates established-account restrictions, then delegates final deletion authorization/cleanup. The final full suite passed.
- Both profile forms state booking retention accurately. Accounts with salon/subscription history are intentionally protected from these deletion paths, including former owners. Bulk deletion is transactional per permitted record rather than for the complete selection; host stores on separate connections/backends require separate consistency review.
- PHP style and authored Git whitespace checks passed. No frontend asset/dependency changes require a rebuild; native browser evidence remains from step sixteen, while new Filament/Volt action paths are exercised directly through Livewire tests.
- No real account deletion, imported-data mutation/migration, actual mail delivery or deployment occurred. Live staging, host and integration release gates remain pending.

Checkpoint: migration-step-19-account-deletion. Private full-history rollback bundle: .local/migration-baseline/step-19-account-deletion.bundle. No imported-database restore is needed for this source stage; do not restore old personal data/credentials to undo source changes. Private fixtures and unrelated image deletions remain excluded.

## Step twenty imported-data native pages

- Fresh isolated MySQL backup/restore, both pending index migrations, no-op second migration, rollback and reapplication passed. Source connection routing and read-only application guard were verified; all-table source record fingerprints matched before/after the successful run.
- Native GET checks: 74 passed, four public salon detail pages, all ten imported accounts (two administrators, five owners, two customers, one other). Eleven owner access gates matched expectations. All ten stored password formats were bcrypt; no actual password login was attempted.
- Initial checker assumptions were corrected: the panel root redirects to the admin dashboard, inactive owner subscriptions forbid bookings, and denied Livewire mounts require redirect state reset between in-process requests. These were harness changes; application authorization was preserved.
- Full PHP suite: 261 passed, 1,580 assertions (87.26 seconds). Private log: .local/migration-baseline/step-20-php-tests.log. Script syntax/style and authored Git whitespace checks passed.
- Private successful report: .local/migration-baseline/imported-pages-20261014_130239_0da1d7/summary.json. Authentication is injected; browser execution, real login/OAuth, delivery/payment integrations, visual parity and actual host verification remain separate gates. No source migrations, account mutations, external delivery or deployment occurred.

Checkpoint: migration-step-20-imported-pages. Private full-history rollback bundle: .local/migration-baseline/step-20-imported-pages.bundle. No source database restore is needed to revert these scripts/docs. Isolated copies/backups and failed artifacts remain private and retained; unrelated workspace changes are excluded.

## Step twenty-one browser journeys with imported records

- Fresh guarded MySQL copy rehearsal passed; actual Chrome form login/logout succeeded for all ten imported identities using temporary passwords written only to the copy. Browser checks: 156 passed, 50 private screenshots, zero uncaught JavaScript errors. Private report: .local/migration-baseline/imported-browser-20261014_131224_ea86e6/summary.json.
- Four public salon pages, desktop/mobile role-dependent dashboards, profile identity/no-store response, permitted customer/management lists, wrong-password rejection, guest protection, logout CSRF rejection and HTTP-only/Lax session cookie were checked. Browser interception blocked 121 external page requests; no external integration or visual/asset parity claim is made. A missing hero image is visible in the local home capture; existing unrelated image deletions are untouched.
- All source record fingerprints matched. Copied business records matched after fixtures/login/logout, excluding operational sessions/cache/visits and the logout-rotated remember_token. Copy passwords are included in that comparison. The HTTP worker config contains only the copy connection, with copy sessions/cache/locks and private logs/views. Mail is array-only, Google disabled and maintenance flags off.
- Focused authentication/privacy regressions: 36 passed, 146 assertions (55.82 seconds); log: .local/migration-baseline/step-21-auth-tests.log. Three new runner boundary tests passed. PHP style/syntax, Node syntax and authored Git whitespace checks passed. Application/dependency/assets are unchanged; step twenty's 261-test full-suite baseline remains applicable.
- First browser run stopped on checker syntax in an asynchronous CSRF probe, before that probe sent a request. Failed-run source integrity passed; corrected full run passed. Temporary runtime database-credential files from both executions were removed; the helper now removes them after server shutdown. Other private artifacts/copies are retained.
- No source migrations/password/account/session/token mutations, actual delivery, OAuth/payment execution or deployment occurred. Original password knowledge, resource mutations, asset parity, production TLS/proxy/cookies, real integrations and live staging remain separate gates.

Checkpoint: migration-step-21-imported-browser. Private full-history rollback bundle: .local/migration-baseline/step-21-imported-browser.bundle. Reverting scripts/docs requires no imported database restoration; unrelated workspace changes remain excluded.
