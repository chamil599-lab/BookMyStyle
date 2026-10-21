# Fresh cPanel installation without Terminal

Target: https://bookmystyle.limach.lk

These packages are prepared for PHP 8.4, private application path `/home/limachlk/bookmystyle`, and public document root `/home/limachlk/public_html/bookmystyle.limach.lk`. They do not contain the development `.env`, database, accounts, uploaded media, or an administrator password. Do not extract the application archive into the public document root.

## Upload

1. In File Manager, enable **Settings → Show Hidden Files**.
2. Upload and extract `bookmystyle-application.zip` in `/home/limachlk/bookmystyle`. `artisan`, `vendor` and `bootstrap` must appear directly inside this folder, without another enclosing folder. Delete the uploaded ZIP after extraction.
3. Upload and extract `bookmystyle-public.zip` in `/home/limachlk/public_html/bookmystyle.limach.lk`. `index.php`, `.htaccess`, `build`, `css`, `js` and `images` belong directly here. Remove a hosting placeholder `index.html` if present. Delete this ZIP after extraction.
4. In the private application folder, copy `.env.example` to `.env`. Enter the actual MySQL credentials, SMTP credentials, and Google client credentials if Google login will be enabled. Keep `APP_KEY=` blank for this fresh install. Leave reminders, push and cleanup disabled initially. Set `.env` permissions to 0600 if supported by the PHP handler; otherwise use the host's recommended private permissions. Do not use 0777.
5. Under `/home/limachlk/bookmystyle/ops`, copy `admin.json.example` to `admin.json`. Fill the three JSON values with your real administrator name, email, and a unique password of at least 16 characters. Keep valid JSON, including escaping any double quotes or backslashes. This file remains outside the document root and is deleted automatically after successful initialization.
6. Confirm the subdomain uses PHP 8.4 with the required extensions and that HTTPS works. The confirmed CLI executable on this host is `/usr/local/bin/php` (PHP 8.4.26). `/usr/bin/php` is CGI/FastCGI and must not be used for this CLI-only helper. Reconfirm the CLI path if moving to another host.

## Run setup through temporary Cron Jobs

In cPanel **Cron Jobs**, choose **Once Per Minute**. Add only one setup job at a time. This is a private CLI helper; do not open it through the browser. No database schema or administrator has been created just by uploading the ZIPs.

First run:

```sh
/usr/local/bin/php /home/limachlk/bookmystyle/ops/setup.php --new-key >> /home/limachlk/bookmystyle-setup.log 2>&1
```

Wait for the next minute, then use File Manager to read:

```text
/home/limachlk/bookmystyle/storage/app/deployment/setup-report.json
```

Proceed only when `mode` matches the current command and `status` is `completed` or `already_completed`. Remove the cron entry when it finishes. Do not share passwords, the key, or complete private logs. A missing report may indicate a wrong PHP path; inspect `/home/limachlk/bookmystyle-setup.log` privately.

Repeat the same command, replacing only its mode, in this order:

| Mode | Result |
| --- | --- |
| `--check` | Verifies PHP/extensions, production configuration, key and database connection |
| `--migrate` | Runs forward migrations into the new MySQL database; no reset or seeding |
| `--initialize` | Creates roles, subscription plans and your approved/verified administrator; no demo users or salons |
| `--prepare` | Publishes Filament assets, links uploaded images and builds configuration/route/view caches |

Delete each temporary cron entry after success. The helper uses a private lock and completion markers so successfully completed modes do not repeat. If a report says `failed`, stop and resolve that mode before proceeding. Do not delete completion markers to rerun initialization or key generation.

After initialization, verify that `ops/admin.json` was automatically removed. Review the seeded subscription plans and prices in the dashboard before opening paid subscriptions to customers. The trial plan is `pro-salon`, with the existing registration trial behavior.

After preparation, if changing `.env`, remove **only** `bootstrap/cache/config.php` and `storage/app/deployment/prepare.done` using File Manager, then run `--prepare` again. Do not remove uploads or database records.

## Verify

Check these URLs:

- https://bookmystyle.limach.lk/up — health response
- https://bookmystyle.limach.lk — homepage
- https://bookmystyle.limach.lk/filament/login — administrator login
- https://bookmystyle.limach.lk/api/v1/catalogue/salons?per_page=1 — JSON, initially an empty list
- https://bookmystyle.limach.lk/api/auth/me — unauthenticated JSON response with HTTP 401
- https://bookmystyle.limach.lk/privacy-policy
- https://bookmystyle.limach.lk/delete-account

Log in with the administrator credentials you entered. Check one controlled image upload, outgoing verification/reset mail, and role access. A new database has no salons or website content until you create them through the dashboard. Verify a new owner can register and receives the intended trial. Ask the host to disable script execution for uploaded media, including the storage link target.

Remove the private `ops/setup.php` after successful setup. Keep credentials and server files private. A public HTTP 500 can be diagnosed from private `storage/logs` without enabling public debug mode.

## Scheduler and mobile

After verifying delivery configuration, the scheduler can use a permanent once-per-minute cron:

```sh
/usr/local/bin/php /home/limachlk/bookmystyle/artisan schedule:run >> /home/limachlk/bookmystyle-scheduler.log 2>&1
```

The cron alone does not deliver queued push messages. Before enabling push, confirm queue-worker support with the host, set Firebase server credentials outside the public folder, and run the push migration/setup checks. Leave delivery features disabled until this is verified. Monitor and rotate private logs.

The Flutter production configuration already uses `https://bookmystyle.limach.lk/api`. Validate the live API first, then build and test the production mobile app. Uploading the backend does not change an already-installed development app.
