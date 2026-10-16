# Automatic owner registration trials

New owner accounts receive one free 90-day subscription at registration. The trial starts immediately, uses the existing available pro-salon package and its limits/features, and records zero amount paid. Email verification and administrator approval remain required for their existing protected/public access rules; time awaiting approval counts toward the 90 days.

AccountService creates the trial in the account transaction for native/API/password and new Google registrations. Filament registration uses the same service; administrator-created owner accounts also start the trial inside their creation transaction. Existing Google accounts retain their current subscription history. Approval does not restart an existing registration trial, and the trial routine refuses repeat grants when any subscription history exists. The registration-only service path requires a newly created owner model and is not exposed as a public action.

If the required package is missing, inactive, deleted or outside its availability dates, owner creation rolls back with a registration error. Keep pro-salon available while owner registration is offered. The fresh-install package seeder now uses that canonical slug; no seeder or migration was run against imported data. The current imported package was checked read-only and is available.

Existing owner subscriptions were not modified or backfilled. This change does not activate trials for the four previously inspected public salons. The older administrator-controlled approval/trial behavior remains available for eligible existing accounts.

Validation: full suite **413 passed, 2,288 assertions; one Windows symlink test skipped**. Eight focused regression cases cover registration paths, exact free trial duration, approval/repeat-call protection, customer exclusion and atomic rollback when the package is unavailable. Changed PHP files pass Pint. Tests use synthetic SQLite fixtures and fake mail/notifications; no real registration, email or imported-database mutation occurred.
