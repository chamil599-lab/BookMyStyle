# 18 - Monitoring and recovery

Checked 16 October 2026. Local operational safeguards passed; monitoring and production recovery remain incomplete/unverified. This category is reviewed, not a production-ready pass.

Reviewed Laravel logging/failed-job configuration, booking notification exception handling, maintenance commands/scheduling, operation flags and existing baseline/rollback instructions. Application logs default to a single file under private storage; an existing daily channel supports rotation but production log selection/retention was not verified. Failed-job persistence defaults to database-uuids; actual worker/scheduler operation is not proven by configuration. Exceptions from booking notifications are reported/logged without undoing already committed booking changes. Logs can contain exception/transport details and must be access-controlled and reviewed for sensitive content.

Maintenance flags default off. Reminder delivery requires both the --send flag and host opt-in, skips covered renewals and uses a cache lock plus success marker. Featured cleanup defaults to dry-run and requires --apply plus host opt-in. Schedules register only when enabled and use withoutOverlapping; reminders run at 09:00 Asia/Colombo. Added a delivery-failure test proving a failed reminder can retry successfully without being falsely marked sent or leaving its lock held.

Validation: **24 tests passed, 115 assertions**, across MaintenanceOperationsTest, AccountDeletionTest and DeploymentReadinessTest. Tests cover dry-run/opt-in, reminder deduplication/retry, eligibility/timing, schedules, cleanup restrictions, transaction rollback in credential deletion and readiness gates. Synthetic SQLite/cache fixtures and fake/mocked notifications only; no imported database writes, emails, host cron changes or actual maintenance actions occurred.

Reviewed docs/migration/rollback.md: it specifies matching data/uploads/code/environment/APP_KEY backups, integrity/checksum checks, isolated restoration, private evidence, and post-backup transaction reconciliation before database rollback. No new backup or restore was performed. Existing local evidence does not establish automatic off-site production backups or recovery targets.

Outstanding findings: no demonstrated production alert pipeline, scheduler heartbeat or queue/mail monitoring; no complete durable administrative security audit trail; source-default single-file logging needs rotation; no verified automatic off-site backup schedule, retention or production restore/rollback rehearsal. Reminder markers are cache-based, so cache loss or a crash after sending can still allow a duplicate. These cannot be certified by passing local tests.

Added [an operations runbook](operations-runbook.md) with concrete logging/redaction, alerts, audit trails, scheduler/worker, backup and restore verification work. No external monitoring service was connected or messaged. Reference: [OWASP logging guidance](https://cheatsheetseries.owasp.org/cheatsheets/Logging_Cheat_Sheet.html).

All 18 checklist categories have now been reviewed locally. Strict script CSP (item 16), production/integration verification (item 17) and operational monitoring/recovery work remain open. The checklist is not a blanket security certification or release approval.
