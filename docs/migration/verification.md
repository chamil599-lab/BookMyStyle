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
