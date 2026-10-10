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
| Actual application-data backup | Awaiting user import | Empty database dump is only a setup checkpoint |
| Restore drill | Not performed | Required after actual data capture |
| Browser/visual journeys | Not performed | Requires imported database and fixture accounts |
| PHP helper syntax | Passed | create-empty-database.php and capture-baseline.php |

Test failures largely concern starter-kit routes/views no longer aligned with the React redirect architecture: named dashboard/profile/auth/verification routes are missing, expected HTTP 200 responses are redirects, and settings components reference profile.edit. These failures predate migration. Do not count them as new migration regressions, but do replace or adapt coverage as features move back to Blade/session auth.

No dependency vulnerability audit or live penetration test was performed. Current locked package versions are recorded for later compatibility/security review.

## Gate before step two changes

- Import the user's real data into the created database.
- Capture the populated database, confirm schema/media references and migration history, and verify an isolated restore.
- Capture role-specific browser behavior without sending real notifications or payment actions.

Source review, private file preservation, local Git checkpoint and empty database setup can be completed before that import. Step one remains conditional on the populated-data backup and restore gate.
