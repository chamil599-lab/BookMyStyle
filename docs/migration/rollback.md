# Backup and rollback runbook

## Local baseline artifacts

The source checkpoint is the Git tag `migration-step-1-baseline`. A source bundle is stored under ignored `.local/migration-baseline/source-baseline.bundle`. Private snapshots contain configuration, uploaded files, and existing compiled assets. They must never be published, placed in a web document root, or committed to Git. Keep an encrypted off-device copy before production work.

Run from the project root after importing the actual database:

```powershell
php scripts/migration/capture-baseline.php 'C:/Program Files/MySQL/MySQL Server 26.7/bin/mysqldump.exe'
```

The helper uses the booted Laravel connection, captures schema/routes/schedule, and dumps MySQL using a temporary private option file rather than a password in command-line arguments. It deletes that option file on completion. SQLite capture uses VACUUM INTO and checks integrity/row counts. A MySQL dump verifies exit status, nonzero size, and SHA-256; it is not marked restore-tested. Database rows and table counts remain private.

The configured database was initially absent. An empty database was created at the user's request so they can import their data. A backup of an empty database is only a setup checkpoint, not a backup of the real application data. After import, capture again and perform the isolated restore below. Never run migrate:fresh or seed into imported data.

## Isolated restore drill

1. Stop writes to the local source during capture, or capture during a controlled idle window. MySQL single-transaction is consistent for transactional tables; do not perform DDL during capture. Row counts are sampled separately and may differ if concurrent writes occur.
2. Verify the SHA-256 against the private manifest using Get-FileHash.
3. Create a NEW disposable local database with a distinct name, never the source or production database. Import the SQL using an interactive MySQL client or cPanel import; do not expose passwords in command history.
4. Point a separate checkout's environment at that disposable database. Disable outbound SMTP, notifications, queues, and external payment calls.
5. Compare schema, table counts, bookings, owners, subscriptions, and media links. Do not print customer records in shared logs. Confirm Laravel boots and representative read-only pages work.
6. Record restore date, backup identifier, result, and any failures in the private manifest. Do not claim the backup is verified until this drill passes.

## Source rollback

Use a separate checkout to inspect/recover the baseline without destroying current work:

```powershell
git worktree add .local/baseline-review migration-step-1-baseline
```

Alternatively, clone the source-baseline.bundle into a separate local folder. Restore .env and APP_KEY from the matching private configuration snapshot, install dependencies from the lock files, and use the matching database/uploads. Generated assets and credentials are deliberately outside the source bundle.

Do not use git reset --hard or recursive deletion as a routine rollback. The checkpoint is local and is not pushed to any remote.

## Production rollback (future cutover)

Before cutover, capture a deployment-specific full database backup, uploaded files, exact code release, compiled assets, environment configuration, APP_KEY, document roots, proxy rules, cron and worker settings. Record the deployment time and verify a restore in staging. The workspace checkpoint is not a production backup.

If cutover fails, pause writes/workers and switch routing/assets/code to the last verified release. Keep compatible additive schema changes in place when possible. If database restore is necessary, reconcile post-backup bookings/payments before restoring; a blind restore would lose new transactions. Restore matching upload files, preserve APP_KEY, clear/rebuild caches, restart workers, and verify login, public pages, booking access, and subscription recovery before reopening writes.

Avoid down-migrating or restoring a database just to revert frontend rendering. During the migration, keep changes backward-compatible and retire endpoints only after parity and authorization gates pass.
