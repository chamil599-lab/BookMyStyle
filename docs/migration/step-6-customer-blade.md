# Step six: native customer dashboard

Implemented on 11 October 2026 (Asia/Colombo). This is a local source checkpoint; no production deployment was performed.

## Customer journeys

Verified customers now receive a Blade account overview with statistics and their next three appointments. Upcoming appointments, paginated history, saved salons and notifications use native Laravel pages, session authentication and the public site's shared navigation/theme. These routes no longer serve a React index.html or redirect to FRONTEND_URL.

The existing `/dashboard/profile` and `/dashboard/change-password` bookmarks redirect to the already-tested native `/profile` and `/settings/password` forms. These forms remain accessible without email verification so a customer can correct an address and resend verification. Profile and password forms keep their existing account layout. Pending owners retain their restricted account page; approved owners and administrators still enter their respective Filament dashboards.

Upcoming means a pending or approved appointment whose start time is still ahead, in the application's timezone. History contains every remaining appointment, including appointments earlier today and future rejected/completed bookings. Completed counts require the actual completed status; past approved appointments are not silently marked completed. The imported schema stores both salon rejection and customer cancellation as rejected, so the page labels that count and status “Cancelled / rejected.” Cancellation still follows the existing shared service's pending/approved eligibility; this step does not introduce a cancellation cutoff policy. The upcoming page provides confirmation before submitting cancellation.

Favorite listings show approved public salons, with pagination and explicit removal. Removed/unapproved salons are excluded without deleting historical favorites automatically. Notifications show escaped title/message text, pagination, mark-read/read-all and delete/clear controls. Stored notification URLs are never rendered as links; navigation uses trusted named routes. Previously issued `/dashboard/bookings` links redirect to upcoming appointments.

The native notification list refreshes when opened or after an action; it does not open the legacy continuously running SSE stream. Automatic live updates and exact React visual parity are not claimed by this checkpoint.

## Security and shared rules

- Native customer pages require authentication, the customer role and verified email, except profile/password aliases. Private account responses carry `Cache-Control: private, no-store` and `X-Robots-Tag: noindex, nofollow`.
- Booking lists and cancellation use `forCustomer`: account-owned bookings plus unclaimed guest bookings with the verified account email. Another account's booking cannot be claimed through a matching email. Cancellation checks scope again under the shared booking service's transaction and row lock. Repeated and terminal-status cancellation fail without changing state.
- Native mutation forms use Laravel CSRF protection and throttling. The legacy API cancellation and favorite toggle also require verification and throttling. Guest booking remains supported on public pages.
- `CustomerDashboard` supplies the same classification and statistics to Blade and the existing customer API. API success payload fields remain compatible. Hidden prices are suppressed in customer HTML and JSON as well as public pages. Soft-deleted service titles remain available on historical customer bookings.
- `FavoriteService` supplies API, public Livewire and customer-page mutations. Account locking serializes changes, listings exclude private salons, and removals only affect the authenticated account. The existing `favorite_salons` table is retained; no merge with `salon_favorites` was performed.
- Notification reads and deletes query the authenticated account's relation; forged identifiers return 404. Clear/read-all actions cannot affect another account.
- Existing profile forms keep uniqueness checks and clear verification on email changes. Password forms require the current password. Existing User hooks rotate remember tokens, revoke Sanctum tokens and remove other database sessions. Tests cover these protections using synthetic accounts; no production credentials or mail delivery are involved.

## Deployment, rollback and next stage

Build root assets with `npm run build` and deploy Laravel source/views plus its manifest and built files. Keep Laravel's index.php as the document entry point and retain the previous step's rewrite rules. React customer assets are no longer needed by these URLs, but do not delete the React source or remaining admin/owner integration assets until Filament parity and bookmark cutover have been validated.

No imported migrations, seeds, schema changes or record edits were performed. Verification uses isolated SQLite fixtures, suppressed mail and a local HTTP/Chrome server; read-only aggregate checks confirm imported counts and media integrity. Production Apache, real email/Google integrations, broader dependency security review and admin/owner Filament parity remain separate gates.

Checkpoint: `migration-step-6-customer-blade`. Private source bundle: `.local/migration-baseline/step-6-customer-blade.bundle`. Use [the rollback runbook](rollback.md) with step five and rebuild root assets to roll back source. A source rollback restores the transitional React customer redirects and therefore requires retaining the old bundle where used; no database restore is needed for this source-only step.

Next: complete owner/admin Filament resource and action parity, role-scoped queries, analytics/subscription approval behavior, and old dashboard bookmarks before retiring the remaining React dashboard paths.
