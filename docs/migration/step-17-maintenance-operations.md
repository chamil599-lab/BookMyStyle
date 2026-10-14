# Step 17: opt-in maintenance operations

Maintenance schedules now live in the active routes/console.php path instead of the unused legacy kernel. Both operations are disabled by default in config/operations.php and .env.example. No real .env changes, cron installation, imported-data maintenance or external notification delivery occurred.

## Command behavior

php artisan featured:purge-expired and php artisan subscriptions:notify-expiring now produce aggregate dry-run counts by default. They do not mutate records, acquire cache locks or send notifications. Actual featured cleanup requires --apply plus FEATURED_CLEANUP_ENABLED=true. Actual reminders require --send plus SUBSCRIPTION_REMINDERS_ENABLED=true. The explicit flags without the matching host opt-in return failure. This deliberately changes the old console commands' default side effects; update any reviewed host automation to the documented options.

When opted in, featured cleanup runs hourly and marks only active entries whose end timestamp has passed as inactive; it deletes no rows. The FeaturedSalon active scope now checks exact timestamps, including future starts and expiry at the current instant. Callers of that scope exclude expired records even while stored active flags await cleanup.

Opted-in subscription reminders run at 09:00 Asia/Colombo with overlap prevention. Eligible subscriptions must be currently active, end on the date three calendar days ahead, and belong to verified approved owners. Approved active renewals that already cover the expiry suppress the old-plan reminder. Pending, unverified, unapproved and future-start entries are excluded. Candidate iteration is bounded by lazyById rather than loading the entire result set.

Each successfully completed reminder is marked in cache for seven days using subscription ID and expiry timestamp, with a five-minute per-reminder lock. Repeat runs skip successful deliveries; changing the expiry permits a new reminder. Use a persistent lock-capable cache shared by workers within one environment and separate cache prefixes between environments. Clearing that cache removes deduplication evidence. A crash/partial multi-channel notification failure, or delivery longer than the lock lifetime, can still cause retries/duplicates; this is not an exactly-once outbox or proof of email delivery.

## Host activation remains pending

After approved staging/host verification, inspect the dry-run output and schedule:list using the actual host environment, validate SMTP delivery with an approved recipient and confirm persistent cache locks before changing either opt-in flag. Rebuild the host's configuration cache after a reviewed environment change. Install/enable cron only as part of approved deployment; never run schedule:run against the imported development data to test this source stage. A single scheduler host is the reviewed baseline; multiple scheduler hosts need an explicit coordination review.

Laravel's scheduling documentation describes routes/console.php registration, cache-based overlap prevention and the server cron entry: https://laravel.com/docs/12.x/scheduling. Host PHP paths, cron, queue supervision, actual mail and restart/recovery behavior remain unverified. These notifications currently deliver synchronously; queue worker setup alone does not prove reminder delivery.

Seven isolated regressions cover default dry runs, opt-in rejection, successful-send deduplication, expiry changes, approved renewal suppression, eligibility/calendar boundaries, exact featured timestamps and actual opt-in schedule registration. Notifications are faked and SQLite is in memory. Runtime schedule:list was also checked with both flags disabled (zero tasks) and enabled (the two intended tasks), using isolated SQL/cache/mail settings; it did not execute either task. Final suite results and checkpoint are recorded in verification.md.
