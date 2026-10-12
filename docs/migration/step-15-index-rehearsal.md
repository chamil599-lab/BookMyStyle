# Step 15: isolated index migration rehearsal

The two pending index migrations were validated on a newly restored local MySQL copy. The final rehearsal checks all 35 table schemas and record fingerprints against the source before migration, then verifies expected index definitions, unchanged business rows/columns/foreign keys, a second migration run with no changes, rollback to the exact baseline, reapplication and unchanged source state.

The imported database already has a single-column occurred_at index on site_visits. The dashboard migration now skips creating a redundant index and removes its own named index on rollback only when present. On this imported schema the final migrations add six necessary composite indexes; the existing site-visit index is preserved. Both existing-index and missing-index branches have isolated SQLite regression coverage.

## Isolation incident and recovery

An initial rehearsal incorrectly retained the source connection's internal name when cloning its Laravel configuration. Laravel's migrator routed seven index DDL statements to the source while recording the two migration entries on the copy. No business rows were changed. The source migration history stayed at 51 entries.

The automatic approval review rejected the proposed source repair because the rehearsal authorization was limited to a copy. The user then explicitly approved restoring the original seven-index state. A guarded repair compared exact index definitions against the pre-change copy, verified source record fingerprints, removed only those seven accidental indexes and confirmed matching original indexes and unchanged fingerprints/history. Private repair evidence: .local/migration-baseline/step-15-source-repair.json. The source's original site-visit index was preserved.

The final helper strips inherited connection names, verifies the copy's routing key and attaches a callback that rejects writes through the original Laravel connection before execution. Regression tests reproduce Laravel's inherited-name behavior and verify both DML/DDL blocking and permitted source reads. These are application-level guardrails, not a substitute for database credentials with restricted permissions. The final isolated run passed with source schema/data unchanged from the restored baseline; it does not erase the fact that transient source DDL occurred earlier.

## Running the rehearsal

Use only an idle local workspace and a directly configured loopback MySQL connection. Run from the project root:

~~~powershell
php scripts/migration/verify-index-migrations.php --fresh 'C:/Program Files/MySQL/MySQL Server 26.7/bin/mysqldump.exe' 'C:/Program Files/MySQL/MySQL Server 26.7/bin/mysql.exe'
~~~

Alternatively, pass a checked private backup directory followed by the mysql executable. A backup that differs from the current source is rejected. Fresh mode captures a single-transaction backup without locking tables or exporting server-global replication settings. Source schemas with triggers/routines/events are rejected for separate review. Dumps containing database switches or executable/global replication objects are rejected before import.

Private backups, diagnostics and retained rehearsal databases contain application data. They remain local; configuration files containing client credentials are removed in finally blocks on success or failure. Do not publish .local or dump contents. Target database names are saved privately in target.txt; only summary statuses/counts are printed. No database is dropped automatically. This helper is intentionally restricted to the current small local dataset and is not a production-size load or lock-duration benchmark.

Final isolated artifact directory: .local/migration-baseline/index-check-20261012_185926_3198ce/. The final JSON report is also saved as .local/migration-baseline/step-15-final-rehearsal.json. Exact final directory is recorded in verification.md.

The two migrations remain unapplied in the source migration history. Rehearsal success is not production migration approval. Actual host integration, production backup restoration, lock timing, load and release approval remain required. No production deployment occurred.
