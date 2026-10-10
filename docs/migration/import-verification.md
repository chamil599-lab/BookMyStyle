# Imported database verification

Verified on 10 October 2026, Asia/Colombo, after the user imported data into `book-my-style`.

## Backup and restore

Private backup: `.local/migration-baseline/20261010-232903/database.sql`. Its SHA-256 is recorded in that directory's private manifest and in the sanitized baseline/environment-summary.json.

The dump was restored only into a new, uniquely named local `bms_restore_...` database. All 35 table schemas, row counts, and record fingerprints match the source. The restore database is retained for inspection; its name is recorded in private restore-target.txt and manifest.json. No source rows, migrations, or seeds were changed.

The first restore attempt exposed overlapping MySQL replication GTID metadata. The capture helper was corrected to use `--set-gtid-purged=OFF`, and a fresh backup passed the restore. The earlier verification target may remain as a disposable local artifact; no databases were dropped. Recovery backups for this app must not attempt to change server-wide GTID state.

## Read-only checks

| Check | Result |
| --- | --- |
| Application tables | 35 |
| Users / salons / bookings | 10 / 10 / 10 |
| Services / subscriptions / media records | 6 / 5 / 6 |
| Migration-history records | 51 |
| Orphaned declared foreign-key references | 0 |
| Missing original upload files | 0 |
| Missing marked generated conversions | 0 |
| Non-transactional tables | 0 |

Aggregate evidence: baseline/import-checks.json. Checks of declared foreign keys do not prove every application-level relationship or business invariant is correct. No customer records or credentials are stored in the documentation.

## Pending migrations

Read-only `php artisan migrate:status` reports two pending index migrations:

- `2026_04_29_000001_add_customer_dashboard_indexes_to_bookings_table`
- `2026_05_17_000001_add_dashboard_performance_indexes`

They were NOT run. Review compatibility and existing indexes before applying them in a later implementation step.

## Repeating the checks

```powershell
php scripts/migration/capture-baseline.php 'C:/Program Files/MySQL/MySQL Server 26.7/bin/mysqldump.exe'
php scripts/migration/verify-restore.php .local/migration-baseline/<new-backup-directory> 'C:/Program Files/MySQL/MySQL Server 26.7/bin/mysql.exe'
php scripts/migration/inspect-import.php
```

Keep the source idle between backup capture and fingerprint comparison. Restore verification refuses non-local environments, non-loopback connections, dump database-switch statements, scheduled events, and existing target names. It never overwrites the source database. Database records and credentials stay in ignored private directories; keep an encrypted off-device copy for durable recovery.

The imported-data backup gate is satisfied for local implementation. Browser/visual parity, shared booking/subscription rules, and deployment-specific backup verification remain separate migration work.
