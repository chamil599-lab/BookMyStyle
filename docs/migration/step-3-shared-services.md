# Step three: shared booking and subscription rules

Implemented on 11 October 2026. React remains the active frontend. This step prepares the business layer for Blade and Filament by routing existing writes through shared services. The imported database received no migrations, seeds, or application-record changes during verification.

## Booking behavior

`app/Services/BookingService.php` handles public/API/Livewire creation, owner editing, API and Filament status changes, and customer cancellation. Existing BookingPolicy authorization remains in force.

- Owner and salon row locks serialize writes. Owner locks cover monthly quotas across every salon belonging to that owner. Conflicting writes are retried for database deadlocks; stale edits receive a conflict instead of overwriting another change.
- Services must belong to the salon's creator. Being a service's last editor does not establish ownership; public service lists follow the same rule.
- Pending and approved bookings reserve capacity. Capacity uses the peak simultaneous appointment count throughout the requested duration; adjacent appointments do not overlap. Durations round up to 30-minute slots with a 30-minute minimum.
- Public appointments must be in the future. Active appointments must start on a 30-minute increment relative to opening time and finish before closing. Missing hours retain the existing 09:00–18:00 fallback. Historical rejected/completed records do not reserve capacity.
- Reactivation and approval recheck capacity, excluding the booking being edited. Completed bookings cannot be reopened.
- Public creation forces pending status and uses the authenticated actor for account association. Management writes cannot change a booking's customer association through submitted form data.
- Public booking requires an approved owner with a current subscription/package. Administrator management retains its existing subscription/quota bypass. Quotas count creation dates within the current calendar month, including rejected/completed records; null is unlimited and zero permits no new bookings.
- Mail and notifications run after commit. Rollbacks emit neither. BookingObserver handles creation and customer status notifications/mail; the service handles cancellation and administrator-to-owner status notifications. Repeating the same status does not resend it.
- The dormant Livewire booking component now has a usable Blade view, a locked salon ID, and a ten-attempts-per-IP-per-minute submit limit. It calls the same service; the frontend cutover is still a later step.
- Public API success fields and the conflict `error`, `available_slots`, and `staff_count` fields remain compatible with the existing React booking form. Filament page validation errors map to form field paths.

## Subscription behavior approved by the user

`app/Services/SubscriptionService.php` handles requests, approvals, rejection/cancellation, pending payment edits, and trial grants. API, existing Blade checkout, and Filament creation/editing/actions use it.

1. A new request creates a separate pending record. Current paid access, payment history, dates, and package identity stay intact while approval is pending.
2. Approval of the same plan starts after the latest already-approved period ends, including previously queued renewals. Future approved records do not grant current access until their start timestamp.
3. Approval of a different plan starts immediately and cancels other unexpired approved periods. Their payment amounts, original package, dates, and snapshots remain available as history.
4. Rejecting a pending request stores `canceled`, which the existing database enum supports, and leaves the current plan active. Only pending requests can be approved or rejected. An expired/cancelled record must be renewed through a new request.
5. Owner row locks prevent duplicate pending requests and repeated approval races. Submitted packages must be active with a valid duration. New requests snapshot package details; approval uses the captured duration. Legacy requests without a duration snapshot use their package duration.
6. Only administrators approve/cancel subscriptions. Owners can submit requests and edit their own pending payment details. Historical payment details, ownership, package identity, and activation dates cannot be rewritten through the Filament edit form. The existing bulk approval action also uses the service and explicitly asks administrators to verify every payment first.
7. Access lasts until the actual expiry timestamp, including the final partial day. Legacy active records with null start dates remain usable until expiry. Days remaining rounds up; exact timestamp comparisons determine access.
8. The existing API approval trial remains 90 days and is granted at most once to an owner with no subscription history. Re-approving an owner cannot repeatedly create trials.

Payment confirmation remains manual. These changes do not implement payment-provider callbacks, reconciliation, refunds, or automatic verification. Entitlement limits continue to use the current package definition, preserving the existing quota model; snapshots preserve request/history details.

## Verification

- Focused security/migration suite: **47 tests passed, 159 assertions** (17 existing security tests and 30 new shared-service tests).
- Final full suite: **60 passed, 22 failed, 215 assertions**. All 22 failing test names exactly match the previous checkpoint. They concern the existing starter-kit routes/views awaiting the Blade authentication migration.
- Actual Filament Livewire create/edit tests exercise capacity conflicts, subscription request creation, and pending payment edits. Livewire public booking, public API conflict compatibility, subscription API approval, final-day expiry, rollback/no-notification behavior, and notification deduplication are covered.
- Real local MySQL concurrency drill passes simultaneous booking creation, simultaneous reactivation, a monthly quota shared by two salons, and duplicate subscription requests. In each case one worker succeeds and one receives the expected conflict/validation response. The drill runs migrations and synthetic fixtures exclusively in a uniquely named `bms_service_test_*` database.
- Blade compilation passes. Changed PHP files pass Pint formatting checks. Read-only imported-data inspection still reports 35 tables, 10 users, 10 salons, 6 services, 10 bookings, 5 subscriptions, 6 media items, 51 migration rows, no foreign-key orphans, no missing originals/conversions, and transactional tables.

Private evidence: `.local/migration-baseline/step-3-full-tests-final.log` and `.local/migration-baseline/service-concurrency/`. Scratch MySQL databases are retained for inspection; they contain synthetic data. Re-run the drill with `php scripts/migration/verify-service-concurrency.php`; it creates a new database rather than overwriting one.

## Checkpoint and remaining work

Tag: `migration-step-3-shared-services`. No imported-database schema change is required for this step. Reverting this source checkpoint restores the prior services/entry-point behavior; future subscription records created after deployment remain separate records and require careful data reconciliation if reverting behavior.

Next, restore Blade/session authentication and its verification/reset flows, then migrate public and customer views and complete the Filament dashboard/authorization work. Browser parity, remaining resource policies, upload review, dependency auditing, production cookie/proxy/HTTPS settings, scheduler registration, delivery retry/queue configuration, and payment integration remain outstanding. Existing starter-kit test failures must be addressed as their corresponding routes return to Blade. The two pending performance-index migrations still have not run against imported data; review them before deployment and load testing.

Filament integration follows its documented record persistence hooks: [creation](https://filamentphp.com/docs/4.x/resources/creating-records#customizing-the-creation-process) and [editing](https://filamentphp.com/docs/4.x/resources/editing-records#customizing-the-saving-process).
