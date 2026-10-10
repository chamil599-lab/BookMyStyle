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
