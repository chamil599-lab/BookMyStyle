# Update the existing hosted salon features

This is an upgrade for `https://bookmystyle.limach.lk`, not a fresh installation. It adds closed-date calendars and salon map URLs. The bundle is prepared from committed source and excludes unrelated local edits, credentials, databases, uploads and dependencies. No production update has been performed merely by creating or uploading these files.

The feature branch and draft PR must be reviewed through the normal Git workflow before rollout. The package's manifest identifies its exact candidate source commit; it is not a published version tag.

## Before uploading

Back up the hosted database, current application files and public assets using cPanel. Keep the existing `.env`, `APP_KEY`, uploaded media, `storage` content and `vendor` directory. Keep the backup outside `public_html`. The helper does not create a database backup for you. Stop any queue worker during the maintenance window, and restart it after the update through the host's existing process; do not introduce a new permanent worker/cron configuration for this update.

The confirmed PHP CLI is `/usr/local/bin/php` (PHP 8.4). `/usr/bin/php` is CGI/FastCGI and is unsuitable. Do not use the old `ops/setup.php`, `--new-key`, or `--initialize` for this upgrade, and do not delete old installation completion markers.

## 1. Upload the preparation archive

In cPanel File Manager, upload and extract **01-salon-update-prepare.zip** into:

```text
/home/limachlk/bookmystyle
```

This adds only the private `ops/update-salon-features.php`, its manifest, and the two feature migration files. It does not replace live application code. Delete the uploaded ZIP after extraction. Never extract a private archive under `public_html`.

## 2. Check the installation

Add this temporary **Once Per Minute** job in cPanel Cron Jobs:

```sh
/usr/local/bin/php /home/limachlk/bookmystyle/ops/update-salon-features.php --check >> /home/limachlk/bookmystyle-salon-update.log 2>&1
```

After it runs, read this private file using File Manager:

```text
/home/limachlk/bookmystyle/storage/app/deployment/salon-features-update-report.json
```

Proceed only if `mode` is `--check` and `status` is `completed`. This checks the existing production configuration/database, application key, public path, migration files, and Composer autoload compatibility. It lists the feature migrations not yet recorded on the server. Remove the temporary cron after each mode finishes. A `completed` report from an earlier mode is not evidence that the current mode ran.

If the report says `failed`, stop the cron and resolve the failure before continuing. Keep private logs/credentials private. No success is claimed by a missing report.

## 3. Enter maintenance and migrate

After confirming the backup, run the same temporary cron with **`--down`** instead of `--check`. Verify a matching `completed` report and `maintenance: true`, then remove the cron. Visitors will see the maintenance response until the final preparation succeeds.

Next run it with **`--migrate`**. Verify a matching `completed` report, then remove the cron. It runs only these two forward migrations, retaining existing records:

- `2026_10_29_000001_create_salon_closed_dates_table`
- `2026_10_29_000002_add_map_url_to_salons_table`

The normal Laravel migration records make repeating this mode safe. It does not run other pending migrations, reset tables, seed data, or rotate the application key. If it fails, leave the site in maintenance and inspect the private report/logs. MySQL DDL is not guaranteed to roll back automatically; inspect actual migration/schema state before retrying a failed migration.

## 4. Upload the application and public update

While the site is in maintenance:

1. Extract **02-salon-update-application.zip** into `/home/limachlk/bookmystyle`, allowing replacement of the listed feature files.
2. Extract **03-salon-update-public.zip** into `/home/limachlk/public_html/bookmystyle.limach.lk`, allowing replacement of `build/manifest.json` and adding the included hashed assets.
3. Delete the uploaded ZIPs after extraction. Confirm there is no extra enclosing folder.

These are patches. Do not delete the existing application or public directory first. Do not replace the hosted `bootstrap/app.php`, `index.php`, `.env`, dependencies, media link or uploads. Keep existing hashed build assets for source rollback; the new manifest selects the new files.

## 5. Verify the files and reopen the site

Run the temporary cron with **`--prepare`**. It requires maintenance mode and the feature schema, verifies every included application/public asset against its SHA-256 manifest, refreshes configuration/route/view caches, and reopens the site only after those steps succeed.

Read the report: `mode` must be `--prepare`, `status` must be `completed`, and `maintenance` must be `false`. Remove the cron immediately. Resume any previously stopped worker using the host's existing process. No new dependency installation is needed.

## 6. Confirm the features

- Check `/up`, the homepage, owner login and a public salon detail page.
- Open **Salons > Edit > Closed dates**. Close a date with no active appointments and verify customers cannot book it; reopen it and check availability returns.
- Verify dates with pending/approved appointments show conflicts without cancelling those appointments.
- In **Salons > Edit > Basic Information > Map URL**, save a shared map link. Verify **View on map** and **Get directions** use it. Clear it to verify the address fallback.
- Verify existing bookings, salon records and uploaded images remain available.

Once verified, remove the private `ops/update-salon-features.php` and `ops/salon-features-manifest.json`. Retain the report/manifest privately with the deployment record if required. Do not publish any helper or log through the browser.

## Recovery

On failure, stop the temporary cron and keep the site in maintenance while investigating. Restore only the changed application files and the previous public build manifest/assets from the backup when reverting source. Retain the new table/column and their data: dropping them loses closed schedules/map links. Do not restore an old database over new customer transactions without a separate recovery decision.

After restoring the previous source/assets, run the helper once with **`--resume`** to refresh caches and leave maintenance mode, then remove that cron. This recovery mode does not apply migrations or verify new feature files; use it only after restoring the intended previous source. The existing application key, environment, dependencies and uploads must remain in place.
