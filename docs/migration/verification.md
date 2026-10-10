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
