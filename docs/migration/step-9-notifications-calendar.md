# Step nine: notifications, WhatsApp and calendar

Prepared 11 October 2026 (Asia/Colombo). This is a local source checkpoint; no production deployment or imported database changes were performed.

## Notifications

Customers retain their Blade inbox. Approved, verified owners and administrators now have a Filament inbox and a shared Livewire bell. Expired owners can still read account/subscription updates. The bell and management inbox poll every 30 seconds while visible; inbox actions refresh the bell immediately. This is polling, not native push delivery.

Queries and read/delete actions use the authenticated account's notification relation. Every Livewire action rechecks current verification, role and owner approval. Foreign notification IDs return 404; mutation requests are rate limited. Stored titles/messages are escaped, and imported links are replaced with fixed internal destinations, including in the retained React API. Private responses use no-store headers.

The retained notification API requires verified access. Its SSE connection lasts at most 25 seconds, rechecks account approval and bearer token validity while open, and reconnects through the retained React hook. Production worker capacity, disconnect handling and cross-worker revocation still require deployment/load verification. Native pages do not depend on SSE.

Booking notifications now use the service title, preserve deleted-service references and select role-appropriate destinations. Subscription renewal links lead to the native owner subscription journey. Mail remains suppressed during tests; real delivery is a release gate.

## WhatsApp handoff

Bookings expose an explicit Open WhatsApp action for authorized managers. It normalizes Sri Lankan local numbers and international numeric formats, rejects invalid input, encodes plain-text appointment details, preserves deleted service titles and opens wa.me with noopener/noreferrer. This prepares a message for manual review/send; it does not send messages or establish that a number belongs to the customer. Browser checks inspect the URL without contacting WhatsApp.

## Calendar

Events use salon wall time (the configured Asia/Colombo application timezone) regardless of the browser's offset. Date-range fetches are bounded to 93 days and scoped to authorized salons; access is rechecked after subscription/approval changes. Deleted services retain labels and duration. Completed appointments have their own status color.

Clicking an event opens its booking details. Calendar edit/delete actions are disabled and forged action names rejected; appointment changes go through Bookings and the shared business rules. Drag and resize are disabled. No database date/time conversion or timezone migration was applied.

## Verification and remaining gates

See [verification.md](verification.md) for final suite, build, HTTP and Chrome evidence. Tests cover account isolation, revoked access, escaped imported data, safe links, service history and calendar dates/times across offsets including midnight. Chrome exercises inbox read/delete, immediate bell refresh and the event popup with the browser set to America/Los_Angeles.

Legacy APIs and React source remain during staged retirement. Remaining work includes API/write-route reconciliation and retirement, dependency/media audit, account retention rules, real mail/payment delivery, MySQL concurrency/load checks, visual parity and production deployment checks. This stage does not certify complete security.

Checkpoint: migration-step-9-notifications-calendar. Private source bundle: .local/migration-baseline/step-9-notifications-calendar.bundle. Source rollback follows [rollback.md](rollback.md); imported records require no restore for this source-only stage.
