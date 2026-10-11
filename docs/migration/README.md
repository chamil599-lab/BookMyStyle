# Blade and Filament migration baseline

Prepared on 10 October 2026 (Asia/Colombo). This records the application before migration; it does not certify that existing behavior is safe or correct.

## Scope and evidence

The active browser entry point is `client/index.html` -> `client/src/main.jsx`, not the unused `client/src/App.jsx`. Laravel redirects public web routes to React and exposes the API. A single Filament panel already serves both admin and owner roles at `/filament`.

Locked versions: Laravel 12.32.5, Filament 4.1.0, Livewire 3.6.4, React 18.2.0. The capture runtime is PHP 8.4.26. There are two independent Vite builds: root Laravel/Filament assets and `client/` React assets. `frontend/` contains only a Tailwind configuration.

Read these documents before changing behavior:

- [Feature inventory and parity checklist](feature-inventory.md)
- [Booking, authentication, and subscription behavior](current-behavior.md)
- [Integrations and migration risks](integrations-and-risks.md)
- [Backup and rollback runbook](rollback.md)
- [Verification results and outstanding gates](verification.md)
- [Step two security changes](step-2-security.md)
- [Imported database verification](import-verification.md)
- [Step three shared booking and subscription services](step-3-shared-services.md)
- [Step four Blade authentication and Filament session handoff](step-4-blade-auth.md)
- [Step five public Blade pages and security checks](step-5-public-blade.md)
- [Step six native customer dashboard and account journeys](step-6-customer-blade.md)
- [Step seven secure Filament management foundation](step-7-filament-management.md)
- [Step eight Filament reporting and administrative actions](step-8-filament-reporting.md)
- [Step nine notifications, WhatsApp and calendar](step-9-notifications-calendar.md)

Machine-readable evidence is in `baseline/`: the booted Laravel route collection, scheduler events, and a sanitized environment summary. The database schema is emitted only when the configured database is reachable. Database records, credentials, build/test logs, uploads, and backup archives belong exclusively under ignored `.local/migration-baseline/`.

## Step-one acceptance

- [x] Identify active frontend, routes, panel resources, business rules, and integrations.
- [x] Record known differences that must be resolved, rather than silently reproduced.
- [x] Capture runtime routes and scheduler registration.
- [x] Prepare local source checkpoint and private file preservation.
- [x] Back up the actual application database and verify an isolated restore.
- [ ] Capture authenticated browser journeys and visual references against that database.

The configured local MySQL server initially reported SQLSTATE HY000 / 1049 (unknown database). At the user's request, the empty database `book-my-style` was created for their import. The user subsequently imported their data. Its 35-table backup now passes an isolated MySQL restore with matching schema, row counts, and record fingerprints. No migrations or seeds were run into the imported database. The workspace SQLite file is a separate legacy artifact. Browser/visual parity and production-specific backup gates remain open.

Step one's tagged checkpoint deliberately makes no application behavior, authentication, schema, or routing changes. Step two proceeds with source-level security fixes and isolated tests while the user prepares the database import. Populated-data backup and restore verification remain mandatory before schema changes and cutover.
