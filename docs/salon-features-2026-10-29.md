# Salon closed dates and map links

Reference date: 29 October 2026 (Asia/Colombo).

## Current implementation and Git status

Both features are available in the main project folder served by the local Herd site at `https://bookmystyle.test` and pushed to `origin/feature/salon-map-url`. [Draft PR #2](https://github.com/chamil599-lab/BookMyStyle/pull/2) is open. They have not been merged into `main` or deployed to the hosted site. The user selected cPanel File Manager and temporary Cron Jobs for the hosted upgrade.

The current branch is `feature/salon-map-url`. It was created from the local preview branch and includes both feature commits:

| Feature | Commit | Branch history |
| --- | --- | --- |
| Closed dates | `e60f222` | Applied to `preview/salon-closed-dates` from original feature commit `5d6799f` |
| Map links | `153b0ad` | Added on `feature/salon-map-url` after the closed-date preview |

The original closed-date implementation remains in `.worktrees/salon-closed-dates` on `feature/salon-closed-dates`. That separate worktree is not the folder served by the main Herd domain. Initially, the button was missing locally because the feature existed only in that worktree; applying it to the served folder resolved this.

Existing unrelated local edits were preserved and excluded from the feature commits. The current branch includes the earlier deployment checkpoint and both features, so review its base and commit range when preparing PRs. Follow [the release workflow](releases.md); do not assume this local preview is already a production release.

## Closed dates

1. Log in as the salon owner.
2. Open **Salon Management > Salons > Edit** for the salon.
3. Click **Closed dates** at the top of the edit page, beside the existing actions.
4. Select individual dates, or enter **From / To** and click **Close date range**.
5. Optionally enter a reason for newly closed dates, then submit the dialog.

Red dates are closed. Select a red date again and save to reopen it. Past closures are retained. The calendar supports up to 730 selected dates; closures are full-day and specific to each salon. Recurring weekly closures are not included in this implementation.

Pending or approved appointments prevent closing their dates. The error lists the affected booking IDs, dates, times and customers. Reschedule or cancel those appointments first; the feature never automatically cancels them. Completed and rejected appointments do not prevent closure. A conflicting selection is rejected without partially saving other closures.

The customer calendar disables closed dates. Shared booking validation also rejects pending/approved appointments on closed dates through website, API and management submissions. Reopening a date restores normal availability, subject to opening hours and capacity. Existing salon permissions restrict owners to their own salons. If managers edit closures simultaneously, the last saved selection takes effect.

| Storage/API | Details |
| --- | --- |
| Table | `salon_closed_dates`: salon, date, optional private reason, timestamps |
| Constraint | Unique salon/date pair |
| Public salon and working-hours responses | `closed_dates`: date strings in `YYYY-MM-DD` format |
| Booked-times response | `is_closed`: boolean for the requested date |

External clients must consume these API fields to disable closed dates in their own calendars. Closure reasons are not published.

See [the detailed closed-date reference](salon-closed-dates.md).

## Map links

1. Open **Salon Management > Salons > Edit > Basic Information**.
2. Paste the salon's shared location link into **Map URL**, below **Location**.
3. Save the salon.

The public salon detail page shows a map icon and **View on map** below the address. Both that link and **Get directions** open the saved destination in a new tab. Clearing the field restores address-based Google Maps directions. If there is no valid map URL or address, map links are hidden.

The optional field accepts HTTP/HTTPS URLs up to 2048 characters, including shared short links. Shared salon validation rejects unsafe URL schemes, and legacy unsafe values are not rendered as public links. Salon ownership permissions apply.

The nullable `salons.map_url` column stores the owner's URL. Public salon responses expose the resolved destination in `map_url`, including the address fallback when appropriate. This website/backend update does not change the Android application's code.

See [the detailed map-link reference](salon-map-url.md).

## Migrations and hosted update

| Feature | Migration |
| --- | --- |
| Closed dates | `2026_10_29_000001_create_salon_closed_dates_table.php` |
| Map URL | `2026_10_29_000002_add_map_url_to_salons_table.php` |

Both additive migrations were applied to the verified local Herd MySQL database. This does not establish migration status on the hosted database. The features themselves need no dependency or environment changes. During the fresh hosted-upgrade audit, CVE-2026-45070 was reported for the existing Symfony Mime 7.4.19 lock, so the candidate also includes the targeted 7.4.22 security patch and matching production Composer metadata.

The cPanel update uses three patch archives and a private CLI-only helper. See [the exact upload and cron sequence](../deployment/cpanel/SALON-FEATURES-UPDATE.md). Unlike the fresh-install helper, this process preserves the production key and existing records, runs only the two feature migrations, verifies installed update files, refreshes caches and reopens the site after successful preparation.

Before a hosted update:

1. Prepare and review the intended commits through the normal PR/release workflow, keeping unrelated local edits separate.
2. Back up the hosted database and files. Preserve the production `APP_KEY`, environment configuration and uploaded media.
3. Review the host's actual pending migrations and apply only the approved migration set through the normal upgrade process. Do not use `migrate:fresh`, demo seeders, or fresh-install initialization.
4. Build the release assets, include `public/build`, and refresh compiled views as required by the deployment procedure.
5. Verify owner closure save/reopen behavior, active-appointment conflicts, customer booking rejection, map URL save/clear behavior, and public map links using controlled accounts.

For source rollback, retain the additive table/column and their data where possible. Dropping the closure table loses schedules and permits bookings on previously closed dates. Dropping `salons.map_url` loses saved destinations. Review database recovery separately from reverting source code.

See [the cPanel deployment guide](../DEPLOY_CPANEL.md) and [release workflow](releases.md). Production deployment remains a separate authorized step.

## Verification recorded

The final local full test suite passed with **492 tests and 2,881 assertions**. One Windows directory-symlink test was skipped. The focused map and closed-date tests also passed: **11 tests, 66 assertions**. Tests used the disposable test database.

The production asset build and formatting checks for the map feature's PHP files passed. Repository-wide formatting reported existing issues in `PublicContentController.php`, `ServicePage.php`, and `deployment/cpanel/backup.php`; those unrelated files were left untouched.

The local public salon page at `https://bookmystyle.test/salon/53` returned HTTP 200 and rendered the map link. Feature tests verified the owner forms and closure dialog. Full interactive browser and hosted smoke checks remain release verification tasks.
