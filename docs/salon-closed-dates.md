# Salon closed dates

For the combined feature history, local preview status, commits and hosted upgrade checklist, see [Salon closed dates and map links](salon-features-2026-10-29.md).

Owners open Salon Management > Salons > Edit > Closed dates. Select individual days or use From/To to close a range. Red days are closed; select a red day again to reopen it, then save. An optional reason applies to newly closed dates and remains private. Past closures are retained.

Pending and approved appointments prevent a closure. The error lists affected booking IDs, dates, times and customers; reschedule or cancel them through booking management first. Historical completed/rejected appointments do not prevent closure. Calendar changes apply when saved. If multiple managers edit simultaneously, the last saved selection takes effect.

Website customers cannot select closed dates. Shared server validation rejects pending/approved appointments on closed dates, including API and management submissions. Completing/rejecting historical records remains possible. API salon details and working-hours responses include closed_dates (YYYY-MM-DD strings); booked-times responses include is_closed. External API clients must consume these fields to disable dates in their own calendars.

## Hosted upgrade

Deploy through the reviewed feature PR and normal release process. Back up the production database and preserve APP_KEY, environment, and uploads. Review the host's actual pending migrations, including 2026_10_29_000001_create_salon_closed_dates_table, then apply the approved set through the normal upgrade migration process (php artisan migrate --force); do not use fresh-install initialization or demo seeders. No environment or dependency changes are needed. Build assets and refresh compiled views as part of the release. Test closure, rejection of a new booking, and reopening with controlled accounts before declaring the upgrade verified.

For source rollback, restore the previous release and retain the closure table/data for recovery. Dropping salon_closed_dates loses the saved schedule and permits bookings on those dates; do not reverse the migration as a routine rollback. No production changes are performed by this feature branch.

## Verification

On 29 October 2026, the full Pest suite passed: 418 tests, one platform-dependent directory-symlink test skipped. The final closed-date test file passed separately with seven tests and 43 assertions, including API metadata and modal conflict errors added after the full suite began. Repository-wide Pint and npm run build passed. Tests used an isolated SQLite in-memory database. Interactive browser and hosted smoke checks remain part of review/release verification.
